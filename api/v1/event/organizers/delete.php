<?php
require_once __DIR__ . '/../config.php';

$auth = authenticateEventRequest();
requireRole($auth, ['admin']);
$event_id = $auth['event_id'];

$data = json_decode(file_get_contents('php://input'), true);
$id = $data['id'] ?? null;

if (empty($id)) {
    sendResponse(false, "Organizer ID is required");
}

if ($id == $auth['organizer_id']) {
    sendResponse(false, "आप स्वयं को नहीं हटा सकते (Cannot delete yourself)");
}

try {
    $stmt = $pdo->prepare("UPDATE em_organizers SET is_deleted = 1, is_active = 0 WHERE id = ? AND event_id = ?");
    $stmt->execute([$id, $event_id]);
    
    sendResponse(true, "आयोजक हटा दिया गया (Organizer deleted successfully)");
} catch (PDOException $e) {
    sendResponse(false, "Database error: " . $e->getMessage());
}
