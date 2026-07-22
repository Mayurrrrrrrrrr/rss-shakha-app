<?php

/**
 * API Routes
 */

$router->get('/api/test', function() {
    header('Content-Type: application/json');
    echo json_encode(["status" => "success", "message" => "API works!"]);
});
