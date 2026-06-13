<?php
require_once __DIR__ . '/../config/db.php';
$u = $pdo->query("SELECT id, username, role, shakha_id FROM admin_users")->fetchAll();
print_r($u);
?>
