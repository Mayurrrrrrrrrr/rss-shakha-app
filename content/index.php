<?php
/**
 * Content Router — routes /geet/, /prarthna/, etc. to content/*.php
 * Old URLs preserved via .htaccess rewrites
 */
$page = isset($_GET['page']) ? basename($_GET['page']) : '';

$validPages = [
    'amrit-vachan',
    'geet',
    'ghoshnayein',
    'subhashit',
    'prarthna',
    'ekatmata-mantra'
];

if (in_array($page, $validPages)) {
    require_once __DIR__ . "/{$page}.php";
} else {
    http_response_code(404);
    header('Location: /');
    exit;
}
