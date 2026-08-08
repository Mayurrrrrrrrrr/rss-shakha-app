<?php
$dir = new RecursiveDirectoryIterator(__DIR__ . '/api/v1/event');
$ite = new RecursiveIteratorIterator($dir);
$files = new RegexIterator($ite, '/.*\.php$/', RegexIterator::GET_MATCH);

$count = 0;
foreach ($files as $file) {
    $path = $file[0];
    $content = file_get_contents($path);
    
    $newContent = str_replace('em_food_tracking', 'em_meal_tracking', $content);
    
    if ($content !== $newContent) {
        file_put_contents($path, $newContent);
        echo "Updated: $path\n";
        $count++;
    }
}
echo "Total files updated: $count\n";
