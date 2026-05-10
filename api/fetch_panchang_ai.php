<?php
require_once '../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
requireLogin();

header('Content-Type: application/json; charset=UTF-8');

$shakhaId = getCurrentShakhaId();
if (!$shakhaId) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Fetch Shakha-specific Keys and City Name
$stmt = $pdo->prepare("SELECT gemini_api_key, openai_api_key, groq_api_key, use_ai_crosscheck, city_name FROM shakhas WHERE id = ?");
$stmt->execute([$shakhaId]);
$shakhaData = $stmt->fetch();
$geminiKey = $shakhaData['gemini_api_key'] ?? '';
$openaiKey = $shakhaData['openai_api_key'] ?? '';
$groqKey = $shakhaData['groq_api_key'] ?? '';
$useCrossCheck = ($shakhaData['use_ai_crosscheck'] ?? 0) == 1;
$cityName = $shakhaData['city_name'] ?? 'मुम्बई';

if (empty($geminiKey) && empty($openaiKey) && empty($groqKey)) {
    echo json_encode(['success' => false, 'message' => 'AI सुविधा सक्रिय नहीं है। कृपया सेटिंग्स में API Key डालें।']);
    exit;
}

$date = $_GET['date'] ?? date('Y-m-d');
$providerParam = $_GET['provider'] ?? 'all';
$forceFetch = isset($_GET['force']) && $_GET['force'] === 'true';

// Validate date
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    echo json_encode(['success' => false, 'message' => 'Invalid date format']);
    exit;
}

// 1. Check Cache (Skip if force)
if (!$forceFetch) {
    try {
        $stmtCache = $pdo->prepare("SELECT response_json FROM ai_content_cache WHERE content_type = 'panchang' AND content_key = ?");
        $stmtCache->execute([$date]);
        $cachedData = $stmtCache->fetchColumn();
        if ($cachedData) {
            $parsed = json_decode($cachedData, true);
            echo json_encode([
                'success' => true,
                'date' => $date,
                'panchang' => $parsed,
                'source' => 'cache'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    } catch (Exception $e) {}
}

$ts = strtotime($date);
$hindiDays = ['रविवार', 'सोमवार', 'मंगलवार', 'बुधवार', 'गुरुवार', 'शुक्रवार', 'शनिवार'];
$hindiMonths = ['जनवरी', 'फ़रवरी', 'मार्च', 'अप्रैल', 'मई', 'जून', 'जुलाई', 'अगस्त', 'सितंबर', 'अक्टूबर', 'नवंबर', 'दिसंबर'];
$dayName = $hindiDays[date('w', $ts)];
$formattedDate = date('j', $ts) . ' ' . $hindiMonths[date('n', $ts) - 1] . ' ' . date('Y', $ts);

$systemPrompt = <<<PROMPT
तुम एक विश्व-प्रसिद्ध वैदिक ज्योतिषाचार्य हो। तुम्हें दी गई तारीख के लिए {$cityName} (India) के लिए अत्यंत सटीक 'दृक-सिद्धांत' आधारित हिन्दू पंचांग हिंदी में देना है।

महत्वपूर्ण निर्देश:
1. वर्तमान वर्ष: ग्रेगोरियन वर्ष 2026 है। इसका विक्रम संवत् 2083 (राक्षस संवत्सर) है। इसे संवत् 2073 न समझें।
2. तिथि गणना: तिथि के बदलने का समय (अंत-काल) ProKerela या DrikPanchang के अनुसार अत्यंत सटीक होना चाहिए।
3. माह (Month) फॉर्मेट: माह का नाम पूर्णिमान्त (Purnimant) और अमान्त (Amant) दोनों पद्धतियों के अनुसार स्पष्ट लिखें।
   उदाहरण: "ज्येष्ठ (पूर्णिमान्त) / वैशाख (अमान्त)"
4. समय: सूर्योदय, सूर्यास्त, नक्षत्र परिवर्तन आदि का समय {$cityName} के स्थानीय अक्षांश/देशांतर के अनुसार हो। जेनेरिक समय (जैसे 06:00) न दें।
5. JSON संरचना: केवल शुद्ध JSON लौटाएं।

अपेक्षित JSON प्रारूप:
{
  "surya": { "udaya": "HH:MM AM/PM", "asta": "HH:MM AM/PM" },
  "chandra": { "udaya": "HH:MM AM/PM", "asta": "HH:MM AM/PM", "rashi": "राशि नाम" },
  "samvat": { "vikram": "2083 (राक्षस)", "shaka": "1948 (क्षय)", "yugabdha": "5128" },
  "maah": { "purnimant": "पूर्णिमान्त माह", "amant": "अमान्त माह" },
  "paksha": "शुक्ल या कृष्ण",
  "tithi": "तिथि नाम (समय तक, फिर अगली तिथि)",
  "nakshatra": "नक्षत्र नाम (समय तक, फिर अगला)",
  "yoga": "योग नाम (समय तक)",
  "karana": "करण नाम (समय तक)",
  "rahukaal": "HH:MM से HH:MM",
  "shubh_muhurt": { "abhijit": "HH:MM से HH:MM", "amrit_kaal": "HH:MM से HH:MM", "vijay": "HH:MM से HH:MM", "ravi_yoga": "समय", "sarvarth_siddhi": "समय" },
  "vrat_tyohar": "त्यौहार का नाम या null",
  "vishesh": "कोई विशेष योग या टिप्पणी"
}
PROMPT;

$userPrompt = "दिनांक: $formattedDate, $dayName (Date: $date)\nस्थान: $cityName, भारत\nकृपया इस दिन का सम्पूर्ण पंचांग बताएं।";

function fetchGemini($apiKey, $prompt) {
    // Switching to gemini-pro for stability if flash is 404
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=" . $apiKey;
    $payload = ['contents' => [['parts' => [['text' => $prompt]]]]];
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 40,
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    $res = curl_exec($ch);
    $data = json_decode($res, true);
    $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
    return json_decode(extractJson($text), true);
}

function fetchOpenAI($apiKey, $prompt) {
    $url = "https://api.openai.com/v1/chat/completions";
    $payload = [
        'model' => 'gpt-4o-mini',
        'messages' => [['role' => 'user', 'content' => $prompt]],
        'temperature' => 0.2,
        'response_format' => ['type' => 'json_object']
    ];
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . $apiKey],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 40,
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    $res = curl_exec($ch);
    $data = json_decode($res, true);
    $text = $data['choices'][0]['message']['content'] ?? '';
    return json_decode($text, true);
}

function fetchGroq($apiKey, $prompt) {
    $url = "https://api.groq.com/openai/v1/chat/completions";
    $payload = [
        'model' => 'llama-3.1-8b-instant', // Stable Groq model
        'messages' => [['role' => 'user', 'content' => $prompt]],
        'temperature' => 0.2,
        'response_format' => ['type' => 'json_object']
    ];
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . $apiKey],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 40,
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    $res = curl_exec($ch);
    $data = json_decode($res, true);
    $text = $data['choices'][0]['message']['content'] ?? '';
    return json_decode($text, true);
}

function extractJson($text) {
    if (preg_match('/\{.*\}/s', $text, $matches)) return $matches[0];
    return $text;
}

$geminiData = null;
$openaiData = null;
$groqData = null;

if ($providerParam === 'gemini' || $providerParam === 'all') {
    if (!empty($geminiKey)) $geminiData = fetchGemini($geminiKey, $systemPrompt . "\n\n" . $userPrompt);
}
if ($providerParam === 'groq' || ($providerParam === 'all' && (!$geminiData || $useCrossCheck))) {
    if (!empty($groqKey)) $groqData = fetchGroq($groqKey, $systemPrompt . "\n\n" . $userPrompt);
}
if ($providerParam === 'openai' || ($providerParam === 'all' && (!$geminiData && !$groqData || $useCrossCheck))) {
    if (!empty($openaiKey)) $openaiData = fetchOpenAI($openaiKey, $systemPrompt . "\n\n" . $userPrompt);
}

$finalPanchang = $geminiData ?: ($groqData ?: $openaiData);

$sources = array_filter(['Gemini' => $geminiData, 'Groq' => $groqData, 'OpenAI' => $openaiData]);

if (count($sources) >= 2 && $providerParam === 'all') {
    $keys = array_keys($sources);
    $m1 = $sources[$keys[0]]; $m2 = $sources[$keys[1]];
    $t1 = mb_substr($m1['tithi'] ?? '', 0, 10); $t2 = mb_substr($m2['tithi'] ?? '', 0, 10);
    if (strpos($t1, $t2) === false && strpos($t2, $t1) === false) {
        $finalPanchang['vishesh'] = ($finalPanchang['vishesh'] ? $finalPanchang['vishesh'] . " | " : "") . 
            "⚠️ AI Cross-check: {$keys[0]} identifies '{$m1['tithi']}' while {$keys[1]} identifies '{$m2['tithi']}'.";
    }
}

if ($finalPanchang) {
    try {
        $stmtSave = $pdo->prepare("REPLACE INTO ai_content_cache (content_type, content_key, response_json) VALUES ('panchang', ?, ?)");
        $stmtSave->execute([$date, json_encode($finalPanchang, JSON_UNESCAPED_UNICODE)]);
    } catch (Exception $e) {}
    echo json_encode([
        'success' => true,
        'date' => $date,
        'panchang' => $finalPanchang,
        'source' => count($sources) > 1 ? 'ai-multi-model' : 'ai-' . array_key_first($sources)
    ], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to generate Panchang. All AI models failed. Please check API Keys/Quota.']);
}
