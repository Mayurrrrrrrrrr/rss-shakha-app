<?php
header('Content-Type: application/json');
$text = trim($_GET['text'] ?? '');
if ($text === '') {
    echo json_encode([]);
    exit;
}
$url = "https://inputtools.google.com/request?text=" . urlencode($text) . "&itc=hi-t-i0-und&num=5";
$ctx = stream_context_create(['http' => ['timeout' => 2]]);
$response = @file_get_contents($url, false, $ctx);
if ($response) {
    echo $response;
} else {
    echo json_encode(['error' => 'Failed to fetch from Google Input Tools']);
}
