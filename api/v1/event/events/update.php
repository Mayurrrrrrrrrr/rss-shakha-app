<?php
require_once __DIR__ . '/../config.php';

$auth = authenticateEventRequest();
requireRole($auth, ['admin']);

$data = json_decode(file_get_contents('php://input'), true);
$event_id = $data['event_id'] ?? null;

if (empty($event_id)) {
    sendResponse(false, "Event ID is required");
}

try {
    $stmt = $pdo->prepare("SELECT id FROM em_events WHERE id = ? AND is_deleted = 0");
    $stmt->execute([$event_id]);
    if (!$stmt->fetch()) {
        sendResponse(false, "Event not found or deleted");
    }

    $updates = [];
    $params = [];
    
    $allowedFields = ['name', 'description', 'venue', 'start_date', 'end_date', 'status'];
    
    foreach ($allowedFields as $field) {
        if (isset($data[$field])) {
            $updates[] = "$field = :$field";
            $params[":$field"] = $data[$field];
        }
    }
    
    if (empty($updates)) {
        sendResponse(false, "No fields to update");
    }
    
    $params[':event_id'] = $event_id;
    $updateSql = implode(", ", $updates);
    
    $stmt = $pdo->prepare("UPDATE em_events SET $updateSql, updated_at = NOW() WHERE id = :event_id");
    $stmt->execute($params);
    
    sendResponse(true, "इवेंट सफलतापूर्वक अपडेट किया गया (Event updated successfully)");
} catch (PDOException $e) {
    sendResponse(false, "Database error: " . $e->getMessage());
}
