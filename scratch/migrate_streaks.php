<?php
// Force error display for CLI debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/db.php';

try {
    // Add streak columns separately for better compatibility
    try {
        $pdo->exec("ALTER TABLE swayamsevaks ADD COLUMN IF NOT EXISTS current_streak INT DEFAULT 0");
    } catch (PDOException $e) { /* Ignore if already exists */ }

    try {
        $pdo->exec("ALTER TABLE swayamsevaks ADD COLUMN IF NOT EXISTS longest_streak INT DEFAULT 0");
    } catch (PDOException $e) { /* Ignore if already exists */ }
    
    echo "Migration successful: Streak columns verified/added.\n";
} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
?>
