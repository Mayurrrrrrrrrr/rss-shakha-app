<?php
require_once __DIR__ . '/../config.php';
$auth = authenticateEventRequest();
$session_id = $_GET['session_id'] ?? null;
$date = $_GET['date'] ?? null;

try {
    $query = "
        SELECT p.`group` as participant_group, COUNT(p.id) as total_participants, 
               SUM(CASE WHEN a.is_present = 1 THEN 1 ELSE 0 END) as present_count,
               SUM(CASE WHEN a.is_present = 0 OR a.is_present IS NULL THEN 1 ELSE 0 END) as absent_count
        FROM em_participants p
        LEFT JOIN em_participant_attendance a ON p.id = a.participant_id AND a.attendance_session_id = ?
        WHERE p.event_id = ?
        GROUP BY p.`group`
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$session_id, $auth['event_id']]);
    $group_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $total = 0; $present = 0; $absent = 0;
    foreach($group_stats as $stat) {
        $total += $stat['total_participants'];
        $present += $stat['present_count'];
        $absent += $stat['absent_count'];
    }
    
    sendResponse(true, "Attendance report", [
        'total_participants' => $total,
        'present_count' => $present,
        'absent_count' => $absent,
        'percentage' => $total > 0 ? round(($present / $total) * 100, 2) : 0,
        'per_group' => $group_stats
    ]);
} catch (PDOException $e) {
    sendResponse(false, "Database error: " . $e->getMessage());
}
