<?php
require_once __DIR__ . '/../config.php';
$auth = authenticateEventRequest();

try {
    $stats = [];
    
    // Event info
    $stmt = $pdo->prepare("SELECT * FROM em_events WHERE id = ?");
    $stmt->execute([$auth['event_id']]);
    $stats['event_info'] = $stmt->fetch(PDO::FETCH_ASSOC);
    
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
            SELECT 
                COUNT(*) as present,
                (SELECT COUNT(*) FROM em_participants WHERE event_id = ?) as total
            FROM em_attendance 
            WHERE attendance_session_id = ? AND is_present = 1
        ");
        $stmt->execute([$auth['event_id'], $latest_session]);
        $att = $stmt->fetch(PDO::FETCH_ASSOC);
        $att['percentage'] = $att['total'] > 0 ? round(($att['present'] / $att['total']) * 100, 2) : 0;
        $stats['todays_attendance'] = $att;
    } else {
        $stats['todays_attendance'] = null;
    }
    
    // Food today
    $stmt = $pdo->prepare("
        SELECT m.meal_name, m.expected_count,
        (SELECT COUNT(*) FROM em_food_tracking t WHERE t.meal_id = m.id AND t.status = 'consumed') as consumed_count
        FROM em_meals m WHERE event_id = ? AND meal_date = CURDATE()
    ");
    $stmt->execute([$auth['event_id']]);
    $stats['food_today'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Rooms
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(id) as total_rooms,
            SUM(capacity) as total_capacity,
            (SELECT COUNT(*) FROM em_room_allotments WHERE event_id = ?) as total_occupied
        FROM em_rooms WHERE event_id = ?
    ");
    $stmt->execute([$auth['event_id'], $auth['event_id']]);
    $room_stat = $stmt->fetch(PDO::FETCH_ASSOC);
    $room_stat['available_capacity'] = $room_stat['total_capacity'] - $room_stat['total_occupied'];
    $stats['rooms'] = $room_stat;
    
    // Pending tasks
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM em_work_assignments WHERE event_id = ? AND organizer_id = ? AND status = 'pending'");
    $stmt->execute([$auth['event_id'], $auth['organizer_id']]);
    $stats['my_pending_tasks'] = $stmt->fetchColumn();
    
    // Next activity
    $stmt = $pdo->prepare("
        SELECT * FROM em_schedule 
        WHERE event_id = ? AND (activity_date > CURDATE() OR (activity_date = CURDATE() AND start_time >= CURTIME()))
        ORDER BY activity_date ASC, start_time ASC LIMIT 1
    ");
    $stmt->execute([$auth['event_id']]);
    $stats['next_activity'] = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Spot entries
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM em_participants WHERE event_id = ? AND entry_type = 'spot' AND DATE(created_at) = CURDATE()");
    $stmt->execute([$auth['event_id']]);
    $stats['spot_entries_today'] = $stmt->fetchColumn();
    
    sendResponse(true, "Dashboard stats", $stats);
} catch (PDOException $e) {
    sendResponse(false, "Database error: " . $e->getMessage());
}
