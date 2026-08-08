<?php
require_once "/var/www/html/sanghasthan/config/db.php";
try {
    $pdo->exec("ALTER TABLE em_organizers ADD COLUMN assigned_bhag VARCHAR(255) DEFAULT NULL AFTER role");
    echo "Successfully added assigned_bhag column.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "Column already exists.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
