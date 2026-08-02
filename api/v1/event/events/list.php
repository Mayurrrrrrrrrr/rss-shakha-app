<?php
require_once __DIR__ . '/../config.php';

$auth = authenticateEventRequest();

$status = $_GET['status'] ?? null;

$whereClauses = ["is_deleted = 0"];
$params = [];

if ($auth['role'] !== 'admin') {
    $whereClauses[] = "id = :event_id";
    $params[':event_id'] = $auth['event_id'];
}

if ($status) {
    $whereClauses[] = "status = :status";
    $params[':status'] = $status;
}

$whereSql = implode(" AND ", $whereClauses);

try {
    $stmt = $pdo->prepare("SELECT id, name, venue, start_date, end_date, status, created_at FROM em_events WHERE $whereSql ORDER BY start_date DESC");
    $stmt->execute($params);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    sendResponse(true, "Events fetched successfully", $events);
} catch (PDOException $e) {
    sendResponse(false, "Database error: " . $e->getMessage());
}
