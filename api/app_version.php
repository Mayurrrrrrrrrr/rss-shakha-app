<?php
/**
 * App Version Check API
 */
header('Content-Type: application/json; charset=UTF-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

// Return the latest application release meta info
echo json_encode([
    'status' => 'success',
    'version_name' => '1.0.4',
    'version_code' => 5,
    'download_url' => 'https://sanghasthan.yuktaa.com/sanghasthan.apk',
    'force_update' => false,
    'message' => 'नया संस्करण (1.0.4) उपलब्ध है। बेहतर अनुभव और नए फीचर्स के लिए कृपया अभी अपडेट करें!'
], JSON_UNESCAPED_UNICODE);
