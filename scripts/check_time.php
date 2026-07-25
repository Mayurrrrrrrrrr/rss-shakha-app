<?php
require_once __DIR__ . '/../config/db.php';
$stmt = $pdo->query("SELECT evening_send_time FROM daily_message_config WHERE shakha_id = 1");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
