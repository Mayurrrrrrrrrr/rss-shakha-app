<?php
require_once 'config/db.php';
require_once 'includes/PanchangHelper.php';
require_once 'includes/LunarMonthCalculator.php';
require_once 'includes/PanchangCalculator.php';

$calc = new PanchangCalculator();
echo "PC Output:\n";
print_r($calc->getPanchang('2026-08-07'));
print_r(PanchangHelper::getForDate($pdo, '2026-08-07'));
