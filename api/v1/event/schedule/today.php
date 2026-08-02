<?php
require_once __DIR__ . '/../../config.php';
$auth = authenticateEventRequest();

try {
    $stmt = $pdo->prepare("
        SELECT * FROM em_schedule 
        WHERE event_id = ? AND activity_date = CURDATE() AND start_time >= CURTIME() 
        ORDER BY start_time ASC
    ");
    $stmt->execute([$auth['event_id']]);
    sendResponse(true, "Today's schedule retrieved", $stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (PDOException $e) {
    sendResponse(false, "Database error: " . $e->getMessage());
}
