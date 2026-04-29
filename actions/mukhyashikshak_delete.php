<?php
require_once '../includes/auth.php';
require_once '../config/db.php';
requireLogin();
csrf_verify();
if (!isAdmin()) {
    header('Location: ../pages/index.php');
    exit;
}

$id = $_GET['id'] ?? null;

if ($id) {
    try {
        // Make sure we never delete the super admin unintentionally.
        $stmt = $pdo->prepare("DELETE FROM admin_users WHERE id = ? AND role = 'mukhyashikshak'");
        $stmt->execute([$id]);
        header('Location: ../pages/mukhyashikshaks.php?msg=deleted');
    } catch (PDOException $e) {
        header('Location: ../pages/mukhyashikshaks.php?msg=error');
    }
} else {
    header('Location: ../pages/mukhyashikshaks.php');
}
