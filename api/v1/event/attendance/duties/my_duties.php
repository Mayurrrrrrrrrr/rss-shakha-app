<?php
require_once __DIR__ . '/../../config.php';
$auth = authenticateEventRequest();
try {
    $stmt = $pdo->prepare("
        SELECT d.*, s.session_name, s.session_date, s.session_time, 
        (SELECT COUNT(*) FROM em_participants WHERE `group` = d.participant_group AND event_id = ?) as participant_count
        FROM em_attendance_duties d
        JOIN em_attendance_sessions s ON d.attendance_session_id = s.id
        WHERE d.event_id = ? AND d.organizer_id = ?
    ");
    $stmt->execute([$auth['event_id'], $auth['event_id'], $auth['organizer_id']]);
    sendResponse(true, "My duties retrieved", $stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (PDOException $e) {
    sendResponse(false, "Database error: " . $e->getMessage());
}
