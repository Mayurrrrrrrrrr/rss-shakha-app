<?php
require_once 'config/db.php';
try {
    $pdo->query('ALTER TABLE panchang_data ADD COLUMN amant_month VARCHAR(50) AFTER vikram_month');
} catch (Exception $e) {
    // Column might already exist, ignore
}
$pdo->query('TRUNCATE TABLE panchang_data');
echo 'Altered and Truncated!';
?>
