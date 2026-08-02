<?php
require_once __DIR__ . '/../../config.php';
$auth = authenticateEventRequest();
requireRole($auth, ['Admin', 'Coordinator']);
$input = json_decode(file_get_contents('php://input'), true);

$id = $input['id'] ?? null;
$activity_name = $input['activity_name'] ?? '';
$activity_date = $input['activity_date'] ?? '';
$start_time = $input['start_time'] ?? '';
$end_time = $input['end_time'] ?? '';
$venue = $input['venue'] ?? '';
$responsible_organizer_id = $input['responsible_organizer_id'] ?? null;
$description = $input['description'] ?? '';
$sort_order = $input['sort_order'] ?? 0;

try {
    if ($id) {
        $stmt = $pdo->prepare("UPDATE em_schedule SET activity_name=?, activity_date=?, start_time=?, end_time=?, venue=?, responsible_organizer_id=?, description=?, sort_order=? WHERE id=? AND event_id=?");
        $stmt->execute([$activity_name, $activity_date, $start_time, $end_time, $venue, $responsible_organizer_id, $description, $sort_order, $id, $auth['event_id']]);
        sendResponse(true, "Schedule updated");
    } else {
        $stmt = $pdo->prepare("INSERT INTO em_schedule (event_id, activity_name, activity_date, start_time, end_time, venue, responsible_organizer_id, description, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$auth['event_id'], $activity_name, $activity_date, $start_time, $end_time, $venue, $responsible_organizer_id, $description, $sort_order]);
        sendResponse(true, "Schedule added");
    }
} catch (PDOException $e) {
    sendResponse(false, "Database error: " . $e->getMessage());
}
