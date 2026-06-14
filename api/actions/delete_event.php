<?php
require_once '../../includes/auth.php';
require_once '../../config/db.php';
csrf_verify();

// Only allow Admins and Mukhyashikshaks
if (!isLoggedIn() || (!isAdmin() && !isMukhyashikshak())) {
    header("Location: ../index.php");
    exit;
}

$inputs = getRequestInputs();
if (isset($inputs['id'])) {
    $event_id = $inputs['id'];

    if (!isAdmin()) {
        // Mukhyashikshaks can only delete their shakha's events
        $shakha_id = $_SESSION['shakha_id'];
        $stmt = $pdo->prepare("UPDATE events SET is_active = 0, is_deleted = 1, updated_at = NOW() WHERE id = ? AND shakha_id = ?");
        $stmt->execute([$event_id, $shakha_id]);
    } else {
        // Admins can delete anything
        $stmt = $pdo->prepare("UPDATE events SET is_active = 0, is_deleted = 1, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$event_id]);
    }

    header("Location: ../../pages/events.php?msg=Event+deleted+successfully");
    exit;
}

header("Location: ../../pages/events.php");
