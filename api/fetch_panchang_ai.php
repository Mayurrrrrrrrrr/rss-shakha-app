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
तुम एक वरिष्ठ और अत्यंत सटीक हिन्दू ज्योतिषाचार्य (Expert Vedic Astrologer) हो। तुम्हें दी गई तारीख के लिए {$cityName} (India) के स्थानीय सूर्योदय (Local Sunrise) के समय के आधार पर सम्पूर्ण और सटीक हिन्दू पंचांग की जानकारी हिंदी में JSON format में देनी है।

महत्वपूर्ण निर्देश (अति-आवश्यक):
1. पंचांग की गणना 'दृक-सिद्धांत' (Drik-Siddhanta) पर आधारित होनी चाहिए। यह अत्यंत महत्वपूर्ण है कि समय और तिथि की गणना सटीक हो।
2. तिथि (Tithi): यदि तिथि दिन के बीच में बदल रही है, तो दोनों लिखें और बदलने का सटीक समय (अंत-काल) दें। उदाहरण: "अष्टमी (अपराह्न 15:07 तक, तत्पश्चात नवमी)"। 
3. ध्यान दें: तिथि के बदलने का समय बहुत सटीक होना चाहिए (ProKerela/DrikPanchang के अनुसार)।
4. नक्षत्र (Nakshatra), योग (Yoga), और करण (Karana): यदि ये दिन के बीच में बदल रहे हैं, तो सटीक समय के साथ दोनों जानकारी दें।
5. चंद्र राशि (Chandra Rashi): चंद्रमा किस राशि में है और यदि दिन में राशि बदल रही है, तो उसका विवरण दें।
6. सूर्य/चंद्र समय: {$cityName} के स्थानीय अक्षांश/देशांतर के अनुसार सटीक समय दें।
7. संवत्: विक्रम संवत्, शक संवत् और युगाब्द की सटीक संख्या और वर्ष का नाम (जैसे: क्रोधी, विश्वावसु आदि) दें।
8. माह (Month): माह का नाम स्पष्ट रूप से लिखें। यदि पूर्णिमान्त और अमान्त के अनुसार माह अलग है, तो उसे स्पष्ट लिखें (जैसे: "वैशाख (पूर्णिमान्त / अमान्त)")।
9. त्यौहार/व्रत: उस दिन के सभी महत्वपूर्ण हिन्दू त्यौहार, व्रत (जैसे: एकादशी, प्रदोष, संकष्टी) या जयंतियाँ शामिल करें।
10. उत्तर संक्षिप्त रखें और 'vishesh' फील्ड में केवल अत्यंत महत्वपूर्ण जानकारी दें। JSON को सही ढंग से बंद करें।

निम्नलिखित JSON structure का अक्षरशः पालन करें (सभी values हिंदी में):
{
  "surya": { "udaya": "प्रातः HH:MM", "asta": "सायं HH:MM" },
  "chandra": { "udaya": "समय", "asta": "समय", "rashi": "राशि" },
  "samvat": { "vikram": "संख्या और नाम", "shaka": "संख्या और नाम", "yugabdha": "संख्या" },
  "maah": { "purnimant": "माह नाम", "amant": "माह नाम" },
  "paksha": "शुक्ल या कृष्ण",
  "tithi": "नाम (समय सहित)",
  "nakshatra": "नाम (समय सहित)",
  "yoga": "नाम (समय सहित)",
  "karana": "नाम (समय सहित)",
  "rahukaal": "HH:MM से HH:MM",
  "shubh_muhurt": { "abhijit": "HH:MM से HH:MM", "amrit_kaal": "HH:MM से HH:MM", "vijay": "HH:MM से HH:MM", "ravi_yoga": "समय", "sarvarth_siddhi": "समय" },
  "vrat_tyohar": "त्यौहार का नाम या null",
  "vishesh": "विशेष सूचना या null"
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
