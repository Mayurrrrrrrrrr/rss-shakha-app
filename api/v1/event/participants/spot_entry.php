<?php
require_once __DIR__ . '/../config.php';

$auth = authenticateEventRequest();
requireRole($auth, ['Admin', 'Coordinator']);

$data = json_decode(file_get_contents("php://input"), true) ?? $_POST;

$name = isset($data['name']) ? trim($data['name']) : null;
$phone = isset($data['phone']) ? trim($data['phone']) : null;
$city = isset($data['city']) ? trim($data['city']) : null;
$address = isset($data['address']) ? trim($data['address']) : null;
$age = isset($data['age']) && $data['age'] !== '' ? (int)$data['age'] : null;
$gender = isset($data['gender']) ? trim($data['gender']) : null;
$category = isset($data['category']) ? trim($data['category']) : null;
$group_number = isset($data['group_number']) ? trim($data['group_number']) : null;
$notes = isset($data['notes']) ? trim($data['notes']) : null;

if (empty($name)) {
    sendResponse(false, "प्रतिभागी का नाम आवश्यक है");
}

try {
    $stmt = $pdo->prepare("INSERT INTO em_participants 
        (event_id, name, phone, city, address, age, gender, category, group_number, notes, entry_type, registered_by) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'spot', ?)");
    $stmt->execute([$auth['event_id'], $name, $phone, $city, $address, $age, $gender, $category, $group_number, $notes, $auth['organizer_id']]);
    
    $id = $pdo->lastInsertId();
    
    $getStmt = $pdo->prepare("SELECT * FROM em_participants WHERE id = ?");
    $getStmt->execute([$id]);
    $participant = $getStmt->fetch(PDO::FETCH_ASSOC);
    
    sendResponse(true, "स्पॉट एंट्री सफलतापूर्वक दर्ज की गई", $participant);
} catch (PDOException $e) {
    sendResponse(false, "डेटाबेस त्रुटि: " . $e->getMessage());
}
