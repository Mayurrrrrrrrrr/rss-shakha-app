<?php
require_once "/var/www/html/sanghasthan/config/db.php";
$stmt = $pdo->query("SELECT id, username, role, shakha_id FROM admin_users LIMIT 5");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
