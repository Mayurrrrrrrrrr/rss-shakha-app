<?php
// config/jwt.php
// Define secret key for JWT signing. In production, keep this secret safe (e.g., env variable).
if (!defined('JWT_SECRET')) {
    define('JWT_SECRET', 'your-very-secret-key-please-change');
}
?>
