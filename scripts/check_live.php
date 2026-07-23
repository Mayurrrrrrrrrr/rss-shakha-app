<?php
$context = stream_context_create(['http' => ['ignore_errors' => true]]);
$response = file_get_contents('https://sanghasthan.yuktaa.com/pages/daily_message_settings.php', false, $context);
echo "Status: " . $http_response_header[0] . "\n";
