<?php
$dir = new RecursiveDirectoryIterator(__DIR__ . '/api/v1/event');
$ite = new RecursiveIteratorIterator($dir);
$files = new RegexIterator($ite, '/.*\.php$/', RegexIterator::GET_MATCH);

$count = 0;
foreach ($files as $file) {
    $path = $file[0];
    $content = file_get_contents($path);
    
    // Replace 'em_attendance ' with 'em_participant_attendance '
    // We use preg_replace to match word boundaries
    // We want to replace em_attendance but NOT em_attendance_sessions
    // So we match em_attendance followed by a space, newline, or a character that is NOT an underscore
    $newContent = preg_replace('/\bem_attendance\b(?!_)/', 'em_participant_attendance', $content);
    
    if ($content !== $newContent) {
        file_put_contents($path, $newContent);
        echo "Updated: $path\n";
        $count++;
    }
}
echo "Total files updated: $count\n";
