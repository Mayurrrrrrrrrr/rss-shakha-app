<?php
require_once '../includes/auth.php';
require_once '../config/db.php';
requireLogin();

if (!isMukhyashikshak() && !isAdmin()) {
    header('Location: ../pages/dashboard.php');
    exit;
}

$shakhaId = getCurrentShakhaId();
$action = $_POST['action'] ?? '';

if ($action === 'add' || $action === 'edit') {
    $name = trim($_POST['name'] ?? '');
    $sortOrder = (int) ($_POST['sort_order'] ?? 10);
    $id = $_POST['id'] ?? null;

    if (empty($name)) {
        header('Location: ../pages/activities.php?error=नाम अनिवार्य है');
        exit;
    }

    if ($action === 'add') {
        $stmt = $pdo->prepare("INSERT INTO activities (name, sort_order, shakha_id, is_active) VALUES (?, ?, ?, 1)");
        $stmt->execute([$name, $sortOrder, $shakhaId]);
        header('Location: ../pages/activities.php?success=गतिविधि जोड़ी गई');
    } else {
        // Edit
        $stmt = $pdo->prepare("UPDATE activities SET name = ?, sort_order = ? WHERE id = ? AND shakha_id = ?");
        $stmt->execute([$name, $sortOrder, $id, $shakhaId]);
        header('Location: ../pages/activities.php?success=गतिविधि अपडेट की गई');
    }
} elseif ($action === 'delete') {
    $id = $_POST['id'] ?? null;
    if ($id) {
        $stmt = $pdo->prepare("UPDATE activities SET is_active = 0 WHERE id = ? AND shakha_id = ?");
        $stmt->execute([$id, $shakhaId]);
        header('Location: ../pages/activities.php?success=गतिविधि हटा दी गई');
    }
} else {
    header('Location: ../pages/activities.php');
}
