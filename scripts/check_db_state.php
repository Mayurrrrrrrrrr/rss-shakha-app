<?php
require_once __DIR__ . '/../config/db.php';
$stmt = $pdo->query("SELECT shakha_id, evening_send_time, last_amritvachan_id FROM daily_message_config");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
