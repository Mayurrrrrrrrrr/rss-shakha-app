<?php
require_once __DIR__ . '/../../config.php';
$auth = authenticateEventRequest();
try {
    $stmt = $pdo->prepare("
        SELECT r.*, 
               (SELECT COUNT(*) FROM em_room_allotments a WHERE a.room_id = r.id) as current_occupant_count
        FROM em_rooms r
        WHERE r.event_id = ?
    ");
    $stmt->execute([$auth['event_id']]);
    sendResponse(true, "Rooms retrieved", $stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (PDOException $e) {
    sendResponse(false, "Database error: " . $e->getMessage());
}
