<?php
require_once __DIR__ . '/../config/db.php';

try {
    $columnsToAdd = [
        'yoga' => "VARCHAR(50) DEFAULT '—'",
        'karana' => "VARCHAR(50) DEFAULT '—'",
        'rahukaal' => "VARCHAR(100) DEFAULT '—'",
        'chandra_udaya' => "VARCHAR(20) DEFAULT '—'",
        'chandra_asta' => "VARCHAR(20) DEFAULT '—'",
        'shubh_muhurt' => "TEXT NULL"
    ];

    foreach ($columnsToAdd as $col => $definition) {
        // Check if column exists
        $stmt = $pdo->query("SHOW COLUMNS FROM panchang_data LIKE '$col'");
        if ($stmt->rowCount() === 0) {
            $pdo->exec("ALTER TABLE panchang_data ADD COLUMN $col $definition");
            echo "Added column '$col' to panchang_data.\n";
        } else {
            echo "Column '$col' already exists in panchang_data.\n";
        }
    }
    echo "Panchang table migration completed successfully.\n";
} catch (PDOException $e) {
    echo "Error modifying panchang_data table: " . $e->getMessage() . "\n";
}
?>
