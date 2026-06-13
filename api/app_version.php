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
    'version_name' => '1.0.5',
    'version_code' => 6,
    'download_url' => 'https://sanghasthan.yuktaa.com/sanghasthan.apk',
    'force_update' => true,
    'message' => 'नया संस्करण (1.0.5) उपलब्ध है जिसमें दैनिक रिपोर्ट का स्नैपशॉट ऑप्शन तथा महत्वपूर्ण बग फिक्स शामिल हैं। कृपया सुचारू संचालन के लिए अभी अपडेट करें!'
], JSON_UNESCAPED_UNICODE);
