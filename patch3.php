<?php
require_once __DIR__ . '/config/db.php';
try {
    $pdo->exec("ALTER TABLE em_organizers ADD COLUMN vyavastha ENUM('all','hajiri','bhojan','nivas') DEFAULT NULL AFTER role");
    echo "Added vyavastha column successfully.\n";
} catch (Exception $e) {
    echo "Error or already exists: " . $e->getMessage() . "\n";
}
// Set admin users to 'all' vyavastha by default
try {
    $pdo->exec("UPDATE em_organizers SET vyavastha = 'all' WHERE role = 'admin'");
    echo "Set admin vyavastha to 'all'.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
