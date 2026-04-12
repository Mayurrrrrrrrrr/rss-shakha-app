<?php
/**
 * One-time password reset script
 * Upload this, run it once, then DELETE it
 */
require_once '../config/db.php';

$newPassword = password_hash('admin123', PASSWORD_BCRYPT);
$stmt = $pdo->prepare("UPDATE admin_users SET password = ? WHERE username = 'admin'");
$stmt->execute([$newPassword]);

echo "✅ पासवर्ड रीसेट हो गया! अब admin / admin123 से लॉगिन करें।<br>";
echo "<strong>⚠️ इस फ़ाइल (reset_password.php) को तुरंत डिलीट करें!</strong><br>";
echo "<a href='../pages/index.php'>🔑 लॉगिन पर जाएँ</a>";
