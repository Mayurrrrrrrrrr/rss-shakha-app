<?php
require_once __DIR__ . '/../config.php';

$auth = authenticateEventRequest();
requireRole($auth, ['admin']);

$data = json_decode(file_get_contents('php://input'), true);

$name = $data['name'] ?? '';
$description = $data['description'] ?? '';
$venue = $data['venue'] ?? '';
$start_date = $data['start_date'] ?? '';
$end_date = $data['end_date'] ?? '';

if (empty($name) || empty($start_date)) {
    sendResponse(false, "नाम और प्रारंभ तिथि आवश्यक हैं (Name and start date are required)");
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO em_events (name, description, venue, start_date, end_date, status, created_by, created_at)
        VALUES (:name, :description, :venue, :start_date, :end_date, 'draft', :created_by, NOW())
    ");
    
    $stmt->execute([
        ':name' => $name,
        ':description' => $description,
        ':venue' => $venue,
        ':start_date' => $start_date,
        ':end_date' => $end_date,
        ':created_by' => $auth['organizer_id']
    ]);
    
    $eventId = $pdo->lastInsertId();
    
    $stmt = $pdo->prepare("SELECT * FROM em_events WHERE id = ?");
    $stmt->execute([$eventId]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);
    
    sendResponse(true, "इवेंट सफलतापूर्वक बनाया गया (Event created successfully)", $event);
} catch (PDOException $e) {
    sendResponse(false, "Database error: " . $e->getMessage());
}
