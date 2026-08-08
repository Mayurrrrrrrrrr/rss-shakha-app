<?php
require '/var/www/html/sanghasthan/api/v1/event/config.php';

try {
    $session_id = 1;
    $event_id = 1;

    $query = "
        SELECT p.id, p.name, p.phone, p.city, p.vasti, p.organization, p.level_type, p.responsibility, p.sangh_shikshan, p.age_group, p.category, p.bhag,
               COALESCE(a.is_present, 0) as is_present
        FROM em_participants p
        LEFT JOIN em_participant_attendance a ON p.id = a.participant_id AND a.attendance_session_id = :session_id
        WHERE p.event_id = :event_id AND p.is_deleted = 0
    ";
    
    $params = [
        ':session_id' => $session_id,
        ':event_id' => $event_id
    ];

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $list = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Success: " . count($list) . " rows fetched.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
