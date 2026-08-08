<?php
require_once __DIR__ . '/config/db.php';
try {
    $stmt = $pdo->query("DESCRIBE em_events");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($columns);
} catch (Exception $e) {
    echo $e->getMessage();
}
