<?php
/**
 * Migration Script for Sync Support - सिंक समर्थन के लिए डेटाबेस अपग्रेड
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';

// Allow CLI execution or Admin user execution
if (php_sapi_name() !== 'cli') {
    requireLogin();
    if (!isAdmin()) {
        http_response_code(403);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['success' => false, 'message' => 'Forbidden']);
        exit;
    }
}

$tables_timestamp = [
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

$tables_active = [
    'daily_records',
    'events',
    'subhashits',
    'amrit_vachan',
    'geet',
    'ghoshnayein',
    'timetable_defaults',
    'timetable_overrides'
];

try {
    echo "Starting Sync Migrations...\n<br>";

    // 1. Add updated_at column to all tables for delta sync tracking
    foreach ($tables_timestamp as $table) {
        $columns = $pdo->query("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('updated_at', $columns)) {
            $pdo->exec("ALTER TABLE `$table` ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
            echo "Added 'updated_at' to table '$table'.\n<br>";
        } else {
            echo "Table '$table' already has 'updated_at'.\n<br>";
        }
    }

    // 2. Add is_active column for soft deletes to support synchronizing deletions
    foreach ($tables_active as $table) {
        $columns = $pdo->query("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('is_active', $columns)) {
            $pdo->exec("ALTER TABLE `$table` ADD COLUMN is_active TINYINT(1) DEFAULT 1");
            echo "Added 'is_active' to table '$table'.\n<br>";
        } else {
            echo "Table '$table' already has 'is_active'.\n<br>";
        }
    }

    echo "\n<br><b>Sync Migrations Completed Successfully!</b>\n";
} catch (Exception $e) {
    echo "\n<br><b style='color:red;'>Migration Error: " . htmlspecialchars($e->getMessage()) . "</b>\n";
}
?>
