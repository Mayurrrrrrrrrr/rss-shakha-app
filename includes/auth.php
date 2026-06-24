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

if (!function_exists('getJWTSecret')) {
    function getJWTSecret() {
        return defined('DB_PASS') ? (DB_PASS ?: 'sanghasthan_sec_key_384') : 'sanghasthan_sec_key_384';
    }
}
if (!function_exists('validateAPIToken')) {
    function validateAPIToken($token) {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }
        
        list($base64Header, $base64Payload, $base64Signature) = $parts;
        
        $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, getJWTSecret(), true);
        $expectedSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        
        if (!hash_equals($expectedSignature, $base64Signature)) {
            return null;
        }
        
        $payload = json_decode(base64_decode(str_replace(['-','_'], ['+','/'], $base64Payload)), true);
        if (!is_array($payload) || !isset($payload['exp']) || $payload['exp'] < time()) {
            return null;
        }
        
        return $payload;
    }
}

if (!function_exists('generateAPIToken')) {
    function generateAPIToken($user_id, $user_type, $shakha_id) {
        $header = json_encode(['alg' => 'HS256', 'typ' => 'JWT']);
        $payload = json_encode([
            'user_id' => (int)$user_id,
            'user_type' => $user_type,
            'shakha_id' => (int)$shakha_id,
            'exp' => time() + (30 * 24 * 60 * 60) // 30 days
        ]);
        
        $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
        
        $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, getJWTSecret(), true);
        $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        
        return $base64Header . "." . $base64Payload . "." . $base64Signature;
    }
}

// Token-based auto login for webviews/external links
if (isset($_GET['token']) && !empty($_GET['token'])) {
    // Require DB configuration to have access to DB_PASS
    require_once __DIR__ . '/../config/db.php';
    
    $payload = validateAPIToken($_GET['token']);
    if ($payload) {
        $_SESSION['user_id'] = $payload['user_id'];
        $_SESSION['user_type'] = $payload['user_type'];
        $_SESSION['shakha_id'] = $payload['shakha_id'];
        $_SESSION['last_active'] = time();
    }
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
    header('Location: /login.php?timeout=1');
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
        header('Location: /login.php');
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

if (!function_exists('authenticateAPIRequest')) {
    function authenticateAPIRequest() {
        $headers = [];
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
        } else {
            // fallback if getallheaders is not available
            foreach ($_SERVER as $name => $value) {
                if (substr($name, 0, 5) == 'HTTP_') {
                    $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
                }
            }
        }
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        
        if (empty($authHeader)) {
            $customToken = $headers['X-API-Token'] ?? $headers['x-api-token'] ?? $headers['X-Api-Token'] ?? '';
            if (!empty($customToken)) {
                $authHeader = 'Bearer ' . $customToken;
            }
        }
        
        if (empty($authHeader)) {
            $paramToken = $_GET['token'] ?? $_POST['token'] ?? '';
            if (!empty($paramToken)) {
                $authHeader = 'Bearer ' . $paramToken;
            }
        }
        
        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $token = $matches[1];
            $payload = validateAPIToken($token);
            if ($payload) {
                return $payload;
            }
        }
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (isset($_SESSION['user_id'])) {
            return [
                'user_id' => $_SESSION['user_id'],
                'user_type' => $_SESSION['user_type'],
                'shakha_id' => $_SESSION['shakha_id']
            ];
        }
        
        http_response_code(401);
        header("Content-Type: application/json; charset=UTF-8");
        echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
        exit;
    }
}

