<?php
require_once __DIR__ . '/../config.php';

$auth = authenticateEventRequest();
requireRole($auth, ['admin']);

$data = json_decode(file_get_contents('php://input'), true);

$source_event_id = $data['source_event_id'] ?? null;
$new_name = $data['new_name'] ?? '';
$new_start_date = $data['new_start_date'] ?? '';
$new_end_date = $data['new_end_date'] ?? '';
$new_venue = $data['new_venue'] ?? '';

if (empty($source_event_id) || empty($new_name) || empty($new_start_date)) {
    sendResponse(false, "Required fields missing");
}

try {
    $pdo->beginTransaction();
    
    $stmt = $pdo->prepare("SELECT * FROM em_events WHERE id = ? AND is_deleted = 0");
    $stmt->execute([$source_event_id]);
    $source_event = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$source_event) {
        throw new Exception("Source event not found");
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO em_events (name, description, venue, start_date, end_date, status, created_by, created_at)
        VALUES (?, ?, ?, ?, ?, 'draft', ?, NOW())
    ");
    $stmt->execute([
        $new_name, 
        $source_event['description'], 
        $new_venue, 
        $new_start_date, 
        $new_end_date, 
        $auth['organizer_id']
    ]);
    $new_event_id = $pdo->lastInsertId();
    
    // Note: Clone logic for work_categories, rooms, schedule, and organizers 
    // would be implemented here in subsequent update.
    
    $pdo->commit();
    sendResponse(true, "इवेंट सफलतापूर्वक क्लोन किया गया (Event cloned successfully)", ['new_event_id' => $new_event_id]);
} catch (Exception $e) {
    $pdo->rollBack();
    sendResponse(false, "Error: " . $e->getMessage());
}
