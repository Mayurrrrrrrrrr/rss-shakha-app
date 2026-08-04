<?php
require_once __DIR__ . '/config/db.php';
try {
    $sql = "ALTER TABLE em_participants 
            ADD COLUMN organization VARCHAR(100) AFTER event_id,
            ADD COLUMN level_type VARCHAR(100) AFTER organization,
            ADD COLUMN responsibility VARCHAR(100) AFTER level_type,
            ADD COLUMN sangh_shikshan VARCHAR(100) AFTER phone,
            ADD COLUMN age_group VARCHAR(50) AFTER age,
            ADD COLUMN vasti VARCHAR(100) AFTER city,
            ADD COLUMN email VARCHAR(100) AFTER vasti;";
    $pdo->exec($sql);
    echo "Added new participant columns successfully.\n";
} catch (Exception $e) {
    echo "Error or already exists: " . $e->getMessage() . "\n";
}
