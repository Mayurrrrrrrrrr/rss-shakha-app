<?php
require_once __DIR__ . '/config/db.php';
$event_id = 1;

try {
    $stmt = $pdo->prepare("SELECT COALESCE(category, 'अज्ञात') as category, COUNT(*) as cnt FROM em_participants WHERE event_id = ? GROUP BY category ORDER BY cnt DESC");
    $stmt->execute([$event_id]);
    $catData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($catData, JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage();
}
