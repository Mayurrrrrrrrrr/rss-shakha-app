<?php

// 1. Error Reporting (Dev mode)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 2. Constants
define('BASE_PATH', dirname(__DIR__));

// 3. Autoloading
require_once BASE_PATH . '/app/Core/Autoloader.php';
\App\Core\Autoloader::register();

// 3. Database connection
require_once BASE_PATH . '/config/db.php';
\App\Core\DB::init($pdo);

// 4. Initialize Application & Routing
$router = new \App\Core\Router();
require_once BASE_PATH . '/routes/web.php';
require_once BASE_PATH . '/routes/api.php';
$router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);
