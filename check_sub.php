<?php
require_once "/var/www/html/sanghasthan/config/db.php";
$stmt = $pdo->query("SELECT * FROM subhashits LIMIT 5");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
