<?php

namespace App\Core;

/**
 * Native PSR-4 Autoloader
 * Replaces the need for Composer, keeping the ecosystem 100% vanilla PHP.
 */
class Autoloader
{
    public static function register()
    {
        spl_autoload_register(function ($class) {
            // Project-specific namespace prefix
            $prefix = 'App\\';
            
            // Base directory for the namespace prefix (points to /app/)
            $base_dir = __DIR__ . '/../';

            // Does the class use the namespace prefix?
            $len = strlen($prefix);
            if (strncmp($prefix, $class, $len) !== 0) {
                return; // no, move to the next registered autoloader
            }

            // Get the relative class name
            $relative_class = substr($class, $len);

            // Replace the namespace prefix with the base directory, 
            // replace namespace separators with directory separators, append .php
            $file = $base_dir . str_replace('\\', DIRECTORY_SEPARATOR, $relative_class) . '.php';

            // If the file exists, require it
            if (file_exists($file)) {
                require $file;
            }
        });
    }
}
