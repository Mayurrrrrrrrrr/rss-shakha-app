<?php
require_once __DIR__ . '/../config/db.php';

try {
    // Update .png to .webp in personalities image_path
    $stmt = $pdo->exec("UPDATE personalities SET image_path = REPLACE(image_path, '.png', '.webp')");
    echo "Updated personalities image paths to WebP ($stmt rows affected).\n";
    
    // Also let's clean up any .jpg references if they were converted or need to be .webp
    $stmt2 = $pdo->exec("UPDATE personalities SET image_path = REPLACE(image_path, '.jpg', '.webp')");
    echo "Updated personalities image paths from JPG to WebP ($stmt2 rows affected).\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
