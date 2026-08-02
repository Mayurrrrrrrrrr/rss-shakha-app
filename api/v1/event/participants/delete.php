<?php
require_once __DIR__ . '/../config.php';

$auth = authenticateEventRequest();
requireRole($auth, ['Admin']);

$data = json_decode(file_get_contents("php://input"), true) ?? $_POST;
$id = isset($data['id']) ? (int)$data['id'] : null;

if (!$id) {
    sendResponse(false, "प्रतिभागी ID आवश्यक है");
}

try {
    $checkStmt = $pdo->prepare("SELECT id FROM em_participants WHERE id = ? AND event_id = ?");
    $checkStmt->execute([$id, $auth['event_id']]);
    if (!$checkStmt->fetch()) {
        sendResponse(false, "प्रतिभागी नहीं मिला या आप उसे हटाने के लिए अधिकृत नहीं हैं");
    }

    $stmt = $pdo->prepare("UPDATE em_participants SET is_deleted = 1, is_active = 0 WHERE id = ? AND event_id = ?");
    $stmt->execute([$id, $auth['event_id']]);
    
    sendResponse(true, "प्रतिभागी को सफलतापूर्वक हटा दिया गया है");
} catch (PDOException $e) {
    sendResponse(false, "डेटाबेस त्रुटि: " . $e->getMessage());
}
