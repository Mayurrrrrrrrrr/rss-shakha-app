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
    'version_name' => '1.0.7',
    'version_code' => 8,
    'download_url' => 'https://sanghasthan.yuktaa.com/sanghasthan.apk',
    'force_update' => true,
    'message' => 'नया संस्करण (1.0.7) उपलब्ध है! इस अपडेट में होम पेज को और अधिक सरल व सुगम बनाया गया है, साथ ही एक नया साइड मेनू (Drawer) जोड़ा गया है। कृपया बेहतर अनुभव के लिए अभी अपडेट करें!'
], JSON_UNESCAPED_UNICODE);
