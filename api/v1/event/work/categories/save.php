<?php
require_once __DIR__ . '/../../../config.php';
$auth = authenticateEventRequest();
requireRole($auth, ['Admin']);
$input = json_decode(file_get_contents('php://input'), true);

$id = $input['id'] ?? null;
$name = $input['name'] ?? '';
$description = $input['description'] ?? '';
$sort_order = $input['sort_order'] ?? 0;

try {
    if ($id) {
        $stmt = $pdo->prepare("UPDATE em_work_categories SET name = ?, description = ?, sort_order = ? WHERE id = ? AND event_id = ?");
        $stmt->execute([$name, $description, $sort_order, $id, $auth['event_id']]);
        sendResponse(true, "Category updated");
    } else {
        $stmt = $pdo->prepare("INSERT INTO em_work_categories (event_id, name, description, sort_order) VALUES (?, ?, ?, ?)");
        $stmt->execute([$auth['event_id'], $name, $description, $sort_order]);
        sendResponse(true, "Category created");
    }
} catch (PDOException $e) {
    sendResponse(false, "Database error: " . $e->getMessage());
}
