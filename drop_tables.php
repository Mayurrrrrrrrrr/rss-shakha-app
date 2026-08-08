<?php
require_once 'config/db.php';
$pdo->exec("DROP TABLE IF EXISTS em_rooms, em_room_allotments, em_meals, em_meal_tracking, em_work_categories, em_work_assignments, em_schedule;");
echo "Dropped tables successfully.\n";
