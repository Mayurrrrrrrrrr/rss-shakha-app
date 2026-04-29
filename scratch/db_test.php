<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$envFile = __DIR__ . '/../.env';
if (!file_exists($envFile)) {
    die("ERROR: .env file not found at " . realpath($envFile) . "\n");
}

$env = parse_ini_file($envFile);
if (!$env) {
    die("ERROR: Failed to parse .env file. Check for syntax errors.\n");
}

echo "Config loaded:\n";
echo "Host: " . ($env['DB_HOST'] ?? 'MISSING') . "\n";
echo "DB: " . ($env['DB_NAME'] ?? 'MISSING') . "\n";
echo "User: " . ($env['DB_USER'] ?? 'MISSING') . "\n";

try {
    $pdo = new PDO(
        "mysql:host={$env['DB_HOST']};dbname={$env['DB_NAME']};charset=utf8mb4",
        $env['DB_USER'],
        $env['DB_PASS']
    );
    echo "SUCCESS: Database connected successfully!\n";
} catch (PDOException $e) {
    echo "FAILURE: " . $e->getMessage() . "\n";
}
