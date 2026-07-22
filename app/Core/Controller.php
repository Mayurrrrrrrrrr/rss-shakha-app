<?php

namespace App\Core;

class Controller
{
    protected function view($name, $data = [])
    {
        return View::render($name, $data);
    }
    
    protected function json($data, $status = 200)
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    protected function redirect($path)
    {
        header("Location: {$path}");
        exit;
    }
}
