<?php
require_once 'config/db.php';
try {
    $pdo->exec("ALTER TABLE em_events MODIFY COLUMN status VARCHAR(20) DEFAULT 'inactive'");
    echo "Column status updated successfully to VARCHAR(20).<br>";
} catch (Exception $e) {
    echo "Error modifying status column: " . $e->getMessage() . "<br>";
}
?>
