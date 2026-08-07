<?php
/**
 * Server Health Check — verifies database connectivity, required tables, and PHP configuration.
 * Access: https://sanghasthan.yuktaa.com/api/v1/health_check.php
 */
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");

$results = [
    'status' => 'ok',
    'timestamp' => date('Y-m-d H:i:s T'),
    'php_version' => PHP_VERSION,
    'checks' => []
];

// 1. Check PHP extensions
$requiredExtensions = ['pdo', 'pdo_mysql', 'json', 'mbstring', 'session'];
foreach ($requiredExtensions as $ext) {
    $results['checks']["php_ext_$ext"] = extension_loaded($ext) ? '✅' : '❌ MISSING';
    if (!extension_loaded($ext)) $results['status'] = 'error';
}

// 2. Check DB connection
try {
    require_once __DIR__ . '/../../config/db.php';
    $results['checks']['database_connection'] = '✅ Connected to ' . DB_NAME;
} catch (Exception $e) {
    $results['checks']['database_connection'] = '❌ ' . $e->getMessage();
    $results['status'] = 'error';
    echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// 3. Check required tables
$requiredTables = [
    // Core Shakha tables
    'admin_users', 'swayamsevaks', 'shakhas', 'login_attempts',
    'daily_records', 'attendance', 'activities', 'daily_activities',
    'timetable_defaults', 'events', 'notices', 'personalities',
    'subhashits', 'amrit_vachan', 'geet', 'ghoshnayein',
    'panchang_data', 'ai_content_cache',
    // Event management tables
    'em_organizers', 'em_events', 'em_event_organizers',
    'em_participants', 'em_attendance_sessions', 'em_attendance',
    'em_rooms', 'em_room_allotments', 'em_meals', 'em_meal_tracking',
    'em_work_categories', 'em_work_assignments', 'em_login_attempts',
    'em_schedule', 'em_duty_assignments'
];

$stmt = $pdo->query("SHOW TABLES");
$existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);

foreach ($requiredTables as $table) {
    if (in_array($table, $existingTables)) {
        $results['checks']["table_$table"] = '✅';
    } else {
        $results['checks']["table_$table"] = '❌ MISSING';
        $results['status'] = 'warning';
    }
}

// 4. Verify critical column existence
$criticalColumns = [
    'em_login_attempts' => ['ip', 'attempted_at'],
    'em_organizers' => ['id', 'username', 'password', 'name', 'role', 'status'],
    'admin_users' => ['id', 'username', 'password', 'name', 'role', 'shakha_id'],
    'swayamsevaks' => ['id', 'username', 'password', 'name', 'shakha_id', 'is_active', 'is_deleted'],
];

foreach ($criticalColumns as $table => $columns) {
    if (!in_array($table, $existingTables)) continue;
    
    $stmt = $pdo->query("DESCRIBE `$table`");
    $tableColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($columns as $col) {
        $key = "column_{$table}.{$col}";
        if (in_array($col, $tableColumns)) {
            $results['checks'][$key] = '✅';
        } else {
            $results['checks'][$key] = '❌ MISSING';
            $results['status'] = 'error';
        }
    }
}

// 5. Session directory check
$sessionPath = session_save_path() ?: sys_get_temp_dir();
$results['checks']['session_dir'] = is_writable($sessionPath) ? "✅ Writable ($sessionPath)" : "❌ NOT writable ($sessionPath)";

// 6. Check .env file
$envPath = __DIR__ . '/../../.env';
$results['checks']['env_file'] = file_exists($envPath) ? '✅' : '⚠️ Missing (.env)';

echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
