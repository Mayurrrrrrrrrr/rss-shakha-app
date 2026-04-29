<?php
/**
 * Delete Swayamsevak (soft delete)
 */
require_once '../includes/auth.php';
require_once '../config/db.php';
requireLogin();
csrf_verify();
if (isSwayamsevak()) {
    header('Location: ../pages/swayamsevak_dashboard.php');
    exit;
}

$id = $_GET['id'] ?? null;

if ($id) {
    $shakhaId = getCurrentShakhaId();
    $stmt = $pdo->prepare("UPDATE swayamsevaks SET is_active = 0 WHERE id = ? AND shakha_id = ?");
    $stmt->execute([$id, $shakhaId]);
}

header('Location: ../pages/swayamsevaks.php?msg=deleted');
exit;
