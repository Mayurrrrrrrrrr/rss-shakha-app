<?php
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    sendResponse(false, 'विधि की अनुमति नहीं है');
}

$auth = authenticateEventRequest();

// Fetch organizer profile
$stmt = $pdo->prepare("SELECT id, name, username, role, phone, email, status FROM em_organizers WHERE id = ?");
$stmt->execute([$auth['organizer_id']]);
$organizer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$organizer) {
    http_response_code(404);
    sendResponse(false, 'आयोजक नहीं मिला');
}

// Fetch event details
$stmt = $pdo->prepare("SELECT id, name, description, start_date, end_date, location, status FROM em_events WHERE id = ?");
$stmt->execute([$auth['event_id']]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

sendResponse(true, 'प्रोफ़ाइल विवरण', [
    'organizer' => $organizer,
    'event' => $event
]);
