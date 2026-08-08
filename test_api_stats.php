<?php
$url = 'https://sanghasthan.yuktaa.com/api/v1/event/auth/login.php';
$data = ['username' => '9644771118', 'password' => '71118'];

$options = [
    'http' => [
        'header'  => "Content-type: application/json\r\n",
        'method'  => 'POST',
        'content' => json_encode($data)
    ]
];
$context  = stream_context_create($options);
$result = file_get_contents($url, false, $context);
$loginResp = json_decode($result, true);

if (!isset($loginResp['data']['token'])) {
    die("Login failed: " . $result);
}
$token = $loginResp['data']['token'];

$urlStats = 'https://sanghasthan.yuktaa.com/api/v1/event/dashboard/stats.php';
$optionsStats = [
    'http' => [
        'header'  => "Authorization: Bearer " . $token . "\r\n",
        'method'  => 'GET'
    ]
];
$contextStats = stream_context_create($optionsStats);
$resultStats = file_get_contents($urlStats, false, $contextStats);
echo $resultStats;
