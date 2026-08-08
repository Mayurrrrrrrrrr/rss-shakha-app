<?php
require_once "/var/www/html/sanghasthan/config/db.php";
$stmt = $pdo->query("SELECT id, username, role FROM em_organizers LIMIT 5");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
