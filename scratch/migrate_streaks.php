<?php
// Force error display for CLI debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/db.php';

try {
    // Add streak columns
    $pdo->exec("ALTER TABLE swayamsevaks 
        ADD COLUMN IF NOT EXISTS current_streak INT DEFAULT 0, 
        ADD COLUMN IF NOT EXISTS longest_streak INT DEFAULT 0;");
    
    echo "Migration successful: Streak columns added.\n";
} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
?>
