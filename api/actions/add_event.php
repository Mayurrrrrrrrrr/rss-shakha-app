<?php
require_once '../../includes/auth.php';
require_once '../../config/db.php';
csrf_verify();

// Only allow Admins and Mukhyashikshaks
if (!isLoggedIn() || (!isAdmin() && !isMukhyashikshak())) {
    header("Location: ../index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inputs = getRequestInputs();
    $title = trim($inputs['title'] ?? '');
    $description = trim($inputs['description'] ?? '');
    $event_date = $inputs['event_date'] ?? '';
    $event_time = $inputs['event_time'] ?? '';
    $location = trim($inputs['location'] ?? '');
    $meeting_link = trim($inputs['meeting_link'] ?? '');
    
    // Determine shakha_id: Global if Admin creates it (so NULL), Shakha specific if Mukhyashikshak (so their shakha_id)
    if (isAdmin()) {
        $shakha_id = isset($inputs['shakha_id']) && $inputs['shakha_id'] !== '' ? $inputs['shakha_id'] : null;
    } else {
        $shakha_id = $_SESSION['shakha_id']; 
    }

    $created_by = $_SESSION['user_id'];

    if (empty($title) || empty($event_date) || empty($event_time)) {
        header("Location: ../../pages/events.php?error=Missing+required+fields");
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO events (shakha_id, title, description, event_date, event_time, location, meeting_link, created_by, created_at, updated_at) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
        $stmt->execute([$shakha_id, $title, $description, $event_date, $event_time, $location, $meeting_link, $created_by]);
        header("Location: ../../pages/events.php?msg=Event+added+successfully!");
    } catch (PDOException $e) {
        // Log error and redirect
        header("Location: ../../pages/events.php?error=Database+error");
    }
} else {
    header("Location: ../../pages/events.php");
}
