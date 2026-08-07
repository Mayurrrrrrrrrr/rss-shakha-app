<?php
require_once __DIR__ . '/../../config.php';
$auth = authenticateEventRequest();

$date = $_GET['date'] ?? null;
$organizer_id = $_GET['organizer_id'] ?? null;
$status = $_GET['status'] ?? null;
$category_id = $_GET['category_id'] ?? null;

$query = "
    SELECT a.*, c.name as category_name, o.name as organizer_name 
    FROM em_work_assignments a
    JOIN em_work_categories c ON a.work_category_id = c.id
    JOIN em_organizers o ON a.organizer_id = o.id
    WHERE a.event_id = ?
";
$params = [$auth['event_id']];

if ($date) { $query .= " AND a.assignment_date = ?"; $params[] = $date; }
if ($organizer_id) { $query .= " AND a.organizer_id = ?"; $params[] = $organizer_id; }
if ($status) { $query .= " AND a.status = ?"; $params[] = $status; }
if ($category_id) { $query .= " AND a.work_category_id = ?"; $params[] = $category_id; }

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    sendResponse(true, "Assignments retrieved", $stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (PDOException $e) {
    sendResponse(false, "Database error: " . $e->getMessage());
}
