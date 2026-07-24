<?php
require_once __DIR__ . '/../config/db.php';
$stmt = $pdo->query("SELECT id, content, author FROM amrit_vachan");
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
