<?php
/**
 * Sync Pull API - डेटाबेस से अपडेट खींचें
 */
require_once __DIR__ . '/auth_api.php';
require_once __DIR__ . '/../config/db.php';

// Authenticate user request and get context
$userContext = authenticateAPIRequest();
$shakhaId = $userContext['shakha_id'];

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

$lastSync = $_GET['last_sync_timestamp'] ?? '1970-01-01 00:00:00';

// Sanitize timestamp input (basic check)
if (!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $lastSync)) {
    $lastSync = '1970-01-01 00:00:00';
}

$response = [
    'success' => true,
    'server_timestamp' => date('Y-m-d H:i:s'),
    'data' => []
];

try {
    // 1. Shakhas
    $stmt = $pdo->prepare("SELECT * FROM shakhas WHERE updated_at > ? AND id = ?");
    $stmt->execute([$lastSync, $shakhaId]);
    $response['data']['shakhas'] = $stmt->fetchAll();

    // 2. Swayamsevaks
    $stmt = $pdo->prepare("SELECT * FROM swayamsevaks WHERE updated_at > ? AND shakha_id = ?");
    $stmt->execute([$lastSync, $shakhaId]);
    $response['data']['swayamsevaks'] = $stmt->fetchAll();

    // 3. Daily Records
    $stmt = $pdo->prepare("SELECT * FROM daily_records WHERE updated_at > ? AND shakha_id = ?");
    $stmt->execute([$lastSync, $shakhaId]);
    $response['data']['daily_records'] = $stmt->fetchAll();

    // 4. Attendance
    $stmt = $pdo->prepare("SELECT a.* FROM attendance a 
                           INNER JOIN daily_records d ON a.daily_record_id = d.id 
                           WHERE a.updated_at > ? AND d.shakha_id = ?");
    $stmt->execute([$lastSync, $shakhaId]);
    $response['data']['attendance'] = $stmt->fetchAll();

    // 5. Activities
    $stmt = $pdo->prepare("SELECT * FROM activities WHERE updated_at > ? AND (shakha_id = ? OR shakha_id IS NULL)");
    $stmt->execute([$lastSync, $shakhaId]);
    $response['data']['activities'] = $stmt->fetchAll();

    // 6. Daily Activities
    $stmt = $pdo->prepare("SELECT da.* FROM daily_activities da 
                           INNER JOIN daily_records d ON da.daily_record_id = d.id 
                           WHERE da.updated_at > ? AND d.shakha_id = ?");
    $stmt->execute([$lastSync, $shakhaId]);
    $response['data']['daily_activities'] = $stmt->fetchAll();

    // 7. Timetable Defaults
    $stmt = $pdo->prepare("SELECT * FROM timetable_defaults WHERE updated_at > ? AND shakha_id = ?");
    $stmt->execute([$lastSync, $shakhaId]);
    $response['data']['timetable_defaults'] = $stmt->fetchAll();

    // 8. Timetable Overrides
    $stmt = $pdo->prepare("SELECT * FROM timetable_overrides WHERE updated_at > ? AND shakha_id = ?");
    $stmt->execute([$lastSync, $shakhaId]);
    $response['data']['timetable_overrides'] = $stmt->fetchAll();

    // 9. Events
    $stmt = $pdo->prepare("SELECT * FROM events WHERE updated_at > ? AND (shakha_id = ? OR shakha_id IS NULL)");
    $stmt->execute([$lastSync, $shakhaId]);
    $response['data']['events'] = $stmt->fetchAll();

    // 10. Subhashits
    $stmt = $pdo->prepare("SELECT * FROM subhashits WHERE updated_at > ? AND (shakha_id = ? OR shakha_id IS NULL)");
    $stmt->execute([$lastSync, $shakhaId]);
    $response['data']['subhashits'] = $stmt->fetchAll();

    // 11. Amrit Vachan
    $stmt = $pdo->prepare("SELECT * FROM amrit_vachan WHERE updated_at > ? AND (shakha_id = ? OR shakha_id IS NULL)");
    $stmt->execute([$lastSync, $shakhaId]);
    $response['data']['amrit_vachan'] = $stmt->fetchAll();

    // 12. Geet
    $stmt = $pdo->prepare("SELECT * FROM geet WHERE updated_at > ? AND (shakha_id = ? OR shakha_id IS NULL)");
    $stmt->execute([$lastSync, $shakhaId]);
    $response['data']['geet'] = $stmt->fetchAll();

    // 13. Ghoshnayein
    $stmt = $pdo->prepare("SELECT * FROM ghoshnayein WHERE updated_at > ? AND (shakha_id = ? OR shakha_id IS NULL)");
    $stmt->execute([$lastSync, $shakhaId]);
    $response['data']['ghoshnayein'] = $stmt->fetchAll();

    echo json_encode($response, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error: ' . $e->getMessage()
    ]);
}
?>
