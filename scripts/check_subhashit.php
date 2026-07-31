<?php
require_once __DIR__ . '/../config/db.php';
$stmt = $pdo->query("SELECT id, sanskrit_text, hindi_meaning, subhashit_date FROM subhashits ORDER BY id DESC LIMIT 5");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
