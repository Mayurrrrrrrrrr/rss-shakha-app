<?php
require_once __DIR__ . '/../app/Core/Autoloader.php';
\App\Core\Autoloader::register();
define('BASE_PATH', dirname(__DIR__));
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/PanchangHelper.php';
\App\Core\DB::init($pdo);

$today = date('Y-m-d');
$shakhaId = 1; // Assuming default 1

$shakha = $pdo->prepare("SELECT * FROM shakhas WHERE id = ?");
$shakha->execute([$shakhaId]);
$shakha = $shakha->fetch();

$panchang = \PanchangHelper::getForDate($pdo, $today, $shakhaId);

$amrit = $pdo->prepare("SELECT * FROM amrit_vachan WHERE shakha_id = ? AND (is_deleted IS NULL OR is_deleted = 0) ORDER BY RAND() LIMIT 1");
$amrit->execute([$shakhaId]);
$amritVachan = $amrit->fetch();

$logoPath = dirname(__DIR__) . '/' . ($shakha['logo'] ?: 'assets/images/logo.svg');

$generator = new \App\Core\AmritVachanImageGenerator();
$imagePath = $generator->generate($panchang, $amritVachan, $logoPath, $shakha['name']);

echo basename($imagePath);
