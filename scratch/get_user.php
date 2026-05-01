<?php
require_once __DIR__ . '/config/db.php';
$stmt = $pdo->prepare("SELECT id, shakha_id FROM users WHERE username = ?");
$stmt->execute(['aadiguru.ghatkopar']);
$user = $stmt->fetch();
if ($user) {
    echo "ID: " . $user['id'] . "\n";
    echo "SHAKHA_ID: " . $user['shakha_id'] . "\n";
} else {
    echo "User not found\n";
}
