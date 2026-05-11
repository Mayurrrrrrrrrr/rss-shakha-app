<?php
require_once 'includes/PanchangCalculator.php';
$calc = new PanchangCalculator();
$date = '2026-05-11';
$res = $calc->getPanchang($date . ' 05:30:00');
echo json_encode($res, JSON_PRETTY_PRINT);
