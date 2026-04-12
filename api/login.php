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
    sendResponse(true, 'लॉगिन सफल', $userData);
}

sendResponse(false, 'गलत उपयोगकर्ता नाम या पासवर्ड!');
