<?php
require_once __DIR__ . '/../../config.php';
$auth = authenticateEventRequest();
$input = json_decode(file_get_contents('php://input'), true);
$id = $input['id'];
$status = $input['status'];

try {
    if (in_array($auth['role'], ['Admin', 'Coordinator'])) {
        $stmt = $pdo->prepare("UPDATE em_work_assignments SET status = ? WHERE id = ? AND event_id = ?");
        $stmt->execute([$status, $id, $auth['event_id']]);
    } else {
        $stmt = $pdo->prepare("UPDATE em_work_assignments SET status = ? WHERE id = ? AND event_id = ? AND organizer_id = ?");
        $stmt->execute([$status, $id, $auth['event_id'], $auth['organizer_id']]);
    }
    
    if ($stmt->rowCount() > 0) {
        sendResponse(true, "Status updated successfully");
    } else {
        sendResponse(false, "Update failed or unauthorized");
    }
} catch (PDOException $e) {
    sendResponse(false, "Database error: " . $e->getMessage());
}
