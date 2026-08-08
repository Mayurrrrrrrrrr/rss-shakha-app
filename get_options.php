<?php
require_once __DIR__ . '/config/db.php';

$cols = ['organization', 'level_type', 'responsibility', 'sangh_shikshan', 'age_group', 'category'];
$data = [];
foreach($cols as $c) {
    $stmt = $pdo->query("SELECT DISTINCT `$c` FROM em_participants WHERE `$c` IS NOT NULL AND `$c` != ''");
    $data[$c] = $stmt->fetchAll(PDO::FETCH_COLUMN);
}
echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
