<?php
require_once __DIR__ . '/config/db.php';
try {
    $event_id = 1;
    $attendance_session_id = 1;
    $participant_id = 1;
    $is_present = 1;
    $marked_by = 1;
    
    $stmt = $pdo->prepare("
        INSERT INTO em_participant_attendance (event_id, attendance_session_id, participant_id, is_present, marked_by, marked_at) 
        VALUES (?, ?, ?, ?, ?, NOW()) 
        ON DUPLICATE KEY UPDATE 
        is_present = VALUES(is_present), 
        marked_by = VALUES(marked_by), 
        marked_at = NOW()
    ");
    $stmt->execute([$event_id, $attendance_session_id, $participant_id, $is_present, $marked_by]);
    echo "SUCCESS";
} catch(Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
