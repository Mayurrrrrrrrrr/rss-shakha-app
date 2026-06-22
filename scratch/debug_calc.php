<?php
require_once __DIR__ . '/../includes/PanchangCalculator.php';
$calc = new PanchangCalculator();
$res = $calc->getPanchang('2026-06-22');
echo "TITHI NUM: " . $res['tithi_num'] . "\n";
echo "PHASE: " . $res['phase'] . "\n";
echo "MOON LON: " . $res['moon_lon_sidereal'] . "\n";
echo "AYANAMSA: 24.19\n";
echo "TITHI START PHASE: " . (($res['tithi_num'] - 1) * 12.0) . "\n";
?>
