<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../app/Core/Autoloader.php';
\App\Core\Autoloader::register();
\App\Core\DB::init($pdo);

echo "Starting Database Migrations...\n";

// Ensure migrations table exists
$pdo->exec("
    CREATE TABLE IF NOT EXISTS migrations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        migration VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

$ranMigrationsQuery = $pdo->query("SELECT migration FROM migrations");
$ranMigrations = $ranMigrationsQuery->fetchAll(PDO::FETCH_COLUMN);

$migrationFiles = glob(__DIR__ . '/../database/migrations/*.sql');
if (!$migrationFiles) {
    $migrationFiles = [];
}
sort($migrationFiles);

$migratedCount = 0;

foreach ($migrationFiles as $file) {
    $migrationName = basename($file);
    if (!in_array($migrationName, $ranMigrations)) {
        echo "Running migration: {$migrationName}\n";
        
        $sql = file_get_contents($file);
        
        try {
            // Optional: Support multiple queries in a single file more robustly
            $pdo->exec($sql);
            $stmt = $pdo->prepare("INSERT INTO migrations (migration) VALUES (?)");
            $stmt->execute([$migrationName]);
            echo "Successfully applied: {$migrationName}\n";
            $migratedCount++;
        } catch (PDOException $e) {
            echo "Error running {$migrationName}: " . $e->getMessage() . "\n";
            exit(1);
        }
    }
}

if ($migratedCount === 0) {
    echo "Nothing to migrate.\n";
} else {
    echo "Done! Applied {$migratedCount} migrations.\n";
}
