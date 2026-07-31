<?php
require_once __DIR__ . '/../config/db.php';
$pdo->query("UPDATE daily_message_config SET last_amritvachan_id = 19 WHERE shakha_id = 1");
$stmt = $pdo->query("SELECT shakha_id, evening_send_time, last_amritvachan_id FROM daily_message_config");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

$stmt2 = $pdo->query("SELECT id, message_date, channel, status FROM daily_message_log WHERE channel = 'whatsapp_evening' ORDER BY id DESC LIMIT 10");
print_r($stmt2->fetchAll(PDO::FETCH_ASSOC));
