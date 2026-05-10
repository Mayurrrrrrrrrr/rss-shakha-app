<?php
require_once '../../includes/auth.php';
require_once '../../config/db.php';
requireLogin();
csrf_verify();
if (!isAdmin()) {
    header('Location: ../../pages/index.php');
    exit;
}

$id = $_GET['id'] ?? null;

if ($id) {
    try {
        // Warning: This could delete a lot of associated data depending on ON DELETE actions.
        // It relies on the ON DELETE CASCADE / SET NULL from migration script.
        $stmt = $pdo->prepare("DELETE FROM shakhas WHERE id = ?");
        $stmt->execute([$id]);
        header('Location: ../../pages/shakhas.php?msg=deleted');
    } catch (PDOException $e) {
        header('Location: ../../pages/shakhas.php?msg=error');
    }
} else {
    header('Location: ../../pages/shakhas.php');
}
