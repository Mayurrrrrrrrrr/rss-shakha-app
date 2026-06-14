<?php
require_once '../../includes/auth.php';
require_once '../../config/db.php';
requireLogin();
csrf_verify();

header('Content-Type: application/json; charset=UTF-8');

if (!isMukhyashikshak() && !isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$shakhaIdRaw = getCurrentShakhaId();
$shakhaId = filter_var($shakhaIdRaw, FILTER_VALIDATE_INT);
if ($shakhaId === false) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid Shakha ID.']);
    exit;
}

$orderIdsRaw = $_POST['order_ids'] ?? '';
$orderIds = json_decode($orderIdsRaw, true);

if (!is_array($orderIds)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid order data provided.']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // We update the sort_order of each activity in the list sequentially.
    // To ensure that sort orders are clean, we use 10, 20, 30, etc.
    $index = 1;
    $stmt = $pdo->prepare("UPDATE activities SET sort_order = ?, updated_at = NOW() WHERE id = ? AND (shakha_id IS NULL OR shakha_id = ?)");
    
    foreach ($orderIds as $id) {
        $activityId = filter_var($id, FILTER_VALIDATE_INT);
        if ($activityId === false) {
            throw new Exception("Invalid activity ID.");
        }
        $sortOrder = $index * 10;
        $stmt->execute([$sortOrder, $activityId, $shakhaId]);
        $index++;
    }
    
    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Activities reordered successfully.']);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
