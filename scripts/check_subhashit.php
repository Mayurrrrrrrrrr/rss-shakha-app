<?php
require_once __DIR__ . '/../config/db.php';
$stmt = $pdo->query("SELECT id FROM amrit_vachan ORDER BY id ASC LIMIT 20");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
