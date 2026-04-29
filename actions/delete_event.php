<?php
session_start();
require_once '../config/db.php';
require_once '../includes/auth.php';
csrf_verify();

// Only allow Admins and Mukhyashikshaks
if (!isLoggedIn() || (!isAdmin() && !isMukhyashikshak())) {
    header("Location: ../index.php");
    exit;
}

if (isset($_GET['id'])) {
    $event_id = $_GET['id'];

    if (!isAdmin()) {
        // Mukhyashikshaks can only delete their shakha's events
        $shakha_id = $_SESSION['shakha_id'];
        $stmt = $pdo->prepare("DELETE FROM events WHERE id = ? AND shakha_id = ?");
        $stmt->execute([$event_id, $shakha_id]);
    } else {
        // Admins can delete anything
        $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
        $stmt->execute([$event_id]);
    }

    header("Location: ../pages/events.php?msg=Event+deleted+successfully");
    exit;
}

header("Location: ../pages/events.php");
