<?php
require_once __DIR__ . '/config/db.php';
try {
    $pdo->exec("ALTER TABLE em_rooms ADD COLUMN occupancy INT DEFAULT 0");
    echo "Added occupancy column successfully.\n";
} catch (Exception $e) {
    echo "Error or already exists: " . $e->getMessage() . "\n";
}
