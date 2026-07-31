<?php
require_once __DIR__ . '/../config/db.php';
$stmt = $pdo->query("DESCRIBE daily_message_log");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
