<?php
$env_file = __DIR__ . '/../.env';
if (file_exists($env_file)) {
    $env = parse_ini_file($env_file);
    define('DB_HOST', $env['DB_HOST'] ?? 'localhost');
    define('DB_NAME', $env['DB_NAME'] ?? '');
    define('DB_USER', $env['DB_USER'] ?? '');
    define('DB_PASS', $env['DB_PASS'] ?? '');
    if (isset($env['GEMINI_API_KEY'])) {
        define('GEMINI_API_KEY', $env['GEMINI_API_KEY']);
    }
    if (isset($env['OPENAI_API_KEY'])) {
        define('OPENAI_API_KEY', $env['OPENAI_API_KEY']);
    }
} else {
    // Fallback — replace these with actual values on server
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'sanghasthan');
    define('DB_USER', 'root');
    define('DB_PASS', '');
}

define('APP_VERSION', '1.2.1'); // Update this when styles change

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER, DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_PERSISTENT         => true,
        ]
    );
} catch (PDOException $e) {
    error_log('DB Connection failed: ' . $e->getMessage());
    
    http_response_code(503);
    
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    $contentType = $_SERVER['HTTP_CONTENT_TYPE'] ?? $_SERVER['CONTENT_TYPE'] ?? '';
    
    if (strpos($accept, 'application/json') !== false || strpos($contentType, 'application/json') !== false) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'success' => false,
            'message' => 'Database connection failed'
        ]);
    } else {
        header('Content-Type: text/html; charset=UTF-8');
        ?>
        <!DOCTYPE html>
        <html lang="hi">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>डेटाबेस कनेक्शन विफल (Database Connection Failed)</title>
            <style>
                body {
                    font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
                    background: #f4f6f9;
                    color: #333;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    height: 100vh;
                    margin: 0;
                }
                .error-card {
                    background: #fff;
                    padding: 40px 30px;
                    border-radius: 12px;
                    box-shadow: 0 10px 25px rgba(0,0,0,0.05);
                    text-align: center;
                    max-width: 450px;
                    width: 90%;
                }
                h1 {
                    color: #e53935;
                    margin-top: 0;
                    font-size: 24px;
                }
                p {
                    font-size: 16px;
                    line-height: 1.6;
                    color: #555;
                }
            </style>
        </head>
        <body>
            <div class="error-card">
                <h1>🚩 तकनीकी समस्या (Technical Issue)</h1>
                <p>डेटाबेस कनेक्शन स्थापित करने में विफलता आई है। कृपया कुछ समय बाद पुनः प्रयास करें।</p>
            </div>
        </body>
        </html>
        <?php
    }
    exit;
}
