<?php
/**
 * REST API Auth Helper - JWT generation & validation (Versioned API)
 */
require_once __DIR__ . '/../../../config/db.php';

if (!function_exists('getJWTSecret')) {
    function getJWTSecret() {
        return defined('DB_PASS') ? (DB_PASS ?: 'sanghasthan_sec_key_384') : 'sanghasthan_sec_key_384';
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

if (!function_exists('authenticateAPIRequest')) {
    function authenticateAPIRequest() {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        
        if (empty($authHeader)) {
            // Fallback 1: Custom X-API-Token header (Apache does not strip custom X- headers)
            $customToken = $headers['X-API-Token'] ?? $headers['x-api-token'] ?? $headers['X-Api-Token'] ?? '';
            if (!empty($customToken)) {
                $authHeader = 'Bearer ' . $customToken;
            }
        }
        
        if (empty($authHeader)) {
            // Fallback 2: GET/POST parameter fallback
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
        
        // Fallback to session check if running in web browser wrapper
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
?>
