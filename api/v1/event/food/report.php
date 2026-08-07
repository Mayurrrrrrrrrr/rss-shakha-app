<?php
require_once __DIR__ . '/../config.php';
$auth = authenticateEventRequest();
$date = $_GET['date'] ?? null;

$query = "
    SELECT m.meal_name, m.expected_count, m.expected_upcoming,
        (SELECT COUNT(*) FROM em_food_tracking t WHERE t.meal_id = m.id AND t.status = 'opted') as opted_count,
        (SELECT COUNT(*) FROM em_food_tracking t WHERE t.meal_id = m.id AND t.status = 'consumed') as consumed_count,
        (SELECT COUNT(*) FROM em_food_tracking t WHERE t.meal_id = m.id AND t.status = 'skipped') as skipped_count
    FROM em_meals m WHERE m.event_id = ?
";
$params = [$auth['event_id']];
if ($date) {
    $query .= " AND m.meal_date = ?";
    $params[] = $date;
}

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    sendResponse(true, "Food report retrieved", $stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (PDOException $e) {
    sendResponse(false, "Database error: " . $e->getMessage());
}
