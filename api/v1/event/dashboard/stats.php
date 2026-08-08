<?php
require_once __DIR__ . '/../config.php';
$auth = authenticateEventRequest();

try {
    $stats = [];
    
    // Event info
    $stmt = $pdo->prepare("SELECT * FROM em_events WHERE id = ?");
    $stmt->execute([$auth['event_id']]);
    $stats['event_info'] = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    
    // Participants
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM em_participants WHERE event_id = ? AND is_active = 1 AND is_deleted = 0");
    $stmt->execute([$auth['event_id']]);
    $stats['participant_count'] = $stmt->fetchColumn();
    
    // Organizers
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM em_organizers WHERE event_id = ? AND is_active = 1 AND is_deleted = 0");
    $stmt->execute([$auth['event_id']]);
    $stats['organizer_count'] = $stmt->fetchColumn();
    
    // Today's attendance (latest session)
    $stmt = $pdo->prepare("
        SELECT id FROM em_attendance_sessions 
        WHERE event_id = ? AND session_date = CURDATE() 
        ORDER BY session_time DESC LIMIT 1
    ");
    $stmt->execute([$auth['event_id']]);
    $latest_session = $stmt->fetchColumn();
    
    if ($latest_session) {
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT participant_id) as present,
            (SELECT COUNT(*) FROM em_participants WHERE event_id = ?) as total
            FROM em_participant_attendance 
            WHERE event_id = ? 
            AND attendance_session_id = ? 
            AND is_present = 1
        ");
        $stmt->execute([$auth['event_id'], $auth['event_id'], $latest_session]);
        $att = $stmt->fetch(PDO::FETCH_ASSOC);
        $att['percentage'] = $att['total'] > 0 ? round(($att['present'] / $att['total']) * 100, 2) : 0;
        $stats['today_attendance'] = $att;
    } else {
        $stats['today_attendance'] = null;
    }
    

    
    // Spot entries
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM em_participants WHERE event_id = ? AND entry_type = 'spot' AND DATE(created_at) = CURDATE()");
    $stmt->execute([$auth['event_id']]);
    $stats['spot_entries_today'] = $stmt->fetchColumn();
    
    sendResponse(true, "Dashboard stats", $stats);
} catch (PDOException $e) {
    sendResponse(false, "Database error: " . $e->getMessage());
}
