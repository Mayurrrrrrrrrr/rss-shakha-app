<?php
require_once __DIR__ . '/../../config.php';
$auth = authenticateEventRequest();
requireRole($auth, ['Admin', 'Coordinator']);
$input = json_decode(file_get_contents('php://input'), true);

$id = $input['id'] ?? null;
$session_name = $input['session_name'] ?? '';
$session_date = $input['session_date'] ?? '';
$session_time = $input['session_time'] ?? '';
$description = $input['description'] ?? '';

try {
    if ($id) {
        $stmt = $pdo->prepare("UPDATE em_attendance_sessions SET session_name = ?, session_date = ?, session_time = ?, description = ? WHERE id = ? AND event_id = ?");
        $stmt->execute([$session_name, $session_date, $session_time, $description, $id, $auth['event_id']]);
        sendResponse(true, "Session updated successfully");
    } else {
        $stmt = $pdo->prepare("INSERT INTO em_attendance_sessions (event_id, session_name, session_date, session_time, description) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$auth['event_id'], $session_name, $session_date, $session_time, $description]);
        sendResponse(true, "Session created successfully");
    }
} catch (PDOException $e) {
    sendResponse(false, "Database error: " . $e->getMessage());
}
