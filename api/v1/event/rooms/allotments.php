<?php
require_once __DIR__ . '/../config.php';
$auth = authenticateEventRequest();
$room_id = $_GET['room_id'] ?? null;

$query = "SELECT a.*, r.room_name, r.building, r.floor,
    CASE WHEN a.allottee_type = 'participant' THEN p.name ELSE o.name END as allottee_name
    FROM em_room_allotments a
    JOIN em_rooms r ON a.room_id = r.id
    LEFT JOIN em_participants p ON a.allottee_type = 'participant' AND a.allottee_id = p.id
    LEFT JOIN em_organizers o ON a.allottee_type = 'organizer' AND a.allottee_id = o.id
    WHERE a.event_id = ?";
$params = [$auth['event_id']];

if ($room_id) {
    $query .= " AND a.room_id = ?";
    $params[] = $room_id;
}

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    sendResponse(true, "Allotments retrieved", $stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (PDOException $e) {
    sendResponse(false, "Database error: " . $e->getMessage());
}
