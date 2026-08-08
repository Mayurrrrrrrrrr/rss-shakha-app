<?php
require_once __DIR__ . '/config/db.php';
try {
    $stmt = $pdo->query("SHOW CREATE TABLE em_organizers");
    print_r($stmt->fetch(PDO::FETCH_ASSOC));
} catch(Exception $e) {
    echo $e->getMessage();
}
