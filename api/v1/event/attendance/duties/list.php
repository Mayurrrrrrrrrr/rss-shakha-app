<?php
require_once __DIR__ . '/../../config.php';
$auth = authenticateEventRequest();
try {
    $stmt = $pdo->prepare("
        SELECT d.*, s.session_name, s.session_date, s.session_time, o.name as organizer_name 
        FROM em_attendance_duties d
        JOIN em_attendance_sessions s ON d.attendance_session_id = s.id
        JOIN em_organizers o ON d.organizer_id = o.id
        WHERE d.event_id = ?
    ");
    $stmt->execute([$auth['event_id']]);
    sendResponse(true, "Duties retrieved successfully", $stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (PDOException $e) {
    sendResponse(false, "Database error: " . $e->getMessage());
}
