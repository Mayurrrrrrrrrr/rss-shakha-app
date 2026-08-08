<?php
require '/var/www/html/sanghasthan/config/db.php';
$stmt = $pdo->query('SHOW CREATE TABLE em_attendance_sessions');
$result = $stmt->fetch(PDO::FETCH_ASSOC);
print_r($result);
?>
