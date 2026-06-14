<?php
// convert_to_webp.php

function convertImageToWebp($sourcePath, $destinationPath, $quality = 80) {
    $info = getimagesize($sourcePath);
    if ($info === false) {
        return false;
    }
    
    $mime = $info['mime'];
    switch ($mime) {
        case 'image/jpeg':
            $image = @imagecreatefromjpeg($sourcePath);
            break;
        case 'image/png':
            $image = @imagecreatefrompng($sourcePath);
            if ($image) {
                imagepalettetotruecolor($image);
                imagealphablending($image, true);
                imagesavealpha($image, true);
            }
            break;
        default:
            return false;
    }
    
    if (!$image) {
        return false;
    }
    
    $result = imagewebp($image, $destinationPath, $quality);
    imagedestroy($image);
    return $result;
}

$dirs = [
    __DIR__ . '/../assets/images/personalities',
    __DIR__ . '/../flutter_app/assets/images/personalities',
    __DIR__ . '/../personalities_photos',
    __DIR__ . '/../assets/images', // for static images like logo, dr_hedgewar, etc.
];

foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        continue;
    }
    echo "Processing directory: $dir\n";
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        $fullPath = $dir . '/' . $file;
        if (is_file($fullPath)) {
            $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
            if (in_array($ext, ['png', 'jpg', 'jpeg'])) {
                $destPath = pathinfo($fullPath, PATHINFO_DIRNAME) . '/' . pathinfo($fullPath, PATHINFO_FILENAME) . '.webp';
                
                // Perform conversion
                if (convertImageToWebp($fullPath, $destPath)) {
                    echo "Converted: $file -> " . pathinfo($file, PATHINFO_FILENAME) . ".webp\n";
                    
                    // Delete original if it's a personality photo to save space
                    // Keep logo/icon PNGs because print engines (FPDF/Dompdf) might not support WebP.
                    $isStaticAsset = ($file === 'logo.png' || $file === 'flag_icon.png' || $file === 'favicon.png' || strpos($file, 'backup') !== false);
                    if (!$isStaticAsset) {
                        unlink($fullPath);
                        echo "Deleted original: $file\n";
                    }
                } else {
                    echo "Failed to convert: $file\n";
                }
            }
        }
    }
}
echo "Conversion pipeline complete.\n";
?>
