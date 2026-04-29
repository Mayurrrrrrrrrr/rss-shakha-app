<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, 'Invalid request method');
}

$data = json_decode(file_get_contents("php://input"));
$username = trim($data->username ?? '');
$password = $data->password ?? '';

if (empty($username) || empty($password)) {
    sendResponse(false, 'कृपया उपयोगकर्ता नाम और पासवर्ड दोनों भरें।');
}

$ip = $_SERVER['REMOTE_ADDR'];

// Rate Limiting Check
$stmt = $pdo->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND attempted_at > (NOW() - INTERVAL 15 MINUTE)");
$stmt->execute([$ip]);
$attempts = $stmt->fetchColumn();

if ($attempts >= 5) {
    sendResponse(false, 'बहुत सारे असफल प्रयास। 15 मिनट बाद पुनः प्रयास करें।');
}

// Check admin_users
$stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch();

if ($user && password_verify($password, $user['password'])) {
    $userData = [
        'id' => $user['id'],
        'name' => $user['name'],
        'role' => $user['role'] ?? 'mukhyashikshak',
        'shakha_id' => $user['shakha_id'],
        'type' => 'admin_user'
    ];
    // In a real app we'd generate a JWT token here, for this simple demo we will return the user data
    // Clear attempts on success
    $stmt = $pdo->prepare("DELETE FROM login_attempts WHERE ip = ?");
    $stmt->execute([$ip]);

    sendResponse(true, 'लॉगिन सफल', $userData);
}

// Check swayamsevaks
$stmt = $pdo->prepare("SELECT * FROM swayamsevaks WHERE username = ? AND is_active = 1");
$stmt->execute([$username]);
$user = $stmt->fetch();

if ($user && $user['password'] && password_verify($password, $user['password'])) {
    $userData = [
        'id' => $user['id'],
        'name' => $user['name'],
        'role' => 'swayamsevak',
        'shakha_id' => $user['shakha_id'],
        'type' => 'swayamsevak'
    ];
    // Clear attempts on success
    $stmt = $pdo->prepare("DELETE FROM login_attempts WHERE ip = ?");
    $stmt->execute([$ip]);

    sendResponse(true, 'लॉगिन सफल', $userData);
}

// Failed login attempt
$stmt = $pdo->prepare("INSERT INTO login_attempts (ip) VALUES (?)");
$stmt->execute([$ip]);
sleep(1);

sendResponse(false, 'गलत उपयोगकर्ता नाम या पासवर्ड!');
