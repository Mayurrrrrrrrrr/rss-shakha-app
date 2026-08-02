<?php
require_once __DIR__ . '/../config.php';

$auth = authenticateEventRequest();
$event_id = $auth['event_id'];
$role = $_GET['role'] ?? null;

$whereSql = "event_id = :event_id AND is_deleted = 0";
$params = [':event_id' => $event_id];

if ($role) {
    $whereSql .= " AND role = :role";
    $params[':role'] = $role;
}

try {
    $stmt = $pdo->prepare("SELECT id, name, phone, role, is_active FROM em_organizers WHERE $whereSql ORDER BY name ASC");
    $stmt->execute($params);
    $organizers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    sendResponse(true, "Organizers fetched", $organizers);
} catch (PDOException $e) {
    sendResponse(false, "Database error: " . $e->getMessage());
}
