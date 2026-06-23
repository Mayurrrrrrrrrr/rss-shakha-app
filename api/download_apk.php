<?php
/**
 * App Download API - Forces download with versioned filename
 */
$file = __DIR__ . '/../sanghasthan.apk';

if (file_exists($file)) {
    // Read version info
    $version_name = '1.0.8'; // Default
    
    header('Content-Description: File Transfer');
    header('Content-Type: application/vnd.android.package-archive');
    header('Content-Disposition: attachment; filename="sanghasthan_v' . $version_name . '.apk"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    header('Content-Length: ' . filesize($file));
    readfile($file);
    exit;
} else {
    http_response_code(404);
    echo "APK file not found.";
}
?>
