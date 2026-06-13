<?php
require_once '../includes/auth.php';
/**
 * AI Content API — Unified Gemini Endpoint for Content Generation
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/sync/auth_api.php';

$userContext = authenticateAPIRequest();
$shakhaId = $userContext['shakha_id'];

header('Content-Type: application/json; charset=UTF-8');

// Fetch Shakha-specific Gemini Key
$stmtKey = $pdo->prepare("SELECT gemini_api_key FROM shakhas WHERE id = ?");
$stmtKey->execute([$shakhaId]);
$apiKey = $stmtKey->fetchColumn();

if (empty($apiKey)) {
    echo json_encode(['success' => false, 'message' => 'इस शाखा के लिए AI सामग्री निर्माण सक्रिय नहीं है। कृपया सेटिंग्स में अपनी Gemini API Key डालें।']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Only POST requests allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

if (!$action) {
    echo json_encode(['success' => false, 'message' => 'Action required']);
    exit;
}

$systemPrompt = "";
$userPrompt = "";

if ($action === 'geet_meaning') {
    $lyrics = $input['lyrics'] ?? '';
    if (!$lyrics) { echo json_encode(['success' => false, 'message' => 'Lyrics required']); exit; }
    
    $systemPrompt = "तुम एक RSS शाखा के बौद्धिक प्रमुख हो। तुम्हें दिए गए गीत के बोल (lyrics) का गहरा अर्थ और भावार्थ हिंदी में बताना है। साथ ही, उस गीत के भाव से मेल खाता हुआ एक 'अमृत वचन' (प्रेरक उद्धरण/quote) भी देना है। उत्तर को स्पष्ट और संक्षिप्त रखो।";
    $userPrompt = "इस गीत का भावार्थ और एक अमृत वचन बताओ:\n" . $lyrics;

} elseif ($action === 'suggest_ghoshna') {
    $context = $input['context'] ?? '';
    if (!$context) { echo json_encode(['success' => false, 'message' => 'Context required']); exit; }
    
    $systemPrompt = "तुम एक RSS शाखा के मुख्यशिक्षक हो। तुम्हें दिए गए उत्सव या अवसर के लिए एक उपयुक्त 'घोषणा' (Slogan) का सुझाव देना है। उत्तर JSON format में होना चाहिए: {\"sanskrit\": \"संस्कृत में घोष/नारा (यदि हो)\", \"hindi\": \"हिंदी में घोष/नारा या अर्थ\"}";
    $userPrompt = "इस अवसर के लिए एक पारंपरिक RSS घोषणा का सुझाव दो: " . $context;

} elseif ($action === 'subhashit_meaning') {
    $sanskrit = $input['sanskrit'] ?? '';
    if (!$sanskrit) { echo json_encode(['success' => false, 'message' => 'Sanskrit text required']); exit; }
    
    $systemPrompt = "तुम संस्कृत भाषा के विद्वान हो। तुम्हें दिए गए सुभाषित (श्लोक) का सरल हिंदी अर्थ बताना है, और उसके कठिन शब्दों का अर्थ (शब्दार्थ) JSON format में देना है। उत्तर केवल इस JSON format में होना चाहिए, कोई अतिरिक्त टेक्स्ट नहीं: {\"hindi_meaning\": \"हिंदी अर्थ यहाँ\", \"shabdarth\": [{\"shabd\": \"संस्कृत शब्द\", \"arth\": \"हिंदी अर्थ\"}, ...]}";
    $userPrompt = "इस सुभाषित का अर्थ और शब्दार्थ बताओ:\n" . $sanskrit;

} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
}

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
        'maxOutputTokens' => 2048,
    ]
];

// Force JSON mode for specific endpoints
if ($action === 'suggest_ghoshna' || $action === 'subhashit_meaning') {
    $payload['generationConfig']['responseMimeType'] = "application/json";
}

$cacheKey = $action . '_' . md5($systemPrompt . "\n\n" . $userPrompt);
$aiText = null;
$fallbackToCache = false;

try {
    $ch = curl_init($geminiUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'X-goog-api-key: ' . $apiKey
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 3, // 3 seconds timeout
        CURLOPT_SSL_VERIFYPEER => false,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError || $httpCode !== 200 || !$response) {
        throw new Exception($curlError ?: 'HTTP error ' . $httpCode);
    }

    $result = json_decode($response, true);
    $aiText = $result['candidates'][0]['content']['parts'][0]['text'] ?? null;
    if (!$aiText) {
        throw new Exception('Empty response from AI');
    }

    // Save successful response to cache
    try {
        $stmtSave = $pdo->prepare("INSERT INTO ai_content_cache (content_type, content_key, response_json) VALUES ('ai_content', ?, ?) ON DUPLICATE KEY UPDATE response_json = VALUES(response_json)");
        $stmtSave->execute([$cacheKey, $aiText]);
    } catch (Exception $ex) {}

} catch (Exception $e) {
    // Fallback to cache
    $fallbackToCache = true;
    try {
        $stmtCache = $pdo->prepare("SELECT response_json FROM ai_content_cache WHERE content_type = 'ai_content' AND content_key = ?");
        $stmtCache->execute([$cacheKey]);
        $aiText = $stmtCache->fetchColumn();
    } catch (Exception $ex) {}

    if (!$aiText) {
        echo json_encode(['success' => false, 'message' => 'Request timed out or failed and no cached data available.']);
        exit;
    }
}

if ($action === 'suggest_ghoshna' || $action === 'subhashit_meaning') {
    $parsed = json_decode($aiText, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        $responsePayload = ['success' => true, 'result' => $parsed];
        if ($fallbackToCache) {
            $responsePayload['cached'] = true;
        }
        echo json_encode($responsePayload);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to parse AI JSON response: ' . $aiText]);
    }
} else {
    $responsePayload = ['success' => true, 'result' => $aiText];
    if ($fallbackToCache) {
        $responsePayload['cached'] = true;
    }
    echo json_encode($responsePayload);
}
