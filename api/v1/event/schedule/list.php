<?php
require_once __DIR__ . '/../config.php';
$auth = authenticateEventRequest();
$date = $_GET['date'] ?? null;

$query = "SELECT s.*, o.name as organizer_name 
          FROM em_schedule s 
          LEFT JOIN em_organizers o ON s.responsible_organizer_id = o.id 
          WHERE s.event_id = ?";
$params = [$auth['event_id']];
if ($date) {
    $query .= " AND s.activity_date = ?";
    $params[] = $date;
}
$query .= " ORDER BY s.activity_date ASC, s.start_time ASC, s.sort_order ASC";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    sendResponse(true, "Schedule retrieved", $stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (PDOException $e) {
    sendResponse(false, "Database error: " . $e->getMessage());
}
