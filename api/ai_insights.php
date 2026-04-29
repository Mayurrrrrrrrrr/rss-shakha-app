<?php
require_once __DIR__ . '/../includes/auth.php';
/**
 * AI Insights API — Gemini Free Tier
 * Collects shakha data for a date range and sends to Gemini for analysis
 */
require_once __DIR__ . '/../config/db.php';
requireLogin();

header('Content-Type: application/json; charset=UTF-8');

if (!defined('GEMINI_API_KEY') || GEMINI_API_KEY === 'YOUR_GEMINI_API_KEY_HERE') {
    echo json_encode(['success' => false, 'message' => 'Gemini API Key not configured. Please set GEMINI_API_KEY in your .env file.']);
    exit;
}

// Validate params
$fromDate = $_GET['from'] ?? null;
$toDate = $_GET['to'] ?? null;
$shakhaId = getCurrentShakhaId();

if (!$fromDate || !$toDate) {
    echo json_encode(['success' => false, 'message' => 'from and to dates are required']);
    exit;
}

// Fetch shakha name
$stmt = $pdo->prepare("SELECT name FROM shakhas WHERE id = ?");
$stmt->execute([$shakhaId]);
$shakhaName = $stmt->fetchColumn() ?: 'शाखा';

// ===== COLLECT DATA =====

// 1. Daily Records + custom messages
$stmt = $pdo->prepare("SELECT id, record_date, hindi_month, paksh, tithi, custom_message 
    FROM daily_records WHERE shakha_id = ? AND record_date BETWEEN ? AND ? ORDER BY record_date");
$stmt->execute([$shakhaId, $fromDate, $toDate]);
$dailyRecords = $stmt->fetchAll();
$recordIds = array_column($dailyRecords, 'id');

// 2. Attendance data
$attendanceData = [];
if (!empty($recordIds)) {
    $placeholders = implode(',', array_fill(0, count($recordIds), '?'));
    $stmt = $pdo->prepare("SELECT a.daily_record_id, a.is_present, s.name as swayamsevak_name, s.category 
        FROM attendance a 
        JOIN swayamsevaks s ON a.swayamsevak_id = s.id 
        WHERE a.daily_record_id IN ($placeholders)
        ORDER BY s.name");
    $stmt->execute($recordIds);
    $attendanceData = $stmt->fetchAll();
}

// 3. Activities data
$activitiesData = [];
if (!empty($recordIds)) {
    $placeholders = implode(',', array_fill(0, count($recordIds), '?'));
    $stmt = $pdo->prepare("SELECT da.daily_record_id, da.is_done, act.name as activity_name, 
        s.name as conductor_name
        FROM daily_activities da 
        JOIN activities act ON da.activity_id = act.id 
        LEFT JOIN swayamsevaks s ON da.conducted_by = s.id 
        WHERE da.daily_record_id IN ($placeholders)
        ORDER BY act.sort_order");
    $stmt->execute($recordIds);
    $activitiesData = $stmt->fetchAll();
}

// 4. Notices
$stmt = $pdo->prepare("SELECT subject, message, notice_date FROM notices 
    WHERE shakha_id = ? AND notice_date BETWEEN ? AND ? ORDER BY notice_date");
$stmt->execute([$shakhaId, $fromDate, $toDate]);
$notices = $stmt->fetchAll();

// 5. Subhashit
$stmt = $pdo->prepare("SELECT sanskrit_text, hindi_meaning, subhashit_date FROM subhashits 
    WHERE shakha_id = ? AND subhashit_date BETWEEN ? AND ? ORDER BY subhashit_date");
$stmt->execute([$shakhaId, $fromDate, $toDate]);
$subhashits = $stmt->fetchAll();

// 6. Events
$stmt = $pdo->prepare("SELECT title, description, event_date, location FROM events 
    WHERE (shakha_id IS NULL OR shakha_id = ?) AND event_date BETWEEN ? AND ? ORDER BY event_date");
$stmt->execute([$shakhaId, $fromDate, $toDate]);
$events = $stmt->fetchAll();

// 7. New Swayamsevaks joined during the period
$newSwayamsevaks = [];
try {
    $stmt = $pdo->prepare("SELECT name, category, age, created_at FROM swayamsevaks 
        WHERE shakha_id = ? AND is_active = 1 AND DATE(created_at) BETWEEN ? AND ? ORDER BY created_at");
    $stmt->execute([$shakhaId, $fromDate, $toDate]);
    $newSwayamsevaks = $stmt->fetchAll();
} catch (Exception $e) {
    // created_at column may not exist in older schemas, skip gracefully
}

// 8. Schedule Adherence — compare timetable vs actual activities
$scheduleAdherence = [];
try {
    // Fetch default timetable for each day of week
    $stmtTT = $pdo->prepare("SELECT day_of_week, slots FROM timetable_defaults WHERE shakha_id = ?");
    $stmtTT->execute([$shakhaId]);
    $timetableDefaults = [];
    foreach ($stmtTT->fetchAll() as $row) {
        $timetableDefaults[$row['day_of_week']] = json_decode($row['slots'], true) ?: [];
    }

    // For each daily record, compare its day's planned activities vs done
    if (!empty($timetableDefaults) && !empty($dailyRecords)) {
        // Build a map: record_id => [activity_names that were done]
        $doneActivitiesMap = [];
        foreach ($activitiesData as $a) {
            if ($a['is_done']) {
                $doneActivitiesMap[$a['daily_record_id']][] = $a['activity_name'];
            }
        }

        $totalScheduledTopics = 0;
        $matchedTopics = 0;
        $missedTopics = [];

        foreach ($dailyRecords as $rec) {
            $dow = date('w', strtotime($rec['record_date']));
            $plannedSlots = $timetableDefaults[$dow] ?? [];
            $doneList = $doneActivitiesMap[$rec['id']] ?? [];

            foreach ($plannedSlots as $slot) {
                $topic = trim($slot['topic'] ?? '');
                if (empty($topic)) continue;
                $totalScheduledTopics++;

                // Fuzzy match: check if any done activity contains this topic keyword or vice versa
                $found = false;
                foreach ($doneList as $doneName) {
                    if (mb_stripos($doneName, $topic) !== false || mb_stripos($topic, $doneName) !== false) {
                        $found = true;
                        break;
                    }
                }
                if ($found) {
                    $matchedTopics++;
                } else {
                    $missedTopics[$topic] = ($missedTopics[$topic] ?? 0) + 1;
                }
            }
        }

        $scheduleAdherence = [
            'total_scheduled' => $totalScheduledTopics,
            'matched' => $matchedTopics,
            'adherence_pct' => $totalScheduledTopics > 0 ? round(($matchedTopics / $totalScheduledTopics) * 100) : 0,
            'missed_topics' => $missedTopics,
        ];
    }
} catch (Exception $e) {
    // timetable tables might not exist
}

// ===== BUILD CONTEXT =====
$totalDays = count($dailyRecords);

// Attendance summary
$personAttendance = [];
foreach ($attendanceData as $a) {
    $name = $a['swayamsevak_name'];
    if (!isset($personAttendance[$name])) {
        $personAttendance[$name] = ['present' => 0, 'total' => 0, 'category' => $a['category']];
    }
    $personAttendance[$name]['total']++;
    if ($a['is_present']) $personAttendance[$name]['present']++;
}

// Activity summary
$activityCount = [];
$conductorCount = [];
foreach ($activitiesData as $a) {
    $actName = $a['activity_name'];
    if (!isset($activityCount[$actName])) $activityCount[$actName] = ['done' => 0, 'total' => 0];
    $activityCount[$actName]['total']++;
    if ($a['is_done']) $activityCount[$actName]['done']++;
    
    if ($a['conductor_name'] && $a['is_done']) {
        if (!isset($conductorCount[$a['conductor_name']])) $conductorCount[$a['conductor_name']] = 0;
        $conductorCount[$a['conductor_name']]++;
    }
}

// Build text context
$contextParts = [];
$contextParts[] = "शाखा: $shakhaName";
$contextParts[] = "अवधि: $fromDate से $toDate";
$contextParts[] = "कुल दैनिक रिकॉर्ड: $totalDays दिन";

// Attendance context
if (!empty($personAttendance)) {
    $contextParts[] = "\n--- उपस्थिति ---";
    foreach ($personAttendance as $name => $data) {
        $pct = $data['total'] > 0 ? round(($data['present'] / $data['total']) * 100) : 0;
        $cat = $data['category'] ?? 'Unknown';
        $contextParts[] = "$name (श्रेणी: $cat): $data[present]/$data[total] दिन ({$pct}%)";
    }
}

// Activity context
if (!empty($activityCount)) {
    $contextParts[] = "\n--- गतिविधियाँ ---";
    foreach ($activityCount as $name => $data) {
        $contextParts[] = "$name: $data[done]/$data[total] बार पूर्ण";
    }
}

// Conductors
if (!empty($conductorCount)) {
    arsort($conductorCount);
    $contextParts[] = "\n--- संचालक (Activity Conductors) ---";
    foreach ($conductorCount as $name => $count) {
        $contextParts[] = "$name: $count गतिविधियाँ संचालित";
    }
}

// Custom messages
$customMessages = array_filter(array_column($dailyRecords, 'custom_message'));
if (!empty($customMessages)) {
    $contextParts[] = "\n--- विशेष सूचना / दैनिक टिप्पणियाँ ---";
    foreach ($dailyRecords as $r) {
        if (!empty($r['custom_message'])) {
            $contextParts[] = "[$r[record_date]]: " . mb_substr($r['custom_message'], 0, 300);
        }
    }
}

// Notices
if (!empty($notices)) {
    $contextParts[] = "\n--- सूचनाएं (Notices) ---";
    foreach ($notices as $n) {
        $contextParts[] = "[$n[notice_date]] $n[subject]: " . mb_substr($n['message'], 0, 200);
    }
}

// Subhashits
if (!empty($subhashits)) {
    $contextParts[] = "\n--- सुभाषित ---";
    foreach ($subhashits as $s) {
        $contextParts[] = "[$s[subhashit_date]] $s[sanskrit_text]";
        if ($s['hindi_meaning']) $contextParts[] = "  अर्थ: " . mb_substr($s['hindi_meaning'], 0, 200);
    }
}

// Events
if (!empty($events)) {
    $contextParts[] = "\n--- कार्यक्रम (Events) ---";
    foreach ($events as $e) {
        $contextParts[] = "[$e[event_date]] $e[title]" . ($e['description'] ? ": " . mb_substr($e['description'], 0, 150) : "");
    }
}

// New Swayamsevaks
if (!empty($newSwayamsevaks)) {
    $contextParts[] = "\n--- नए स्वयंसेवक (इस अवधि में जुड़े) ---";
    $contextParts[] = "कुल नए सदस्य: " . count($newSwayamsevaks);
    foreach ($newSwayamsevaks as $ns) {
        $catMap = ['Baal' => 'बाल', 'Tarun' => 'तरुण', 'Praudh' => 'प्रौढ़', 'Abhyagat' => 'अभ्यागत'];
        $cat = $catMap[$ns['category'] ?? ''] ?? $ns['category'];
        $age = $ns['age'] ? " (उम्र: $ns[age])" : '';
        $contextParts[] = "$ns[name] — श्रेणी: $cat$age — जुड़े: $ns[created_at]";
    }
}

// Schedule Adherence
if (!empty($scheduleAdherence) && $scheduleAdherence['total_scheduled'] > 0) {
    $contextParts[] = "\n--- समय-सारणी पालन (Schedule Adherence) ---";
    $contextParts[] = "कुल निर्धारित गतिविधियाँ: $scheduleAdherence[total_scheduled]";
    $contextParts[] = "वास्तव में हुई: $scheduleAdherence[matched]";
    $contextParts[] = "पालन दर: $scheduleAdherence[adherence_pct]%";
    if (!empty($scheduleAdherence['missed_topics'])) {
        arsort($scheduleAdherence['missed_topics']);
        $contextParts[] = "सबसे ज्यादा छूटने वाले विषय:";
        foreach ($scheduleAdherence['missed_topics'] as $topic => $missCount) {
            $contextParts[] = "  - $topic: $missCount बार छूटा";
        }
    }
}

$dataContext = implode("\n", $contextParts);

// Check if there's any meaningful data
if ($totalDays === 0 && empty($notices) && empty($subhashits) && empty($events)) {
    echo json_encode(['success' => false, 'message' => "इस अवधि ($fromDate से $toDate) में कोई डेटा नहीं मिला। कृपया अलग तारीख चुनें।"]);
    exit;
}

// ===== GEMINI API CALL =====
$systemPrompt = "तुम एक RSS शाखा (Rashtriya Swayamsevak Sangh शाखा) के data analyst हो। तुम्हें शाखा की गतिविधियों का विश्लेषण करना है और actionable insights देनी हैं।

महत्वपूर्ण संदर्भ:
- स्वयंसेवकों की श्रेणियाँ: बाल (Baal - बच्चे), तरुण (Tarun - युवा), प्रौढ़ (Praudh - वयस्क)
- अभ्यागत (Abhyagat) शाखा के सदस्य नहीं हैं — वे अतिथि/guest हैं। वे कभी-कभी आते हैं, अपने अनुभव साझा करते हैं, और कभी-कभी वे अधिकारी (senior RSS functionaries) भी होते हैं। अभ्यागतों का आना शाखा के लिए अच्छा है, उन्हें नियमित सदस्य बनाने का सुझाव मत दो।
- नए सदस्य = बाल/तरुण/प्रौढ़ श्रेणी में जुड़े लोग (अभ्यागत नहीं)

तुम्हें Hindi (Devanagari) में जवाब देना है। Response को इन sections में बांटो:

📊 **कुल सारांश (Overall Summary)**
- कितने दिन शाखा लगी, कुल उपस्थिति का overview, नए सदस्य कितने जुड़े, अभ्यागत कितनी बार आए

👥 **उपस्थिति विश्लेषण (Attendance Analysis)**  
- सबसे नियमित स्वयंसेवक (top 5)
- सबसे कम आने वाले (need attention)
- श्रेणी-वार (Baal/Tarun/Praudh) breakdown
- अभ्यागतों की उपस्थिति (positive highlight — ये अतिथि हैं)

🆕 **नए सदस्य (New Members)**
- कितने नए स्वयंसेवक जुड़े (बाल/तरुण/प्रौढ़ में), अभ्यागत गिनती अलग रखो
- ग्रोथ रेट पर टिप्पणी

📋 **गतिविधि विश्लेषण (Activity Analysis)**
- सबसे ज्यादा होने वाली गतिविधियाँ
- छूटने वाली गतिविधियाँ  
- सबसे सक्रिय संचालक

📅 **समय-सारणी पालन (Schedule Adherence)**
- निर्धारित समय-सारणी (timetable) की तुलना में वास्तविक गतिविधियों का पालन कितना हुआ
- कौन से निर्धारित विषय सबसे ज्यादा छूटे
- अनुशासन और नियमितता पर अवलोकन

💬 **विषय-वस्तु अंतर्दृष्टि (Content Insights)**
- विशेष सूचना, सुभाषित, notices से themes/patterns

🎯 **सुझाव (Recommendations)**
- उपस्थिति बढ़ाने के उपाय
- समय-सारणी पालन सुधार
- नए सदस्य (बाल/तरुण/प्रौढ़) जोड़ने के लिए सुझाव
- अभ्यागतों को और बुलाने के सुझाव (ये सम्मानित अतिथि हैं)
- 3-4 actionable suggestions

Response concise रखो, bullet points use करो, और data-driven insights दो। If data is limited, work with what's available and mention that more data would give better insights.";

$userPrompt = "निम्नलिखित शाखा डेटा का विश्लेषण करो और insights दो:\n\n" . $dataContext;

$geminiUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent";

$payload = [
    'contents' => [
        [
            'parts' => [
                ['text' => $systemPrompt . "\n\n" . $userPrompt]
            ]
        ]
    ],
    'generationConfig' => [
        'temperature' => 0.7,
        'maxOutputTokens' => 8192,
    ]
];

$ch = curl_init($geminiUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'X-goog-api-key: ' . GEMINI_API_KEY
    ],
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_TIMEOUT => 60,
    CURLOPT_SSL_VERIFYPEER => false,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    echo json_encode(['success' => false, 'message' => 'API connection error: ' . $curlError]);
    exit;
}

$result = json_decode($response, true);

if ($httpCode !== 200 || !$result) {
    $errorMsg = $result['error']['message'] ?? 'Unknown Gemini API error (HTTP ' . $httpCode . ')';
    echo json_encode(['success' => false, 'message' => 'Gemini API error: ' . $errorMsg]);
    exit;
}

// Extract text from Gemini response
$aiText = $result['candidates'][0]['content']['parts'][0]['text'] ?? null;

if (!$aiText) {
    echo json_encode(['success' => false, 'message' => 'Gemini did not return any text response.']);
    exit;
}

echo json_encode([
    'success' => true,
    'insights' => $aiText,
    'meta' => [
        'shakha' => $shakhaName,
        'from' => $fromDate,
        'to' => $toDate,
        'records_analyzed' => $totalDays,
        'swayamsevaks_tracked' => count($personAttendance),
        'new_members' => count($newSwayamsevaks),
        'activities_tracked' => count($activityCount),
        'schedule_adherence' => $scheduleAdherence['adherence_pct'] ?? null,
        'notices_count' => count($notices),
        'subhashits_count' => count($subhashits),
        'events_count' => count($events),
    ]
]);
