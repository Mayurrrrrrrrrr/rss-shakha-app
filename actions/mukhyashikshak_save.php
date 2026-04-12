<?php
require_once '../includes/auth.php';
require_once '../config/db.php';
requireLogin();

if (!isAdmin()) {
    header('Location: ../pages/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $name = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $shakha_id = $_POST['shakha_id'] ?? null;

    if (empty($name) || empty($username) || empty($shakha_id)) {
        header('Location: ../pages/mukhyashikshaks.php?msg=error');
        exit;
    }

    try {
        // Check if username exists (excluding current user if editing)
        $stmt = $pdo->prepare("SELECT id FROM admin_users WHERE username = ? AND id != ?");
        $stmt->execute([$username, $id ?: 0]);
        if ($stmt->fetch()) {
            header('Location: ../pages/mukhyashikshaks.php?msg=error_username');
            exit;
        }

        if ($id) {
            if (!empty($password)) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE admin_users SET name = ?, username = ?, password = ?, shakha_id = ? WHERE id = ?");
                $stmt->execute([$name, $username, $hash, $shakha_id, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE admin_users SET name = ?, username = ?, shakha_id = ? WHERE id = ?");
                $stmt->execute([$name, $username, $shakha_id, $id]);
            }
        } else {
            if (empty($password)) {
                header('Location: ../pages/mukhyashikshaks.php?msg=error');
                exit;
            }
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO admin_users (name, username, password, role, shakha_id) VALUES (?, ?, ?, 'mukhyashikshak', ?)");
            $stmt->execute([$name, $username, $hash, $shakha_id]);
        }
        header('Location: ../pages/mukhyashikshaks.php?msg=saved');
    } catch (PDOException $e) {
        header('Location: ../pages/mukhyashikshaks.php?msg=error');
    }
} else {
    header('Location: ../pages/mukhyashikshaks.php');
}
