<?php
/**
 * Database Configuration
 * ============================
 * FREE HOSTING (page.gd) - Update these values from your cPanel/phpMyAdmin
 * ============================
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('DB_HOST', 'localhost');
define('DB_NAME', 'sanghasthan');
define('DB_USER', 'root');
define('DB_PASS', 'asjhb5465%&55fss');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        )
    );
} catch (PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}
