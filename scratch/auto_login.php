<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';

// Log in as superadmin (id = 2)
$_SESSION['user_id'] = 2;
$_SESSION['role'] = 'admin';
$_SESSION['shakha_id'] = 1;

// Get latest daily record id
$stmt = $pdo->query("SELECT id FROM daily_records WHERE shakha_id = 1 ORDER BY record_date DESC LIMIT 1");
$recordId = $stmt->fetchColumn();

if ($recordId) {
    header("Location: ../pages/snapshot.php?id=" . $recordId);
} else {
    header("Location: ../pages/dashboard.php");
}
exit;
?>
