<?php
session_start();

if (isset($_SESSION['last_active']) && (time() - $_SESSION['last_active']) > 1800) {
  session_unset(); session_destroy(); header('Location: /index.php?expired=1'); exit;
}
$_SESSION['last_active'] = time();

/**
 * Enhanced check if user is logged in, redirect to login if not
 */
function requireLogin()
{
    if (!isset($_SESSION['user_id'])) {
        header('Location: index.php');
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
  if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  return $_SESSION['csrf_token'];
}

function csrf_verify(): void {
  if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? ''))
    { http_response_code(403); die('CSRF validation failed'); }
}
