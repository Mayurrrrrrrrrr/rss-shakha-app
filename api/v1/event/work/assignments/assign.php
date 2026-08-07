<?php
require_once __DIR__ . '/../../config.php';
$auth = authenticateEventRequest();
requireRole($auth, ['Admin', 'Coordinator']);
$input = json_decode(file_get_contents('php://input'), true);

try {
    $stmt = $pdo->prepare("INSERT INTO em_work_assignments (event_id, work_category_id, organizer_id, description, assignment_date, time_slot, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
    $stmt->execute([$auth['event_id'], $input['work_category_id'], $input['organizer_id'], $input['description'] ?? '', $input['assignment_date'] ?? null, $input['time_slot'] ?? '']);
    sendResponse(true, "Work assigned successfully");
} catch (PDOException $e) {
    sendResponse(false, "Database error: " . $e->getMessage());
}
