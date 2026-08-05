<?php
require_once __DIR__ . '/config/db.php';

try {
    $sql = file_get_contents(__DIR__ . '/bulk_import.sql');
    $pdo->exec($sql);
    echo "Bulk import executed successfully.\n";
} catch (Exception $e) {
    echo "Error executing bulk import: " . $e->getMessage() . "\n";
}
