<?php
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['action'] = 'test_send';
$_SESSION['user_id'] = 1;
$_SESSION['shakha_id'] = 1;
$_SESSION['role'] = 'mukhya_shikshak';
define('BASE_PATH_TEST', '/var/www/html/sanghasthan');

try {
    require BASE_PATH_TEST.'/pages/daily_message_settings.php';
} catch (\Throwable $e) {
    echo "CAUGHT THROWABLE: " . $e->getMessage() . "\n";
}
