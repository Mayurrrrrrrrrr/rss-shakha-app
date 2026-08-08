<?php
require_once __DIR__ . '/config/db.php';

try {
    $stmt = $pdo->prepare("UPDATE em_organizers SET vyavastha = 'hajiri' WHERE username = '9999999999' OR phone = '9999999999'");
    $stmt->execute();
    echo "Updated " . $stmt->rowCount() . " row(s).\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
