<?php
require_once '../includes/auth.php';
/**
 * AI-Powered Comprehensive Panchang API
 * Uses Gemini to generate full daily panchang data for a specific city
 */
require_once __DIR__ . '/../config/db.php';
requireLogin();

header('Content-Type: application/json; charset=UTF-8');

$shakhaId = getCurrentShakhaId();
if (!$shakhaId) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Fetch Shakha-specific Gemini Key and City Name
$stmt = $pdo->prepare("SELECT gemini_api_key, city_name FROM shakhas WHERE id = ?");
$stmt->execute([$shakhaId]);
$shakhaData = $stmt->fetch();
$apiKey = $shakhaData['gemini_api_key'] ?? '';
$cityName = $shakhaData['city_name'] ?? 'मुम्बई';

if (empty($apiKey)) {
    echo json_encode(['success' => false, 'message' => 'इस शाखा के लिए AI सुविधा सक्रिय नहीं है। कृपया सेटिंग्स में जाकर अपनी Gemini API Key डालें।']);
    exit;
}

$date = $_GET['date'] ?? date('Y-m-d');
$modelParam = $_GET['model'] ?? 'flash';
$forceFetch = isset($_GET['force']) && $_GET['force'] === 'true';

$modelMap = [
    'flash' => 'gemini-flash-latest',
    'pro'   => 'gemini-pro-latest',
    'std'   => 'gemini-pro'
];
$modelIdentifier = $modelMap[$modelParam] ?? $modelMap['flash'];

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
1. पंचांग की गणना 'दृक-सिद्धांत' (Drik-Siddhanta) पर आधारित होनी चाहिए।
2. तिथि (Tithi): यदि तिथि दिन के बीच में बदल रही है, तो दोनों लिखें। उदाहरण: "सप्तमी (अपराह्न 14:15 तक, तत्पश्चात अष्टमी)"।
3. नक्षत्र (Nakshatra), योग (Yoga), और करण (Karana): यदि ये दिन के बीच में बदल रहे हैं, तो सटीक समय के साथ दोनों जानकारी दें।
4. चंद्र राशि (Chandra Rashi): चंद्रमा किस राशि में है और यदि दिन में राशि बदल रही है, तो उसका विवरण दें।
5. सूर्य/चंद्र समय: {$cityName} के स्थानीय अक्षांश/देशांतर के अनुसार सटीक समय दें।
6. संवत्: विक्रम संवत्, शक संवत् और युगाब्द की सटीक संख्या और वर्ष का नाम (जैसे: क्रोधी, विश्वावसु आदि) दें।
7. माह (Month): माह का नाम स्पष्ट रूप से लिखें। यदि पूर्णिमान्त और अमान्त के अनुसार माह अलग है, तो उसे स्पष्ट लिखें (जैसे: "वैशाख (पूर्णिमान्त / अमान्त)")। संक्षिप्त शब्दों (जैसे पू. या अ.) का प्रयोग न करें।
8. त्यौहार/व्रत: उस दिन के सभी महत्वपूर्ण हिन्दू त्यौहार, व्रत (जैसे: एकादशी, प्रदोष, संकष्टी) या जयंतियाँ शामिल करें।
9. उत्तर संक्षिप्त रखें और 'vishesh' फील्ड में केवल अत्यंत महत्वपूर्ण जानकारी दें। दोहराव (Repetition) से बचें और JSON को सही ढंग से बंद करें।

निम्नलिखित JSON structure का अक्षरशः पालन करें (सभी values हिंदी में):
{
  "surya": {
    "udaya": "प्रातः HH:MM",
    "asta": "सायं HH:MM"
  },
  "chandra": {
    "udaya": "समय (यदि अगले दिन है तो उल्लेख करें)",
    "asta": "समय (यदि अगले दिन है तो उल्लेख करें)",
    "rashi": "राशि नाम (संभव हो तो समय के साथ बदलाव भी)"
  },
  "samvat": {
    "vikram": "संख्या (जैसे: 2083) और नाम",
    "shaka": "संख्या (जैसे: 1948) और नाम",
    "yugabdha": "संख्या (जैसे: 5128)"
  },
  "maah": {
    "purnimant": "माह नाम",
    "amant": "माह नाम"
  },
  "paksha": "शुक्ल या कृष्ण",
  "tithi": "नाम (समय के साथ बदलाव सहित)",
  "nakshatra": "नाम (समय के साथ बदलाव सहित)",
  "yoga": "नाम (समय के साथ बदलाव सहित)",
  "karana": "नाम (समय के साथ बदलाव सहित)",
  "rahukaal": "HH:MM से HH:MM",
  "shubh_muhurt": {
    "abhijit": "HH:MM से HH:MM",
    "amrit_kaal": "HH:MM से HH:MM या N/A",
    "vijay": "HH:MM से HH:MM",
    "ravi_yoga": "समय या N/A",
    "sarvarth_siddhi": "समय या N/A"
  },
  "vrat_tyohar": "महत्वपूर्ण त्यौहार/व्रत का नाम या null",
  "vishesh": "कोई विशेष खगोलीय या आध्यात्मिक सूचना (जैसे: पुष्य नक्षत्र, रवि योग आदि) या null"
}

उत्तर केवल JSON format में दें। गणना में {$cityName} के स्थान को ही आधार मानें।
PROMPT;

$userPrompt = "दिनांक: $formattedDate, $dayName (Date: $date)\nस्थान: $cityName, भारत\nकृपया इस दिन का सम्पूर्ण पंचांग बताएं।";

$geminiUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$modelIdentifier}:generateContent";

$payload = [
    'contents' => [
        [
            'parts' => [
                ['text' => $systemPrompt . "\n\n" . $userPrompt]
            ]
        ]
    ],
    'generationConfig' => [
        'temperature' => 0.4,
        'topP' => 0.8,
        'topK' => 40,
        'maxOutputTokens' => 2048,
        'responseMimeType' => 'application/json',
    ]
];

$ch = curl_init($geminiUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'X-goog-api-key: ' . $apiKey
    ],
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_TIMEOUT => 60,
    CURLOPT_SSL_VERIFYPEER => false
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);

if ($curlError) {
    echo json_encode(['success' => false, 'message' => 'API connection error: ' . $curlError]);
    exit;
}

if ($httpCode !== 200) {
    $err = json_decode($response, true);
    $msg = $err['error']['message'] ?? 'API Error';
    echo json_encode(['success' => false, 'message' => "Gemini API error: $msg"]);
    exit;
}

$data = json_decode($response, true);
$candidate = $data['candidates'][0] ?? null;
$finishReason = $candidate['finishReason'] ?? 'UNKNOWN';

if ($finishReason !== 'STOP') {
    echo json_encode([
        'success' => false, 
        'message' => "AI Response was incomplete (Reason: $finishReason). Please try again.",
        'debug_raw' => json_encode($data, JSON_UNESCAPED_UNICODE)
    ]);
    exit;
}

$rawText = $candidate['content']['parts'][0]['text'] ?? '';

// Improved JSON extraction: find the first { and last }
$cleanJson = '';
if (preg_match('/\{.*\}/s', $rawText, $matches)) {
    $cleanJson = $matches[0];
} else {
    $cleanJson = trim(str_replace(['```json', '```'], '', $rawText));
}

$panchangData = json_decode($cleanJson, true);

if ($panchangData) {
    // Cache the result
    try {
        $stmtSave = $pdo->prepare("REPLACE INTO ai_content_cache (content_type, content_key, response_json) VALUES ('panchang', ?, ?)");
        $stmtSave->execute([$date, json_encode($panchangData, JSON_UNESCAPED_UNICODE)]);
    } catch (Exception $e) {}

    echo json_encode([
        'success' => true,
        'date' => $date,
        'panchang' => $panchangData,
        'source' => 'ai'
    ], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'Failed to parse AI response. Check format.',
        'debug_raw' => $rawText
    ]);
}
