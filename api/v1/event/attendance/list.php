<?php
require_once __DIR__ . '/../config.php';
$auth = authenticateEventRequest();

$session_id = $_GET['session_id'] ?? null;
if (!$session_id) {
    sendResponse(false, "Session ID is required", null, 400);
}

$search = trim($_GET['search'] ?? '');

try {
    // Get organizer details for role and bhag
    $stmt = $pdo->prepare("SELECT role, assigned_bhag FROM em_organizers WHERE id = ?");
    $stmt->execute([$auth['user_id']]);
    $organizer = $stmt->fetch(PDO::FETCH_ASSOC);

    $is_volunteer = ($organizer['role'] === 'volunteer');
    $assigned_bhag = $organizer['assigned_bhag'] ?? null;

    if ($is_volunteer && empty($search)) {
        // Volunteer must search to see users, no default list
        sendResponse(true, "Participants list fetched successfully", ['participants' => []]);
        exit;
    }

    $query = "
        SELECT p.id, p.name, p.phone, p.city, p.vasti, p.organization, p.level_type, p.responsibility, p.sangh_shikshan, p.age_group, p.category, p.bhag,
               COALESCE(a.is_present, 0) as is_present
        FROM em_participants p
        LEFT JOIN em_attendance a ON p.id = a.participant_id AND a.attendance_session_id = :session_id
        WHERE p.event_id = :event_id AND p.is_deleted = 0
    ";
    
    $params = [
        ':session_id' => $session_id,
        ':event_id' => $auth['event_id']
    ];

    if ($is_volunteer && $assigned_bhag) {
        $query .= " AND (p.city = :bhag OR p.bhag = :bhag)";
        $params[':bhag'] = $assigned_bhag;
    }

    if (!empty($search)) {
        $query .= " AND (p.name LIKE :search OR p.phone LIKE :search)";
        $params[':search'] = '%' . $search . '%';
    }

    $query .= " ORDER BY p.name ASC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
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
