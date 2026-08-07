<?php
require_once __DIR__ . '/../config.php';
$auth = authenticateEventRequest();
requireRole($auth, ['Admin']);
$input = json_decode(file_get_contents('php://input'), true);

$id = $input['id'] ?? null;
$room_name = $input['room_name'] ?? '';
$room_type = $input['room_type'] ?? '';
$capacity = $input['capacity'] ?? 0;
$floor = $input['floor'] ?? '';
$building = $input['building'] ?? '';
$notes = $input['notes'] ?? '';

try {
    if ($id) {
        $stmt = $pdo->prepare("UPDATE em_rooms SET room_name=?, room_type=?, capacity=?, floor=?, building=?, notes=? WHERE id=? AND event_id=?");
        $stmt->execute([$room_name, $room_type, $capacity, $floor, $building, $notes, $id, $auth['event_id']]);
        sendResponse(true, "Room updated");
    } else {
        $stmt = $pdo->prepare("INSERT INTO em_rooms (event_id, room_name, room_type, capacity, floor, building, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$auth['event_id'], $room_name, $room_type, $capacity, $floor, $building, $notes]);
        sendResponse(true, "Room added");
    }
} catch (PDOException $e) {
    sendResponse(false, "Database error: " . $e->getMessage());
}
