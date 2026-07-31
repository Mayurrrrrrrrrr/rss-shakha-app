<?php
require_once __DIR__ . '/../config/db.php';
$stmt = $pdo->query("SELECT shakha_id, last_subhashit_id FROM daily_message_config");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

$stmt2 = $pdo->query("SELECT id, message_date, channel, status, subhashit_id FROM daily_message_log WHERE channel = 'whatsapp' ORDER BY id DESC LIMIT 10");
print_r($stmt2->fetchAll(PDO::FETCH_ASSOC));
