<?php
require_once '../../includes/auth.php';
/**
 * Batch Update Gat - बहु-चयन गट अपडेट
 */
require_once '../../config/db.php';
requireLogin();
csrf_verify();

if (isSwayamsevak()) {
    header('Location: ../../pages/swayamsevak_dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../pages/swayamsevaks.php');
    exit;
}

$inputs = getRequestInputs();
$selectedIds = $inputs['selected_ids'] ?? [];
$batchGat = trim($inputs['batch_gat'] ?? '');
$shakhaId = getCurrentShakhaId();

if (empty($selectedIds) || empty($batchGat) || !$shakhaId) {
    header('Location: ../../pages/swayamsevaks.php');
    exit;
}

// Sanitize IDs
$sanitizedIds = array_map('intval', $selectedIds);

if (count($sanitizedIds) > 0) {
    $placeholders = implode(',', array_fill(0, count($sanitizedIds), '?'));
    $sql = "UPDATE swayamsevaks SET gat = ? WHERE id IN ($placeholders) AND shakha_id = ?";
    $params = array_merge([$batchGat], $sanitizedIds, [$shakhaId]);
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
}

header('Location: ../../pages/swayamsevaks.php?msg=saved');
exit;
