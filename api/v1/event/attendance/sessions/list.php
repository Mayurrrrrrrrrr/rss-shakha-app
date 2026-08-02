<?php
require_once __DIR__ . '/../../../config.php';
$auth = authenticateEventRequest();
try {
    $stmt = $pdo->prepare("SELECT * FROM em_attendance_sessions WHERE event_id = ? ORDER BY session_date ASC, session_time ASC");
    $stmt->execute([$auth['event_id']]);
    sendResponse(true, "Sessions retrieved successfully", $stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (PDOException $e) {
    sendResponse(false, "Database error: " . $e->getMessage());
}
