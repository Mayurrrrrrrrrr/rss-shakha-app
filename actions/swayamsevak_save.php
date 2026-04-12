<?php
/**
 * Save Swayamsevak - स्वयंसेवक सहेजें
 */
require_once '../includes/auth.php';
require_once '../config/db.php';
requireLogin();

if (isSwayamsevak()) {
    header('Location: ../pages/swayamsevak_dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/swayamsevaks.php');
    exit;
}

$id = $_POST['id'] ?? null;
$name = trim($_POST['name'] ?? '');
$address = trim($_POST['address'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$age = intval($_POST['age'] ?? 0);
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$category = $_POST['category'] ?? 'Tarun';
$shakhaId = getCurrentShakhaId();

if (empty($name) || !$shakhaId) {
    header('Location: ../pages/swayamsevaks.php');
    exit;
}

// Ensure unique username if provided
if (!empty($username)) {
    $stmt = $pdo->prepare("SELECT id FROM swayamsevaks WHERE username = ? AND id != ?");
    $stmt->execute([$username, $id ?: 0]);
    if ($stmt->fetch()) {
        header('Location: ../pages/swayamsevaks.php?msg=error_username');
        exit;
    }
} else {
    $username = null;
}

if ($id) {
    // Update
    if (!empty($password)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE swayamsevaks SET name = ?, address = ?, phone = ?, age = ?, username = ?, password = ?, category = ? WHERE id = ? AND shakha_id = ?");
        $stmt->execute([$name, $address, $phone, $age ?: null, $username, $hash, $category, $id, $shakhaId]);
    } else {
        $stmt = $pdo->prepare("UPDATE swayamsevaks SET name = ?, address = ?, phone = ?, age = ?, username = ?, category = ? WHERE id = ? AND shakha_id = ?");
        $stmt->execute([$name, $address, $phone, $age ?: null, $username, $category, $id, $shakhaId]);
    }
} else {
    // Insert
    $hash = !empty($password) ? password_hash($password, PASSWORD_DEFAULT) : null;
    $stmt = $pdo->prepare("INSERT INTO swayamsevaks (name, address, phone, age, username, password, shakha_id, category) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$name, $address, $phone, $age ?: null, $username, $hash, $shakhaId, $category]);
}

header('Location: ../pages/swayamsevaks.php?msg=saved');
exit;
