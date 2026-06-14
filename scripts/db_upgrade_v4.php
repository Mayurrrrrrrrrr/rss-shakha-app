<?php
require_once __DIR__ . '/../config/db.php';

$tables = [
    'shakhas',
    'swayamsevaks',
    'daily_records',
    'attendance',
    'activities',
    'daily_activities',
    'timetable_defaults',
    'timetable_overrides',
    'events',
    'subhashits',
    'amrit_vachan',
    'geet',
    'ghoshnayein'
];

foreach ($tables as $table) {
    // Add created_at
    try {
        $pdo->exec("ALTER TABLE `$table` ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP");
        echo "Added created_at to $table\n";
    } catch (PDOException $e) {
        echo "Skip created_at for $table (may already exist)\n";
    }

    // Add updated_at
    try {
        $pdo->exec("ALTER TABLE `$table` ADD COLUMN updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
        echo "Added updated_at to $table\n";
    } catch (PDOException $e) {
        echo "Skip updated_at for $table (may already exist)\n";
    }

    // Add is_deleted
    try {
        $pdo->exec("ALTER TABLE `$table` ADD COLUMN is_deleted TINYINT DEFAULT 0");
        echo "Added is_deleted to $table\n";
    } catch (PDOException $e) {
        echo "Skip is_deleted for $table (may already exist)\n";
    }
}
echo "Database upgrade v4 completed successfully.\n";
?>
