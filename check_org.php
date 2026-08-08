<?php
require_once "/var/www/html/sanghasthan/config/db.php";
$stmt = $pdo->query("SELECT COUNT(*) FROM em_organizers");
echo "Organizers: " . $stmt->fetchColumn() . "\n";
$stmt = $pdo->query("SELECT * FROM em_organizers");
print_r($stmt->fetchAll());
