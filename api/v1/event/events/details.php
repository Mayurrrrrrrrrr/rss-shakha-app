<?php
require_once __DIR__ . '/../config.php';

$auth = authenticateEventRequest();

$event_id = $_GET['event_id'] ?? $auth['event_id'];

if ($auth['role'] !== 'admin' && $event_id != $auth['event_id']) {
    sendResponse(false, "Unauthorized access to this event");
}

try {
    $stmt = $pdo->prepare("SELECT * FROM em_events WHERE id = ? AND is_deleted = 0");
    $stmt->execute([$event_id]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$event) {
        sendResponse(false, "Event not found");
    }
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM em_participants WHERE event_id = ? AND is_deleted = 0");
    $stmt->execute([$event_id]);
    $participants_count = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM em_organizers WHERE event_id = ? AND is_deleted = 0");
    $stmt->execute([$event_id]);
    $organizers_count = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM em_rooms WHERE event_id = ? AND is_deleted = 0");
    $stmt->execute([$event_id]);
    $total_rooms = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT room_id) FROM em_room_allotments WHERE event_id = ? AND is_deleted = 0");
    $stmt->execute([$event_id]);
    $occupied_rooms = $stmt->fetchColumn();
    
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT session_id) FROM em_attendance WHERE event_id = ? AND DATE(date) = ?");
    $stmt->execute([$event_id, $today]);
    $sessions_today = $stmt->fetchColumn();
    
    $attendance_percentage = 0;
    if ($sessions_today > 0 && $participants_count > 0) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM em_attendance WHERE event_id = ? AND DATE(date) = ? AND status = 'present'");
        $stmt->execute([$event_id, $today]);
        $present_count = $stmt->fetchColumn();
        
        $total_expected = $sessions_today * $participants_count;
        $attendance_percentage = round(($present_count / $total_expected) * 100, 2);
    }
    
    $stats = [
        'participants_count' => $participants_count,
        'organizers_count' => $organizers_count,
        'attendance_percentage' => $attendance_percentage,
        'total_rooms' => $total_rooms,
        'occupied_rooms' => $occupied_rooms
    ];
    
    sendResponse(true, "Event details fetched", ['event' => $event, 'stats' => $stats]);
} catch (PDOException $e) {
    sendResponse(false, "Database error: " . $e->getMessage());
}
