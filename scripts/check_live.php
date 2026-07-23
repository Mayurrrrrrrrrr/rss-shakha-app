<?php
$opts = [
    "http" => [
        "method" => "GET",
        "header" => "Cookie: PHPSESSID=test_session\r\n",
        "ignore_errors" => true
    ]
];
$context = stream_context_create($opts);
$response = file_get_contents('http://localhost/sanghasthan/pages/daily_message_settings.php', false, $context);
echo "Response Length: " . strlen($response) . "\n";
echo substr($response, 0, 500);
