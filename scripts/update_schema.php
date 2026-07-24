<?php
require_once __DIR__ . '/../config/db.php';

$queries = [
    "ALTER TABLE daily_message_config ADD COLUMN evening_whatsapp_enabled TINYINT(1) DEFAULT 0 AFTER send_time",
    "ALTER TABLE daily_message_config ADD COLUMN evening_send_time TIME DEFAULT '16:00:00' AFTER evening_whatsapp_enabled",
    "ALTER TABLE daily_message_config ADD COLUMN last_amritvachan_id INT DEFAULT 0 AFTER last_subhashit_id"
];

foreach ($queries as $sql) {
    try {
        $pdo->exec($sql);
        echo "Executed: $sql\n";
    } catch (PDOException $e) {
        echo "Error or already exists: " . $e->getMessage() . "\n";
    }
}
?>
