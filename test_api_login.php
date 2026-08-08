<?php
$url = 'https://sanghasthan.yuktaa.com/api/v1/event/auth/login.php';
$data = ['username' => '9644771118', 'password' => 'test'];

$options = [
    'http' => [
        'header'  => "Content-type: application/json\r\n",
        'method'  => 'POST',
        'content' => json_encode($data),
        'ignore_errors' => true // to get the response body even if 500
    ]
];
$context  = stream_context_create($options);
$result = file_get_contents($url, false, $context);
echo $http_response_header[0] . "\n";
echo $result;
