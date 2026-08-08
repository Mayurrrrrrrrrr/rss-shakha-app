<?php
require_once "/var/www/html/sanghasthan/config/db.php";
$stmt = $pdo->query("SELECT id FROM em_events WHERE status = 'active' LIMIT 1");
echo $stmt->fetchColumn();
