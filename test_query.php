<?php
require_once __DIR__ . '/config/db.php';
try {
    $selected_session_id = 1;
    $event_id = 1;
    
    $query = "
        SELECT p.id, p.name, p.phone, p.city, p.vasti, p.organization, p.level_type, p.responsibility, p.sangh_shikshan, p.age_group, p.email, p.category, p.notes, a.is_present
        FROM em_participants p
        LEFT JOIN em_participant_attendance a ON p.id = a.participant_id AND a.attendance_session_id = :session_id
        WHERE p.event_id = :event_id
    ";
    $params = [':session_id' => $selected_session_id, ':event_id' => $event_id];

    $assigned_bhag = 'Ghatkopar';
    $is_hajiri = true;

    if ($is_hajiri && $assigned_bhag !== '') {
        $query .= " AND (p.bhag = :assigned_bhag OR p.city = :assigned_bhag)";
        $params[':assigned_bhag'] = $assigned_bhag;
    }

    $search = 'savant';
    if ($search !== '') {
        $search_hindi = $search;
        $query .= " AND (p.name LIKE :search1 OR p.name LIKE :search2 OR p.phone LIKE :search3)";
        $params[':search1'] = "%$search%";
        $params[':search2'] = "%$search_hindi%";
        $params[':search3'] = "%$search%";
    }
    
    echo $query;
    print_r($params);

    $partStmt = $pdo->prepare($query);
    $partStmt->execute($params);
    echo "SUCCESS";
} catch(Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
