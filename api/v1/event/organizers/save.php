<?php
require_once __DIR__ . '/../config.php';

$auth = authenticateEventRequest();
requireRole($auth, ['admin']);
$event_id = $auth['event_id'];

$data = json_decode(file_get_contents('php://input'), true);

$id = $data['id'] ?? null;
$name = $data['name'] ?? '';
$phone = $data['phone'] ?? '';
$username = $data['username'] ?? '';
$password = $data['password'] ?? '';
$role = $data['role'] ?? 'organizer';

if (empty($name) || empty($username)) {
    sendResponse(false, "Name and username are required");
}

try {
    $stmt = $pdo->prepare("SELECT id FROM em_organizers WHERE event_id = ? AND username = ? AND is_deleted = 0 AND id != ?");
    $stmt->execute([$event_id, $username, $id ?: 0]);
    if ($stmt->fetch()) {
        sendResponse(false, "Username already exists in this event");
    }
    
    if ($id) {
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE em_organizers SET name = ?, phone = ?, username = ?, password = ?, role = ? WHERE id = ? AND event_id = ?");
            $stmt->execute([$name, $phone, $username, $hashed_password, $role, $id, $event_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE em_organizers SET name = ?, phone = ?, username = ?, role = ? WHERE id = ? AND event_id = ?");
            $stmt->execute([$name, $phone, $username, $role, $id, $event_id]);
        }
        sendResponse(true, "आयोजक अपडेट किया गया (Organizer updated successfully)");
    } else {
        if (empty($password)) {
            sendResponse(false, "Password is required for new organizer");
        }
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO em_organizers (event_id, name, phone, username, password, role, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$event_id, $name, $phone, $username, $hashed_password, $role]);
        
        sendResponse(true, "आयोजक सफलतापूर्वक जोड़ा गया (Organizer added successfully)");
    }
} catch (PDOException $e) {
    sendResponse(false, "Database error: " . $e->getMessage());
}
