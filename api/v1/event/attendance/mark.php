<?php
require_once __DIR__ . '/../config.php';
$auth = authenticateEventRequest();
$input = json_decode(file_get_contents('php://input'), true);
$attendance_session_id = $input['attendance_session_id'];
$attendances = $input['attendances'] ?? [];

try {
    $pdo->beginTransaction();
    
    $stmt = $pdo->prepare("
        INSERT INTO em_attendance (event_id, attendance_session_id, participant_id, is_present, marked_by, marked_at) 
        VALUES (?, ?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE is_present = VALUES(is_present), marked_by = VALUES(marked_by), marked_at = VALUES(marked_at)
    ");
    
    foreach ($attendances as $att) {
        $stmt->execute([$auth['event_id'], $attendance_session_id, $att['participant_id'], $att['is_present'], $auth['organizer_id']]);
    }
    
    $pdo->commit();
    sendResponse(true, "Attendance marked successfully");
} catch (PDOException $e) {
    $pdo->rollBack();
    sendResponse(false, "Database error: " . $e->getMessage());
}
