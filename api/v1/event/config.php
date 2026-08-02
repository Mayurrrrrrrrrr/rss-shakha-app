<?php
require_once __DIR__ . '/../../../includes/auth.php';
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../../../config/db.php';

function sendResponse($success, $message, $data = null)
{
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

function authenticateEventRequest() {
    global $pdo;
    
    // Call the existing authenticateAPIRequest function
    $payload = authenticateAPIRequest();
    
    if (!$payload) {
        http_response_code(401);
        sendResponse(false, 'अमान्य टोकन');
    }
    
    $user_type = is_array($payload) ? ($payload['user_type'] ?? '') : ($payload->user_type ?? '');
    
    if ($user_type !== 'event_organizer') {
        http_response_code(401);
        sendResponse(false, 'आप आयोजन के आयोजक नहीं हैं');
    }
    
    $user_id = is_array($payload) ? ($payload['user_id'] ?? null) : ($payload->user_id ?? null);
    $event_id = is_array($payload) ? ($payload['shakha_id'] ?? null) : ($payload->shakha_id ?? null);
    
    // Fetch organizer details
    $stmt = $pdo->prepare("SELECT id, name, role FROM em_organizers WHERE id = ?");
    $stmt->execute([$user_id]);
    $organizer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$organizer) {
        http_response_code(401);
        sendResponse(false, 'आयोजक नहीं मिला');
    }
    
    return [
        'organizer_id' => $organizer['id'],
        'event_id' => $event_id,
        'role' => $organizer['role'],
        'name' => $organizer['name']
    ];
}

function requireRole($auth, $roles) {
    if (!is_array($roles)) {
        $roles = [$roles];
    }
    
    if (!in_array($auth['role'], $roles)) {
        http_response_code(403);
        sendResponse(false, 'इस क्रिया के लिए आपके पास अनुमति नहीं है');
    }
}
