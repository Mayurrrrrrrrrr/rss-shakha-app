<?php

namespace App\Core;

class ExceptionHandler
{
    public static function register()
    {
        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
    }

    public static function handleError($level, $message, $file, $line)
    {
        if (error_reporting() !== 0) {
            throw new \ErrorException($message, 0, $level, $file, $line);
        }
    }

    public static function handleException(\Throwable $exception)
    {
        $code = $exception->getCode();
        if ($code < 100 || $code > 599) {
            $code = 500;
        }
        
        http_response_code($code);

        $isApi = strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') === 0;

        if ($isApi) {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine()
            ]);
        } else {
            echo "<div style='font-family: sans-serif; padding: 20px; background: #fff3ed; border: 1px solid #ffcc80; border-radius: 8px; margin: 20px;'>";
            echo "<h1 style='color: #d32f2f; margin-top: 0;'>🚨 Application Error</h1>";
            echo "<p><strong>Message:</strong> " . htmlspecialchars($exception->getMessage()) . "</p>";
            echo "<p><strong>File:</strong> " . $exception->getFile() . " on line <strong>" . $exception->getLine() . "</strong></p>";
            echo "<h3>Stack Trace:</h3>";
            echo "<pre style='background: #333; color: #fff; padding: 15px; border-radius: 5px; overflow-x: auto;'>" . $exception->getTraceAsString() . "</pre>";
            echo "</div>";
        }
    }
}
