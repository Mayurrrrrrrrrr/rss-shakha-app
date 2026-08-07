<?php
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    sendResponse(false, 'विधि की अनुमति नहीं है');
}

$data = json_decode(file_get_contents("php://input"), true);
$username = trim($data['username'] ?? '');
$password = $data['password'] ?? '';
$event_id = $data['event_id'] ?? null;

if (empty($username) || empty($password)) {
    http_response_code(400);
    sendResponse(false, 'उपयोगकर्ता नाम और पासवर्ड आवश्यक हैं');
}

$ip_address = $_SERVER['REMOTE_ADDR'];

try {
    // Rate limiting check — columns match migration: ip, attempted_at
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM em_login_attempts WHERE ip = ? AND attempted_at > (NOW() - INTERVAL 15 MINUTE)");
    $stmt->execute([$ip_address]);
    $attempts = $stmt->fetchColumn();
} catch (PDOException $e) {
    // Table might not exist yet — create it
    $pdo->exec("CREATE TABLE IF NOT EXISTS em_login_attempts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ip VARCHAR(45) NOT NULL,
        attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_ip_attempted_at (ip, attempted_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $attempts = 0;
}

if ($attempts >= 5) {
    http_response_code(429);
    sendResponse(false, 'बहुत अधिक लॉगिन प्रयास। कृपया 15 मिनट बाद पुन: प्रयास करें।');
}

function logAttempt($pdo, $ip) {
    try {
        $stmt = $pdo->prepare("INSERT INTO em_login_attempts (ip, attempted_at) VALUES (?, NOW())");
        $stmt->execute([$ip]);
    } catch (PDOException $e) {
        error_log('Failed to log login attempt: ' . $e->getMessage());
    }
}

try {
    // Fetch organizer
    $stmt = $pdo->prepare("SELECT * FROM em_organizers WHERE username = ?");
    $stmt->execute([$username]);
    $organizer = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Event login DB error: ' . $e->getMessage());
    http_response_code(500);
    sendResponse(false, 'सर्वर त्रुटि। कृपया पुनः प्रयास करें।');
}

if (!$organizer || !password_verify($password, $organizer['password'])) {
    logAttempt($pdo, $ip_address);
    http_response_code(401);
    sendResponse(false, 'अमान्य उपयोगकर्ता नाम या पासवर्ड');
}

if ((int)($organizer['is_active'] ?? 1) !== 1) {
    http_response_code(403);
    sendResponse(false, 'आपका खाता निष्क्रिय है');
}

$selected_event_id = null;
$selected_event_name = '';

// Check event association
if ($event_id) {
    // Check if organizer belongs to this event
    $stmt = $pdo->prepare("
        SELECT e.id, e.name 
        FROM em_events e 
        WHERE e.id = ? AND e.id = ? AND e.status = 'active'
    ");
    // $organizer['event_id'] holds the event they belong to
    $stmt->execute([$organizer['event_id'], $event_id]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$event) {
        http_response_code(403);
        sendResponse(false, 'आप इस आयोजन के आयोजक नहीं हैं या आयोजन सक्रिय नहीं है');
    }
    
    $selected_event_id = $event['id'];
    $selected_event_name = $event['name'];
} else {
    // Find the event the organizer belongs to
    $stmt = $pdo->prepare("
        SELECT e.id, e.name 
        FROM em_events e 
        WHERE e.id = ? AND e.status = 'active' 
    ");
    $stmt->execute([$organizer['event_id']]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$event) {
        http_response_code(403);
        sendResponse(false, 'आप किसी भी सक्रिय आयोजन से जुड़े नहीं हैं');
    }
    
    $selected_event_id = $event['id'];
    $selected_event_name = $event['name'];
}

// Clear login attempts
try {
    $stmt = $pdo->prepare("DELETE FROM em_login_attempts WHERE ip = ?");
    $stmt->execute([$ip_address]);
} catch (PDOException $e) {
    // Non-critical, just log
    error_log('Failed to clear login attempts: ' . $e->getMessage());
}

// Generate token (using event_id as shakha_id for event organizers)
$token = generateAPIToken($organizer['id'], 'event_organizer', $selected_event_id);

sendResponse(true, 'लॉगिन सफल', [
    'organizer_id' => $organizer['id'],
    'name' => $organizer['name'],
    'role' => $organizer['role'],
    'event_id' => $selected_event_id,
    'event_name' => $selected_event_name,
    'token' => $token
]);

