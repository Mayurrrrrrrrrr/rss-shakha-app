<?php
$_SERVER['REQUEST_METHOD'] = 'GET';
session_start();
$_SESSION['user_type'] = 'mukhyashikshak';
$_SESSION['user_id'] = 1;
$_SESSION['shakha_id'] = 1;
define('BASE_PATH_TEST', '/var/www/html/sanghasthan');
ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    require BASE_PATH_TEST.'/pages/daily_message_settings.php';
} catch (\Throwable $e) {
    echo "CAUGHT THROWABLE: " . $e->getMessage() . "\n" . $e->getTraceAsString();
}
