<?php
require_once __DIR__ . '/../config.php';
$auth = authenticateEventRequest();

$session_id = $_GET['session_id'] ?? null;
if (!$session_id) {
    sendResponse(false, "Session ID is required", null, 400);
}

try {
    $stmt = $pdo->prepare("
        SELECT p.id, p.name, p.phone, p.city, p.vasti, p.organization, p.level_type, p.responsibility, p.sangh_shikshan, p.age_group, p.category, p.bhag,
               COALESCE(a.is_present, 0) as is_present
        FROM em_participants p
        LEFT JOIN em_attendance a ON p.id = a.participant_id AND a.attendance_session_id = ?
        WHERE p.event_id = ? AND p.is_deleted = 0
        ORDER BY p.name ASC
    ");
    $stmt->execute([$session_id, $auth['event_id']]);
    $list = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // cast integers correctly
    foreach ($list as &$item) {
        $item['id'] = (int)$item['id'];
        $item['is_present'] = (int)$item['is_present'];
    }
    
    sendResponse(true, "Participants list fetched successfully", ['participants' => $list]);
} catch (PDOException $e) {
    sendResponse(false, "Database error: " . $e->getMessage());
}
