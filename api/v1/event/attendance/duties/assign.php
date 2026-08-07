<?php
require_once __DIR__ . '/../../config.php';
$auth = authenticateEventRequest();
requireRole($auth, ['Admin', 'Coordinator']);
$input = json_decode(file_get_contents('php://input'), true);

try {
    $stmt = $pdo->prepare("INSERT INTO em_attendance_duties (event_id, attendance_session_id, organizer_id, participant_group) VALUES (?, ?, ?, ?)");
    $stmt->execute([$auth['event_id'], $input['attendance_session_id'], $input['organizer_id'], $input['participant_group']]);
    sendResponse(true, "Duty assigned successfully");
} catch (PDOException $e) {
    sendResponse(false, "Database error: " . $e->getMessage());
}
