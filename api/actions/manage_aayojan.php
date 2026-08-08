<?php
require_once '../../includes/auth.php';
require_once '../../config/db.php';
requireLogin();

header('Content-Type: application/json');

if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? '';

try {
    if ($action === 'add_event') {
        $name = trim($_POST['name'] ?? '');
        $venue = trim($_POST['venue'] ?? '');
        $start_date = $_POST['start_date'] ?? null;
        $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
        $status = $_POST['status'] ?? 'active';

        if (empty($name) || empty($start_date)) {
            echo json_encode(['success' => false, 'message' => 'Name and Start Date are required']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO em_events (name, venue, start_date, end_date, status, created_by) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $venue, $start_date, $end_date, $status, $_SESSION['user_id']]);
        
        echo json_encode(['success' => true]);
        
    } elseif ($action === 'add_organizer') {
        $event_id = (int)($_POST['event_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'volunteer';

        if (!$event_id || empty($name) || empty($username) || empty($password)) {
            echo json_encode(['success' => false, 'message' => 'Required fields missing']);
            exit;
        }

        // Check if username already exists for this event
        $stmt = $pdo->prepare("SELECT id FROM em_organizers WHERE event_id = ? AND username = ?");
        $stmt->execute([$event_id, $username]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Username already exists for this event']);
            exit;
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO em_organizers (event_id, name, phone, username, password, role) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$event_id, $name, $phone, $username, $hashedPassword, $role]);
        
        echo json_encode(['success' => true]);
        
    } elseif ($action === 'delete_event') {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Invalid ID']);
            exit;
        }
        
        // Soft delete the event
        $stmt = $pdo->prepare("UPDATE em_events SET is_deleted = 1, status = 'archived' WHERE id = ?");
        $stmt->execute([$id]);
        
        // Also soft delete associated organizers
        $stmt = $pdo->prepare("UPDATE em_organizers SET is_deleted = 1, is_active = 0 WHERE event_id = ?");
        $stmt->execute([$id]);
        
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    error_log("Error in manage_aayojan.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
