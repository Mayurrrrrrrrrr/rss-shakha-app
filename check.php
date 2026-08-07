<?php
require_once 'config/db.php';
require_once 'includes/PanchangHelper.php';
require_once 'includes/LunarMonthCalculator.php';

$lmc = new LunarMonthCalculator();
echo "LMC Output:\n";
print_r($lmc->getMonthForDate(2026, 8, 7, 'Krishna'));

echo "\nPH Output:\n";
print_r(PanchangHelper::getForDate($pdo, '2026-08-07'));
