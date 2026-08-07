<?php
require_once __DIR__ . '/../config.php';
$auth = authenticateEventRequest();
requireRole($auth, ['Admin', 'Coordinator']);
$input = json_decode(file_get_contents('php://input'), true);

$id = $input['id'] ?? null;
$meal_name = $input['meal_name'] ?? '';
$meal_date = $input['meal_date'] ?? '';
$meal_time = $input['meal_time'] ?? '';
$expected_count = $input['expected_count'] ?? 0;
$expected_upcoming = $input['expected_upcoming'] ?? 0;
$notes = $input['notes'] ?? '';

try {
    if ($id) {
        $stmt = $pdo->prepare("UPDATE em_meals SET meal_name=?, meal_date=?, meal_time=?, expected_count=?, expected_upcoming=?, notes=? WHERE id=? AND event_id=?");
        $stmt->execute([$meal_name, $meal_date, $meal_time, $expected_count, $expected_upcoming, $notes, $id, $auth['event_id']]);
        sendResponse(true, "Meal updated");
    } else {
        $stmt = $pdo->prepare("INSERT INTO em_meals (event_id, meal_name, meal_date, meal_time, expected_count, expected_upcoming, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$auth['event_id'], $meal_name, $meal_date, $meal_time, $expected_count, $expected_upcoming, $notes]);
        sendResponse(true, "Meal added");
    }
} catch (PDOException $e) {
    sendResponse(false, "Database error: " . $e->getMessage());
}
