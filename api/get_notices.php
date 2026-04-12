<?php
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
    $stmt = $pdo->prepare("SELECT * FROM notices WHERE shakha_id = ? ORDER BY created_at DESC LIMIT 20");
    $stmt->execute([$shakhaId]);
    $notices = $stmt->fetchAll(PDO::FETCH_ASSOC);

    sendResponse(true, 'Notices fetched', $notices);
} catch (Exception $e) {
    sendResponse(false, 'Database Error: ' . $e->getMessage());
}
