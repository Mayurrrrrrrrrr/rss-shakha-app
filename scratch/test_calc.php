<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/PanchangHelper.php';

$shakhaId = 1;
$date = '2026-06-20';

try {
    $data = PanchangHelper::getForDate($pdo, $date, $shakhaId);
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
