<?php
require_once __DIR__ . '/../app/Core/Autoloader.php';
\App\Core\Autoloader::register();
define('BASE_PATH', dirname(__DIR__));
require_once __DIR__ . '/../config/db.php';
\App\Core\DB::init($pdo);

$stmt = $pdo->prepare("SELECT content FROM amrit_vachan WHERE id = 140");
$stmt->execute();
$res = $stmt->fetch();
$content = $res['content'];
echo "Content: $content\n";

for ($i = 0; $i < mb_strlen($content); $i++) {
    $char = mb_substr($content, $i, 1);
    $hex = bin2hex($char);
    echo "$char -> $hex\n";
}
