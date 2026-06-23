<?php
require_once __DIR__ . '/../config/db.php';
try {
    $pdo->exec('DELETE FROM panchang_data');
    echo "Panchang cache table truncated successfully.\n";
} catch (Exception $e) {
    echo "Error clearing cache: " . $e->getMessage() . "\n";
}
?>
