<?php
require_once __DIR__ . '/../config/db.php';

try {
    // Check if gat column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM swayamsevaks LIKE 'gat'");
    $gatExists = $stmt->fetch();

    if (!$gatExists) {
        $pdo->exec("ALTER TABLE swayamsevaks ADD COLUMN gat VARCHAR(100) DEFAULT NULL AFTER category");
        echo "Column 'gat' added successfully.\n";
    } else {
        echo "Column 'gat' already exists.\n";
    }

    // Check if is_gat_nayak column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM swayamsevaks LIKE 'is_gat_nayak'");
    $isGatNayakExists = $stmt->fetch();

    if (!$isGatNayakExists) {
        $pdo->exec("ALTER TABLE swayamsevaks ADD COLUMN is_gat_nayak TINYINT(1) DEFAULT 0 AFTER gat");
        echo "Column 'is_gat_nayak' added successfully.\n";
    } else {
        echo "Column 'is_gat_nayak' already exists.\n";
    }

} catch (Exception $e) {
    die("Database migration error: " . $e->getMessage() . "\n");
}
