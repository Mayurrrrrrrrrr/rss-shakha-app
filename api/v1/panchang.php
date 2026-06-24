<?php
require_once '../../includes/auth.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/PanchangHelper.php';
require_once __DIR__ . '/sync/auth_api.php';

header('Content-Type: application/json; charset=UTF-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

try {
    $userContext = authenticateAPIRequest();
    $shakhaId = $userContext['shakha_id'];
    if (isset($_GET['date'])) {
        $date = $_GET['date'];
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid date format']);
            exit;
        }
        $panchang = PanchangHelper::getForDate($pdo, $date, $shakhaId);
        echo json_encode([
            'status' => 'success',
            'date' => $date,
            'panchang' => $panchang
        ]);
    } else {
        $year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));
        $month = isset($_GET['month']) ? intval($_GET['month']) : intval(date('m'));
        if ($year < 1000 || $year > 9999 || $month < 1 || $month > 12) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid year or month']);
            exit;
        }
        $list = PanchangHelper::getForMonth($pdo, $year, $month, $shakhaId);
        echo json_encode([
            'status' => 'success',
            'year' => $year,
            'month' => $month,
            'panchang_list' => $list
        ]);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
