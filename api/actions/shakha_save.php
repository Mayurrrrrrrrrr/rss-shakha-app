<?php
require_once '../../includes/auth.php';
require_once '../../config/db.php';
requireLogin();
csrf_verify();
if (!isAdmin()) {
    header('Location: ../../pages/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $name = trim($_POST['name'] ?? '');

    if (empty($name)) {
        header('Location: ../../pages/shakhas.php?msg=error');
        exit;
    }

    try {
        if ($id) {
            $stmt = $pdo->prepare("UPDATE shakhas SET name = ? WHERE id = ?");
            $stmt->execute([$name, $id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO shakhas (name) VALUES (?)");
            $stmt->execute([$name]);
        }
        header('Location: ../../pages/shakhas.php?msg=saved');
    } catch (PDOException $e) {
        header('Location: ../../pages/shakhas.php?msg=error');
    }
} else {
    header('Location: ../../pages/shakhas.php');
}
