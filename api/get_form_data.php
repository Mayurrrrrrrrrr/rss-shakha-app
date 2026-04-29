<?php
require_once '../includes/auth.php';
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, 'Invalid request method');
}

$data = json_decode(file_get_contents("php://input"));
$shakhaId = $data->shakha_id ?? null;

if (!$shakhaId) {
    sendResponse(false, 'Shakha ID is required');
}

try {
    // Load all active swayamsevaks
    $stmt = $pdo->prepare("SELECT id, name FROM swayamsevaks WHERE is_active = 1 AND shakha_id = ? ORDER BY name");
    $stmt->execute([$shakhaId]);
    $swayamsevaks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Load all active activities
    $stmt = $pdo->prepare("SELECT id, name FROM activities WHERE is_active = 1 AND (shakha_id IS NULL OR shakha_id = ?) ORDER BY sort_order, id");
    $stmt->execute([$shakhaId]);
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $responseData = [
        'swayamsevaks' => $swayamsevaks,
        'activities' => $activities
    ];

    sendResponse(true, 'Form data fetched', $responseData);
} catch (Exception $e) {
    sendResponse(false, 'Database Error: ' . $e->getMessage());
}
