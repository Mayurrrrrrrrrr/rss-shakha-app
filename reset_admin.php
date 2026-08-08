<?php
require_once "/var/www/html/sanghasthan/config/db.php";
$hash = password_hash('admin123', PASSWORD_DEFAULT);
$stmt = $pdo->prepare("UPDATE admin_users SET password = ? WHERE username = 'superadmin'");
$stmt->execute([$hash]);
echo "Password reset to admin123";
