<?php
session_start();

// Security headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Content-Security-Policy: default-src 'self'; font-src 'self' https://fonts.gstatic.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net; script-src 'self' 'unsafe-inline' https://translate.google.com https://translate.googleapis.com https://cdn.jsdelivr.net; img-src 'self' data: https://translate.google.com; connect-src 'self' https://translate.googleapis.com;");
header("Referrer-Policy: strict-origin-when-cross-origin");

// Error Handling
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// CSRF Token Initialization
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Session idle timeout (30 minutes)
if (isset($_SESSION['last_active']) && (time() - $_SESSION['last_active']) > 1800) {
    $_SESSION = [];
    session_destroy();
    header('Location: /index.php?timeout=1');
    exit;
}
$_SESSION['last_active'] = time();

/**
 * Enhanced check if user is logged in, redirect to login if not
 */
function requireLogin()
{
    if (!isset($_SESSION['user_id'])) {
        header('Location: /index.php');
        exit;
    }
}

/**
 * Get logged-in user's name
 */
function getUserName()
{
    return $_SESSION['user_name'] ?? 'उपयोगकर्ता';
}

function getAdminName()
{
    // Alias for backward compatibility
    return getUserName();
}

/**
 * Check if user is logged in (boolean)
 */
function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

/**
 * Role checkers
 */
function isAdmin()
{
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

function isMukhyashikshak()
{
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'mukhyashikshak';
}

function isSwayamsevak()
{
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'swayamsevak';
}

/**
 * Get the current user's Shakha ID
 */
function getCurrentShakhaId()
{
    return $_SESSION['shakha_id'] ?? null;
}

function csrf_token(): string { 
  return $_SESSION['csrf_token'] ?? '';
}

function csrf_verify(): void {
  if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
      http_response_code(403);
      die('CSRF validation failed');
  }
}
