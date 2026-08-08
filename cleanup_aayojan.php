<?php
require_once "/var/www/html/sanghasthan/config/db.php";
try {
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
    $tables = [
        "em_events", "em_organizers", "em_participants", "em_work_categories", 
        "em_work_assignments", "em_attendance_sessions", "em_attendance_records", 
        "em_meals", "em_meal_records", "em_rooms", "em_room_allocations", "em_schedules"
    ];
    foreach ($tables as $table) {
        try {
            $pdo->exec("TRUNCATE TABLE $table");
        } catch (Exception $e) {
            // Ignore if table doesn't exist
        }
    }
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    echo "Dummy data cleaned successfully!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
