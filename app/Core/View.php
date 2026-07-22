<?php

namespace App\Core;

class View
{
    public static function render($name, $data = [])
    {
        // Extract variables to current scope
        extract($data);
        
        // Convert dot notation (e.g., 'pages.home') to directory separator ('pages/home')
        $file = BASE_PATH . '/resources/views/' . str_replace('.', DIRECTORY_SEPARATOR, $name) . '.php';
        
        if (file_exists($file)) {
            require $file;
        } else {
            throw new \Exception("View [{$name}] not found at {$file}");
        }
    }
}
