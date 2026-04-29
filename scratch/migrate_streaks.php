<?php
require_once __DIR__ . '/../config/db.php';

try {
    // Add streak columns
    $pdo->exec("ALTER TABLE swayamsevaks 
        ADD COLUMN IF NOT EXISTS current_streak INT DEFAULT 0, 
        ADD COLUMN IF NOT EXISTS longest_streak INT DEFAULT 0;");
    
    echo "Migration successful: Streak columns added.\n";
} catch (PDOException $e) {
    // Handle "Duplicate column" error if IF NOT EXISTS isn't supported or columns exist
    if ($e->getCode() == '42S21') {
        echo "Columns already exist.\n";
    } else {
        echo "Migration failed: " . $e->getMessage() . "\n";
    }
}
?>
