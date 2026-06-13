<?php
// PHP Hardening (must be done before session_start)
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', 1);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");

// CSRF Token Initialization
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Idle session timeout
if (isset($_SESSION['last_active']) && (time() - $_SESSION['last_active']) > 1800) {
    $_SESSION = [];
    session_destroy();
    header('Location: /index.php?timeout=1');
    exit;
}
if (isset($_SESSION['user_id'])) {
    $_SESSION['last_active'] = time();
}

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
  $inputs = getRequestInputs();
  $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $inputs['csrf_token'] ?? '';
  if (empty($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
      http_response_code(403);
      header('Content-Type: application/json; charset=UTF-8');
      echo json_encode([
          'success' => false,
          'message' => 'CSRF validation failed'
      ]);
      exit;
  }
}
 
/**
 * Localization Helpers
 */
function toHindiNumerals($number) {
    $hindi_numerals = ['०', '१', '२', '३', '४', '५', '६', '७', '८', '९'];
    $str = (string)$number;
    $res = '';
    for ($i = 0; $i < strlen($str); $i++) {
        $char = $str[$i];
        if (is_numeric($char)) {
            $res .= $hindi_numerals[$char];
        } else {
            $res .= $char;
        }
    }
    return $res;
}

function getHindiDate() {
    $months = [
        1 => 'जनवरी', 2 => 'फ़रवरी', 3 => 'मार्च', 4 => 'अप्रैल',
        5 => 'मई', 6 => 'जून', 7 => 'जुलाई', 8 => 'अगस्त',
        9 => 'सितंबर', 10 => 'अक्टूबर', 11 => 'नवंबर', 12 => 'दिसंबर'
    ];
    $day = date('j');
    $month = $months[(int)date('n')];
    $year = date('Y');
    return toHindiNumerals($day) . ' ' . $month . ' ' . toHindiNumerals($year);
}

/**
 * Retrieve incoming request inputs, handling both JSON and form-urlencoded payloads.
 */
function getRequestInputs(): array
{
    static $inputs = null;
    if ($inputs !== null) {
        return $inputs;
    }
    
    $inputs = array_merge($_GET, $_POST);
    
    $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
    if (stripos($contentType, 'application/json') !== false) {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        if (is_array($data)) {
            $inputs = array_merge($inputs, $data);
        }
    }
    
    return $inputs;
}

/**
 * Safe mime type lookup fallback in case fileinfo PHP extension is missing.
 */
function safe_mime_content_type($path) {
    if (function_exists('mime_content_type')) {
        $mime = @mime_content_type($path);
        if ($mime) return $mime;
    }
    
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    switch ($ext) {
        case 'png': return 'image/png';
        case 'jpg':
        case 'jpeg': return 'image/jpeg';
        case 'gif': return 'image/gif';
        case 'svg': return 'image/svg+xml';
        default: return 'application/octet-stream';
    }
}

