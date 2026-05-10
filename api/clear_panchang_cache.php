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

$date = $_GET['date'] ?? null;
if (!$date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    echo json_encode(['success' => false, 'message' => 'Invalid date']);
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM ai_content_cache WHERE content_type = 'panchang' AND content_key = ?");
    $stmt->execute([$date]);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Cache cleared for ' . $date
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
