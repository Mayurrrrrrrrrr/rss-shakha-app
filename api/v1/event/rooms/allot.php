<?php
require_once __DIR__ . '/../config.php';
$auth = authenticateEventRequest();
requireRole($auth, ['Admin', 'Coordinator']);
$input = json_decode(file_get_contents('php://input'), true);

$room_id = $input['room_id'];
$allottee_type = $input['allottee_type'];
$allottee_id = $input['allottee_id'];
$notes = $input['notes'] ?? '';

try {
    // Check capacity
    $stmt = $pdo->prepare("SELECT capacity, (SELECT COUNT(*) FROM em_room_allotments WHERE room_id = r.id) as occupants FROM em_rooms r WHERE id = ? AND event_id = ?");
    $stmt->execute([$room_id, $auth['event_id']]);
    $room = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$room || $room['occupants'] >= $room['capacity']) {
        sendResponse(false, "Room is full or not found");
    }
    
    // Check if already alloted
    $stmt = $pdo->prepare("SELECT id FROM em_room_allotments WHERE allottee_type = ? AND allottee_id = ? AND event_id = ?");
    $stmt->execute([$allottee_type, $allottee_id, $auth['event_id']]);
    if ($stmt->fetch()) {
        sendResponse(false, "Person is already allotted a room");
    }

    $stmt = $pdo->prepare("INSERT INTO em_room_allotments (event_id, room_id, allottee_type, allottee_id, notes) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$auth['event_id'], $room_id, $allottee_type, $allottee_id, $notes]);
    sendResponse(true, "Room allotted successfully");
} catch (PDOException $e) {
    sendResponse(false, "Database error: " . $e->getMessage());
}
