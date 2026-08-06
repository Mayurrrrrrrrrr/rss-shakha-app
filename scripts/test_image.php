<?php
/**
 * Quick test to verify image generation works on the server.
 * Run: php scripts/test_image.php
 */
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/app/Core/Autoloader.php';
\App\Core\Autoloader::register();
require_once BASE_PATH . '/config/db.php';
\App\Core\DB::init($pdo);
require_once BASE_PATH . '/includes/PanchangHelper.php';

$date = '2026-08-07';
$shakhaId = 1;
$subhashitId = 3;

echo "Testing image generation for {$date}...\n";

// Fetch panchang
$panchang = \PanchangHelper::getForDate($pdo, $date);
echo "Panchang: Tithi={$panchang['tithi']}, Nakshatra={$panchang['nakshatra']}\n";

// Fetch shakha
$shakha = $pdo->query("SELECT * FROM shakhas WHERE id = {$shakhaId}")->fetch();
$shakhaName = $shakha['name'] ?? 'Test Shakha';
$logoPath = BASE_PATH . '/' . ($shakha['logo'] ?: 'assets/images/logo.svg');
echo "Shakha: {$shakhaName}, Logo: {$logoPath}\n";

// Fetch subhashit
$sub = $pdo->query("SELECT * FROM subhashits WHERE id = {$subhashitId} AND (is_deleted IS NULL OR is_deleted = 0)")->fetch();
if ($sub) {
    echo "Subhashit ID: {$sub['id']}\n";
} else {
    echo "No subhashit found, will generate without it.\n";
}

// Generate image
$generator = new \App\Core\ImageGenerator();
$imagePath = $generator->generate($panchang, $sub ?: null, $logoPath, $shakhaName, $date);
echo "Image generated: {$imagePath}\n";
echo "File size: " . round(filesize($imagePath) / 1024) . " KB\n";
echo "SUCCESS!\n";
