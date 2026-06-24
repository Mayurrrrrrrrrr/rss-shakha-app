<?php
require_once '../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/PanchangHelper.php';
require_once __DIR__ . '/sync/auth_api.php';

header('Content-Type: application/json; charset=UTF-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

// Could be called with normal web session or API token
$shakhaId = 1; // Default to shakha ID 1 for guests
$userContext = authenticateAPIRequest(false);
if ($userContext && isset($userContext['shakha_id'])) {
    $shakhaId = $userContext['shakha_id'];
} elseif (isset($_SESSION['shakha_id'])) {
    $shakhaId = $_SESSION['shakha_id'];
}

$date = $_GET['date'] ?? date('Y-m-d');

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid date format']);
    exit;
}

try {
    $panchang = PanchangHelper::getForDate($pdo, $date, $shakhaId);

    echo json_encode([
        'status' => 'success',
        'date' => $date,
        'cached' => true,
        'panchang' => $panchang
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
