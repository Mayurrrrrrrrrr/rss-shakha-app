<?php
require_once __DIR__ . '/../../config.php';
$auth = authenticateEventRequest();
try {
    $stmt = $pdo->prepare("SELECT * FROM em_work_categories WHERE event_id = ? ORDER BY sort_order ASC");
    $stmt->execute([$auth['event_id']]);
    sendResponse(true, "Categories retrieved", $stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (PDOException $e) {
    sendResponse(false, "Database error: " . $e->getMessage());
}
