<?php
require_once '../../includes/auth.php';
require_once '../../config/db.php';
requireLogin();
csrf_verify();
if (!isMukhyashikshak() && !isAdmin()) {
    header('Location: ../../pages/dashboard.php');
    exit;
}

$shakhaId = getCurrentShakhaId();
$action = $_POST['action'] ?? '';

if ($action === 'add' || $action === 'edit') {
    $name = trim($_POST['name'] ?? '');
    $sortOrder = (int) ($_POST['sort_order'] ?? 10);
    $id = $_POST['id'] ?? null;

    if (empty($name)) {
        header('Location: ../../pages/activities.php?error=à¤¨à¤¾à¤® à¤…à¤¨à¤¿à¤µà¤¾à¤°à¥à¤¯ à¤¹à¥ˆ');
        exit;
    }

    if ($action === 'add') {
        $stmt = $pdo->prepare("INSERT INTO activities (name, sort_order, shakha_id, is_active) VALUES (?, ?, ?, 1)");
        $stmt->execute([$name, $sortOrder, $shakhaId]);
        header('Location: ../../pages/activities.php?success=à¤—à¤¤à¤¿à¤µà¤¿à¤§à¤¿ à¤œà¥‹à¤¡à¤¼à¥€ à¤—à¤ˆ');
    } else {
        // Edit
        $stmt = $pdo->prepare("UPDATE activities SET name = ?, sort_order = ? WHERE id = ? AND shakha_id = ?");
        $stmt->execute([$name, $sortOrder, $id, $shakhaId]);
        header('Location: ../../pages/activities.php?success=à¤—à¤¤à¤¿à¤µà¤¿à¤§à¤¿ à¤…à¤ªà¤¡à¥‡à¤Ÿ à¤•à¥€ à¤—à¤ˆ');
    }
} elseif ($action === 'delete') {
    $id = $_POST['id'] ?? null;
    if ($id) {
        $stmt = $pdo->prepare("UPDATE activities SET is_active = 0 WHERE id = ? AND shakha_id = ?");
        $stmt->execute([$id, $shakhaId]);
        header('Location: ../../pages/activities.php?success=à¤—à¤¤à¤¿à¤µà¤¿à¤§à¤¿ à¤¹à¤Ÿà¤¾ à¤¦à¥€ à¤—à¤ˆ');
    }
} else {
    header('Location: ../../pages/activities.php');
}
