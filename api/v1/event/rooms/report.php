<?php
require_once __DIR__ . '/../../config.php';
$auth = authenticateEventRequest();

try {
    $stmt = $pdo->prepare("
        SELECT 
            building, floor, 
            COUNT(id) as total_rooms, 
            SUM(capacity) as total_capacity,
            (SELECT COUNT(*) FROM em_room_allotments a WHERE a.room_id IN (SELECT id FROM em_rooms r2 WHERE r2.building = r.building AND r2.floor = r.floor AND r2.event_id = ?)) as total_occupied
        FROM em_rooms r
        WHERE event_id = ?
        GROUP BY building, floor
    ");
    $stmt->execute([$auth['event_id'], $auth['event_id']]);
    $breakdown = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $total_rooms = 0; $total_cap = 0; $total_occ = 0;
    foreach($breakdown as $b) {
        $total_rooms += $b['total_rooms'];
        $total_cap += $b['total_capacity'];
        $total_occ += $b['total_occupied'];
    }
    
    sendResponse(true, "Room report", [
        'total_rooms' => $total_rooms,
        'total_capacity' => $total_cap,
        'total_occupied' => $total_occ,
        'breakdown' => $breakdown
    ]);
} catch (PDOException $e) {
    sendResponse(false, "Database error: " . $e->getMessage());
}
