<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/LunarMonthCalculator.php';
$lmc = new LunarMonthCalculator();
print_r($lmc->getMonthForDate(2026, 8, 6, 'Krishna'));
