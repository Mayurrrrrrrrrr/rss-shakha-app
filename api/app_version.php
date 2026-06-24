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
    'version_name' => '1.0.9',
    'version_code' => 10,
    'download_url' => 'https://sanghasthan.yuktaa.com/api/download_apk.php',
    'force_update' => false,
    'message' => 'नया संस्करण (1.0.9) उपलब्ध है! इस अपडेट में बिना लॉगिन के बौद्धिक और पंचांग देखने की सुविधा (अतिथि मोड), होम स्क्रीन पर कैलेंडर एकीकरण, और सुरक्षा सुधार शामिल हैं।'
], JSON_UNESCAPED_UNICODE);
