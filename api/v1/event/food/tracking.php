<?php
require_once __DIR__ . '/../config.php';
$auth = authenticateEventRequest();
requireRole($auth, ['Admin', 'Coordinator']);
$input = json_decode(file_get_contents('php://input'), true);

try {
    $stmt = $pdo->prepare("
        INSERT INTO em_food_tracking (event_id, meal_id, person_type, person_id, status)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE status = VALUES(status)
    ");
    $stmt->execute([$auth['event_id'], $input['meal_id'], $input['person_type'], $input['person_id'], $input['status']]);
    sendResponse(true, "Tracking updated");
} catch (PDOException $e) {
    sendResponse(false, "Database error: " . $e->getMessage());
}
