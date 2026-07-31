<?php
require_once __DIR__ . '/../config/db.php';
$stmt = $pdo->query("SELECT id, content FROM amrit_vachan WHERE id IN (14, 15, 19, 20)");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
