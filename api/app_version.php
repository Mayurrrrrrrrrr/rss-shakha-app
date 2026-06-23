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
    'version_name' => '1.0.8',
    'version_code' => 9,
    'download_url' => 'https://sanghasthan.yuktaa.com/sanghasthan.apk',
    'force_update' => false,
    'message' => 'नया संस्करण (1.0.8) उपलब्ध है! इस अपडेट में बहुप्रतीक्षित डार्क मोड (Dark Mode) जोड़ा गया है और पंचांग व उपस्थिति स्क्रीन की त्रुटियों को सुधारा गया है।'
], JSON_UNESCAPED_UNICODE);
