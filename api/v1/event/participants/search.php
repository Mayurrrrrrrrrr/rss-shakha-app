<?php
require_once __DIR__ . '/../config.php';

$auth = authenticateEventRequest();

$q = isset($_GET['q']) ? trim($_GET['q']) : null;

if (empty($q) || mb_strlen($q) < 2) {
    sendResponse(false, "खोज के लिए कम से कम 2 अक्षर आवश्यक हैं");
}

try {
    $query = "SELECT p.id, p.name, p.phone, p.city, p.group_number, p.category, r.room_number as room
              FROM em_participants p
              LEFT JOIN em_room_allotments ra ON p.id = ra.participant_id
              LEFT JOIN em_rooms r ON ra.room_id = r.id
              WHERE p.event_id = :event_id AND p.is_deleted = 0";
              
    // Using LIKE for simplicity and compatibility across DB setups without FULLTEXT index
    $query .= " AND (p.name LIKE :search OR p.phone LIKE :search OR p.city LIKE :search)
                ORDER BY p.name ASC LIMIT 20";
                
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':event_id', $auth['event_id'], PDO::PARAM_INT);
    $stmt->bindValue(':search', '%' . $q . '%', PDO::PARAM_STR);
    $stmt->execute();
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    sendResponse(true, "खोज परिणाम", $results);
} catch (PDOException $e) {
    sendResponse(false, "डेटाबेस त्रुटि: " . $e->getMessage());
}
