<?php

namespace App\Core;

class Auth
{
    public static function check()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        return isset($_SESSION['user_id']);
    }

    public static function user()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        return [
            'id' => $_SESSION['user_id'] ?? null,
            'role' => $_SESSION['role'] ?? null,
            'shakha_id' => $_SESSION['shakha_id'] ?? null,
            'name' => $_SESSION['name'] ?? null
        ];
    }
    
    public static function isSwayamsevak()
    {
        $user = self::user();
        return $user['role'] === 'swayamsevak';
    }
}
