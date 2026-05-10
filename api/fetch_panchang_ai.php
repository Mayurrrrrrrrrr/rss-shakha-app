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

$stmt = $pdo->prepare("SELECT gemini_api_key, openai_api_key, groq_api_key, use_ai_crosscheck, city_name FROM shakhas WHERE id = ?");
$stmt->execute([$shakhaId]);
$shakhaData = $stmt->fetch();
$geminiKey = $shakhaData['gemini_api_key'] ?? '';
$openaiKey = $shakhaData['openai_api_key'] ?? '';
$groqKey = $shakhaData['groq_api_key'] ?? '';
$cityName = $shakhaData['city_name'] ?? 'मुम्बई';

$date = $_GET['date'] ?? date('Y-m-d');
$providerParam = $_GET['provider'] ?? 'all';
$forceFetch = isset($_GET['force']) && $_GET['force'] === 'true';

// ─── Minimal Fetcher with file_get_contents fallback ─────────────────────────
function fetchGroq($apiKey, $prompt) {
    $url = "https://api.groq.com/openai/v1/chat/completions";
    $payload = [
        'model' => 'llama-3.3-70b-versatile',
        'messages' => [['role' => 'user', 'content' => $prompt]],
        'temperature' => 0
    ];
    $options = [
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\n" . "Authorization: Bearer $apiKey\r\n",
            'content' => json_encode($payload),
            'ignore_errors' => true,
            'timeout' => 45
        ],
        'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]
    ];
    $res = @file_get_contents($url, false, stream_context_create($options));
    file_put_contents(__DIR__ . '/debug_api.log', date('Y-m-d H:i:s') . " Groq (FGC): $res\n", FILE_APPEND);
    return json_decode($res, true);
}

// ... (simplified for final test)
$res = fetchGroq($groqKey, "Provide Panchang for $date in $cityName in Hindi JSON format.");
echo json_encode(['success' => true, 'data' => $res]);