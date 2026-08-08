<?php
require_once __DIR__ . '/../config.php';
$auth = authenticateEventRequest();

$input = json_decode(file_get_contents('php://input'), true);
$id = $input['id'] ?? null;

if (!$id) {
    sendResponse(false, "Schedule Item ID is required", null, 400);
}

try {
    $stmt = $pdo->prepare("DELETE FROM em_schedule WHERE id = ? AND event_id = ?");
    $stmt->execute([$id, $auth['event_id']]);
    
    if ($stmt->rowCount() > 0) {
        sendResponse(true, "Schedule item deleted successfully");
    } else {
        sendResponse(false, "Schedule item not found or unauthorized", null, 404);
    }
} catch (PDOException $e) {
    sendResponse(false, "Database error: " . $e->getMessage());
}
