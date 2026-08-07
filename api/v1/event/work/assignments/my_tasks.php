<?php
require_once __DIR__ . '/../../config.php';
$auth = authenticateEventRequest();

$date = $_GET['date'] ?? null;
$status = $_GET['status'] ?? null;

$query = "
    SELECT a.*, c.name as category_name
    FROM em_work_assignments a
    JOIN em_work_categories c ON a.work_category_id = c.id
    WHERE a.event_id = ? AND a.organizer_id = ?
";
$params = [$auth['event_id'], $auth['organizer_id']];

if ($date) { $query .= " AND a.assignment_date = ?"; $params[] = $date; }
if ($status) { $query .= " AND a.status = ?"; $params[] = $status; }

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    sendResponse(true, "My tasks retrieved", $stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (PDOException $e) {
    sendResponse(false, "Database error: " . $e->getMessage());
}
