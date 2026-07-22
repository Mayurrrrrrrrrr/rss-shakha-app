<?php

/**
 * Web Routes
 */

$router->get('/', function() {
    echo "Welcome to Sanghasthan MVC!";
});

$router->get('/test', function() {
    echo "Test route works!";
});

$router->get('/dashboard', [\App\Controllers\DashboardController::class, 'index'], [\App\Middleware\AuthMiddleware::class]);
