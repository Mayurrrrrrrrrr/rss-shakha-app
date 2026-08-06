<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/PanchangHelper.php';
$data = PanchangHelper::getForDate($pdo, '2026-08-06', 1);
print_r($data);
