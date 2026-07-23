<?php
try {
    require '/var/www/html/sanghasthan/app/Core/Autoloader.php';
    \App\Core\Autoloader::register();
    define('BASE_PATH', '/var/www/html/sanghasthan');
    require BASE_PATH.'/includes/PanchangHelper.php';
    require BASE_PATH.'/config/db.php';
    
    $shakhaId = 1;
    $today = date('Y-m-d');
    
    $shakha = $pdo->prepare('SELECT * FROM shakhas WHERE id = ?');
    $shakha->execute([$shakhaId]);
    $shakha = $shakha->fetch();
    
    $panchang = \PanchangHelper::getForDate($pdo, $today, $shakhaId);
    
    $sub = $pdo->prepare('SELECT * FROM subhashits WHERE shakha_id = ? AND (is_deleted IS NULL OR is_deleted = 0) ORDER BY RAND() LIMIT 1');
    $sub->execute([$shakhaId]);
    $subhashit = $sub->fetch();
    
    $logoPath = BASE_PATH . '/' . ($shakha['logo'] ?: 'assets/images/logo.svg');
    $generator = new \App\Core\ImageGenerator();
    echo "Generating image...\n";
    $imagePath = $generator->generate($panchang, $subhashit ?: null, $logoPath, $shakha['name']);
    echo "Done! Image at: $imagePath\n";
    
    echo "Caption: \n";
    require BASE_PATH.'/pages/daily_message_settings.php'; // wait this will run the page...
    
} catch (\Exception $e) {
    echo "Fatal Error Caught: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
