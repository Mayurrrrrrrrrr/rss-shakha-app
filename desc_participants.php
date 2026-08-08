<?php
require_once "/var/www/html/sanghasthan/config/db.php";
$stmt = $pdo->query("DESCRIBE em_participants");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
