<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/PanchangHelper.php';
try {
    $res = PanchangHelper::getForDate($pdo, '2026-06-23', 1);
    echo json_encode($res, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
}
?>
