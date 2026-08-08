<?php
require_once __DIR__ . '/../config.php';
$auth = authenticateEventRequest();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    sendResponse(false, 'विधि की अनुमति नहीं है');
}

$data = json_decode(file_get_contents("php://input"), true);
$session_id = $data['session_id'] ?? null;
$name = trim($data['name'] ?? '');
$phone = trim($data['phone'] ?? '');
$category = trim($data['category'] ?? '');

if (!$session_id || !$name) {
    http_response_code(400);
    sendResponse(false, "सत्र आईडी और नाम अनिवार्य हैं (Session ID and Name are required)");
}

try {
    $pdo->beginTransaction();

    // 1. Get organizer bhag to assign to the new user
    $stmt = $pdo->prepare("SELECT assigned_bhag FROM em_organizers WHERE id = ?");
    $stmt->execute([$auth['user_id']]);
    $assigned_bhag = $stmt->fetchColumn() ?: '';

    // 2. Insert into participants
    $stmt = $pdo->prepare("
        INSERT INTO em_participants (event_id, name, phone, category, city) 
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$auth['event_id'], $name, $phone, $category, $assigned_bhag]);
    $participant_id = $pdo->lastInsertId();

    // 3. Mark attendance immediately
    $stmt = $pdo->prepare("
        INSERT INTO em_attendance (attendance_session_id, participant_id, is_present, updated_by) 
        VALUES (?, ?, 1, ?)
    ");
    $stmt->execute([$session_id, $participant_id, $auth['user_id']]);

    $pdo->commit();
    sendResponse(true, "स्पॉट एंट्री सफलतापूर्वक की गई (Spot entry successful)", ['participant_id' => $participant_id]);
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Spot Entry Error: " . $e->getMessage());
    http_response_code(500);
    sendResponse(false, "डेटाबेस त्रुटि: " . $e->getMessage());
}
