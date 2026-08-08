<?php
/**
 * Central Authorization and Security Helpers
 * 
 * Included via header.php to ensure all pages have session access,
 * consistent authentication, and security helpers.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Enforce login for all pages except login.php
if (!isset($_SESSION['event_user_id']) && basename($_SERVER['PHP_SELF']) !== 'login.php') {
    header('Location: login.php');
    exit;
}

// Active Event Resolution
$current_event_id = $_SESSION['event_id'] ?? null;
$current_role = $_SESSION['event_role'] ?? '';
$current_vyavastha = $_SESSION['event_vyavastha'] ?? 'all';

/**
 * Enforce Admin Access Only
 */
function require_admin() {
    global $current_role, $current_vyavastha;
    if ($current_role !== 'admin' && $current_vyavastha !== 'all') {
        echo "<div class='container'><div class='card'><h2 style='color:var(--danger)'>Permission Denied</h2><p>You don't have permission to access this page.</p><a href='dashboard.php' class='btn'>Back to Dashboard</a></div></div>";
        include __DIR__ . '/footer.php';
        exit;
    }
}

/**
 * Enforce Volunteer Access
 */
function require_volunteer() {
    if (!isset($_SESSION['event_user_id'])) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Sanitize User Input
 */
function sanitize($input) {
    if (is_array($input)) {
        foreach ($input as $key => $value) {
            $input[$key] = sanitize($value);
        }
        return $input;
    }
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}
