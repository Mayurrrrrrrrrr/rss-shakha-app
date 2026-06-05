<?php
require_once '../includes/auth.php';
require_once __DIR__ . '/../config/db.php';

if (!isLoggedIn() || !isAdmin()) {
    http_response_code(403);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

header('Content-Type: application/json; charset=UTF-8');

$shakhaId = getCurrentShakhaId();
if (!$shakhaId) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$date = $_GET['date'] ?? '';
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    echo json_encode(['success' => false, 'message' => 'Invalid date']);
    exit;
}

try {
    $cacheKey = "shakha_{$shakhaId}_{$date}";
    $stmt = $pdo->prepare("DELETE FROM ai_content_cache WHERE content_type='panchang' AND content_key=?");
    $stmt->execute([$cacheKey]);
    echo json_encode(['success' => true, 'message' => "Cache cleared for {$date}"]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'DB error: ' . $e->getMessage()]);
}
