<?php
require_once "/var/www/html/sanghasthan/config/db.php";
$stmt = $pdo->query("DESCRIBE em_organizers");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
