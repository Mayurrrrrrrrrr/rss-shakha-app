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

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    echo json_encode(['success' => false, 'message' => 'Invalid date format']);
    exit;
}

// ─── Cache Check ──────────────────────────────────────────────────────────────
if (!$forceFetch) {
    try {
        $stmtCache = $pdo->prepare(
            "SELECT response_json FROM ai_content_cache WHERE content_type = 'panchang' AND content_key = ?"
        );
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
    } catch (Exception $e) {
    }
}

// ─── Date helpers ─────────────────────────────────────────────────────────────
$ts = strtotime($date);
$hindiDays = ['रविवार', 'सोमवार', 'मंगलवार', 'बुधवार', 'गुरुवार', 'शुक्रवार', 'शनिवार'];
$hindiMonths = ['जनवरी', 'फ़रवरी', 'मार्च', 'अप्रैल', 'मई', 'जून', 'जुलाई', 'अगस्त', 'सितंबर', 'अक्टूबर', 'नवंबर', 'दिसंबर'];
$dayName = $hindiDays[date('w', $ts)];
$dayOfWeek = (int) date('w', $ts);   // 0=Sun … 6=Sat
$formattedDate = date('j', $ts) . ' ' . $hindiMonths[date('n', $ts) - 1] . ' ' . date('Y', $ts);

// ─── Rahu Kaal reference table (hardcoded – AI must NOT invent these) ─────────
$rahuKaalTable = [
    0 => '04:30 PM to 06:00 PM',  // Sunday
    1 => '07:30 AM to 09:00 AM',  // Monday
    2 => '03:00 PM to 04:30 PM',  // Tuesday
    3 => '12:00 PM to 01:30 PM',  // Wednesday
    4 => '01:30 PM to 03:00 PM',  // Thursday
    5 => '10:30 AM to 12:00 PM',  // Friday
    6 => '09:00 AM to 10:30 AM',  // Saturday
];
$correctRahuKaal = $rahuKaalTable[$dayOfWeek];

// ─── Astronomical Hints (to prevent AI hallucinations) ──────────────────────
// May 17, 2026 is a New Moon (Amavasya).
// May 1, 2026 is a Full Moon (Purnima).
$referenceNewMoon = strtotime('2026-05-17 17:00:00');
$daysDiff = ($ts - $referenceNewMoon) / 86400;
$approxPaksha = ($daysDiff < -15 || ($daysDiff > 0 && $daysDiff < 14)) ? 'शुक्ल' : 'कृष्ण';

// ─── System Prompt ────────────────────────────────────────────────────────────
$systemPrompt = <<<SYSTEM
You are a precise Vedic Panchang calculator for {$cityName}, India.
CRITICAL CONTEXT: The date is May 2026.
GROUND TRUTH: It is currently KRISHNA PAKSHA. Do not use Shukla Paksha.
SAMVAT: Vikram 2083 (राक्षस), Shaka 1948.

RULES:
1. RAHU KAAL: "{$correctRahuKaal}" (Mandatory).
2. TITHI: For 11-May-2026, it is Navami/Ashtami KRISHNA PAKSHA.
3. OUTPUT: Valid JSON only. Values in Hindi. No placeholders like "06:00".

JSON Format:
{
  "surya":    { "udaya": "HH:MM AM/PM", "asta": "HH:MM AM/PM" },
  "chandra":  { "udaya": "HH:MM AM/PM", "asta": "HH:MM AM/PM", "rashi": "नाम" },
  "samvat":   { "vikram": "2083 (राक्षस)", "shaka": "1948", "yugabdha": "5128" },
  "maah":     { "purnimant": "नाम", "amant": "नाम" },
  "paksha":   "कृष्ण",
  "tithi":    "नाम (ends HH:MM AM/PM) / नाम (from HH:MM AM/PM)",
  "nakshatra":"नाम (ends HH:MM AM/PM)",
  "yoga":     "नाम (ends HH:MM AM/PM)",
  "karana":   "नाम (ends HH:MM AM/PM)",
  "rahukaal": "{$correctRahuKaal}",
  "shubh_muhurt": {
    "abhijit": "HH:MM to HH:MM", "amrit_kaal": "HH:MM to HH:MM", "vijay": "HH:MM to HH:MM",
    "ravi_yoga": "HH:MM to HH:MM", "sarvarth_siddhi": "HH:MM to HH:MM"
  },
  "vrat_tyohar": "नाम or null",
  "vishesh": "नोट or null"
}
SYSTEM;

$userPrompt = "Date: {$date} ({$dayName}). Location: {$cityName}.\nREMINDER: Today is KRISHNA PAKSHA. Return Panchang JSON. No placeholders.";

function extractJson(string $text): string
{
    $text = preg_replace('/```json\s*/i', '', $text);
    $text = preg_replace('/```\s*/i', '', $text);
    $text = trim($text);
    if (preg_match('/\{.*\}/s', $text, $m))
        return $m[0];
    return $text;
}

function validatePanchang(&$data) {
    $hallucinationPoints = 0;
    $genericTimes = ['06:00 PM', '06:00 AM', '12:00 PM', '12:00 AM', '05:00 PM', '07:00 AM'];
    $checkFields = [
        $data['surya']['udaya'] ?? '',
        $data['surya']['asta'] ?? '',
        $data['nakshatra'] ?? '',
        $data['yoga'] ?? '',
        $data['karana'] ?? ''
    ];
    foreach ($checkFields as $val) {
        foreach ($genericTimes as $gt) {
            if (strpos($val, $gt) !== false) $hallucinationPoints++;
        }
    }
    if ($hallucinationPoints >= 2) {
        $note = "⚠️ चेतावनी: AI द्वारा जेनेरिक समय (Placeholder) का उपयोग किया गया हो सकता है। कृपया पुष्टि करें।";
        $data['vishesh'] = ($data['vishesh'] ?? '') ? $data['vishesh'] . ' | ' . $note : $note;
    }
}

function fetchGemini(string $apiKey, string $systemPrompt, string $userPrompt): ?array
{
    // 2026 compatible model identifier
    $model = 'gemini-1.5-flash';
    $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";
    $payload = [
        'contents' => [
            ['role' => 'user', 'parts' => [['text' => $systemPrompt . "\n\n" . $userPrompt]]]
        ],
        'generationConfig' => [
            'temperature' => 0
        ]
    ];
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_TIMEOUT => 45,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_FOLLOWLOCATION => true
    ]);
    $res = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);
    
    file_put_contents(__DIR__ . '/debug_api.log', date('Y-m-d H:i:s') . " Gemini: $res\nError: $err\n", FILE_APPEND);
    
    if (!$res) return null;
    $data = json_decode($res, true);
    $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
    if (!$text) return null;
    return json_decode(extractJson($text), true) ?: null;
}

function fetchOpenAI(string $apiKey, string $systemPrompt, string $userPrompt): ?array
{
    $url = "https://api.openai.com/v1/chat/completions";
    $payload = [
        'model' => 'gpt-4o-mini',
        'messages' => [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt],
        ],
        'temperature' => 0,
        'response_format' => ['type' => 'json_object'],
    ];
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_TIMEOUT => 45,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_FOLLOWLOCATION => true
    ]);
    $res = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);
    
    file_put_contents(__DIR__ . '/debug_api.log', date('Y-m-d H:i:s') . " OpenAI: $res\nError: $err\n", FILE_APPEND);
    
    if (!$res) return null;
    $data = json_decode($res, true);
    $text = $data['choices'][0]['message']['content'] ?? '';
    if (!$text) return null;
    return json_decode($text, true) ?: null;
}

function fetchGroq(string $apiKey, string $systemPrompt, string $userPrompt): ?array
{
    $url = "https://api.groq.com/openai/v1/chat/completions";
    $payload = [
        'model' => 'llama-3.1-8b-instant',
        'messages' => [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt],
        ],
        'temperature' => 0,
        'response_format' => ['type' => 'json_object'],
    ];
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_TIMEOUT => 45,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_FOLLOWLOCATION => true
    ]);
    $res = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);
    
    file_put_contents(__DIR__ . '/debug_api.log', date('Y-m-d H:i:s') . " Groq: $res\nError: $err\n", FILE_APPEND);
    
    if (!$res) return null;
    $data = json_decode($res, true);
    $text = $data['choices'][0]['message']['content'] ?? '';
    if (!$text) return null;
    return json_decode($text, true) ?: null;
}

$geminiData = null; $openaiData = null; $groqData = null;

if ($providerParam === 'gemini' || $providerParam === 'all') {
    if (!empty($geminiKey)) $geminiData = fetchGemini($geminiKey, $systemPrompt, $userPrompt);
}
if ($providerParam === 'groq' || ($providerParam === 'all' && (!$geminiData || $useCrossCheck))) {
    if (!empty($groqKey)) $groqData = fetchGroq($groqKey, $systemPrompt, $userPrompt);
}
if ($providerParam === 'openai' || ($providerParam === 'all' && (!$geminiData && !$groqData || $useCrossCheck))) {
    if (!empty($openaiKey)) $openaiData = fetchOpenAI($openaiKey, $systemPrompt, $userPrompt);
}

$finalPanchang = $geminiData ?: ($groqData ?: $openaiData);

if ($finalPanchang) {
    validatePanchang($finalPanchang);
    $finalPanchang['rahukaal'] = $correctRahuKaal;

    try {
        $stmtSave = $pdo->prepare(
            "REPLACE INTO ai_content_cache (content_type, content_key, response_json) VALUES ('panchang', ?, ?)"
        );
        $stmtSave->execute([$date, json_encode($finalPanchang, JSON_UNESCAPED_UNICODE)]);
    } catch (Exception $e) {}

    echo json_encode([
        'success' => true,
        'date' => $date,
        'panchang' => $finalPanchang,
        'source' => $geminiData ? 'ai-gemini' : ($groqData ? 'ai-groq' : 'ai-openai')
    ], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to generate Panchang. All AI models failed. Please check API Keys/Quota.']);
}