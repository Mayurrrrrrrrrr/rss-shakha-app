<?php
require_once __DIR__ . '/config/db.php';
try {
    $stmt = $pdo->query("SHOW TABLES LIKE '%attendance%'");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    print_r($tables);
} catch (Exception $e) {
    echo $e->getMessage();
}
