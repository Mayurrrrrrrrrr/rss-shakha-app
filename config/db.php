<?php
/**
 * Database Configuration
 */
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

$envFile = __DIR__ . '/../.env';
if (!file_exists($envFile)) {
    die("Configuration file (.env) missing. Please create it from .env.example");
}
$env = parse_ini_file($envFile);

try {
    $pdo = new PDO(
        "mysql:host={$env['DB_HOST']};dbname={$env['DB_NAME']};charset=utf8mb4",
        $env['DB_USER'],
        $env['DB_PASS'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
         PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
         PDO::ATTR_PERSISTENT => true]
    );
} catch (PDOException $e) {
    if (PHP_SAPI === 'cli') {
        die("Database Connection Failed: " . $e->getMessage() . "\n");
    }
    die("Database Connection Failed");
}
