<?php
require_once __DIR__ . '/../config.php';
$auth = authenticateEventRequest();
requireRole($auth, ['Admin', 'Coordinator']);

try {
    $summary = [];
    
    $stmt = $pdo->prepare("SELECT * FROM em_events WHERE id = ?");
    $stmt->execute([$auth['event_id']]);
    $summary['event_info'] = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->prepare("
        SELECT s.session_date, COUNT(a.participant_id) as present_count 
        FROM em_attendance_sessions s
        LEFT JOIN em_attendance a ON s.id = a.attendance_session_id AND a.is_present = 1
        WHERE s.event_id = ?
        GROUP BY s.session_date ORDER BY s.session_date ASC
    ");
    $stmt->execute([$auth['event_id']]);
    $summary['daily_attendance'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->prepare("
        SELECT status, COUNT(*) as count 
        FROM em_work_assignments WHERE event_id = ? GROUP BY status
    ");
    $stmt->execute([$auth['event_id']]);
    $summary['work_stats'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    sendResponse(true, "Event summary report", $summary);
} catch (PDOException $e) {
    sendResponse(false, "Database error: " . $e->getMessage());
}
