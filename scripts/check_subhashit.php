<?php
require_once __DIR__ . '/../config/db.php';
$stmt = $pdo->query("SELECT id, sanskrit_text, hindi_meaning FROM subhashits WHERE id IN (48, 49, 50)");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
