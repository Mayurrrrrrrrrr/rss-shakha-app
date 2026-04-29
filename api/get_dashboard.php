<?php
require_once '../includes/auth.php';
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, 'Invalid request method');
}

$data = json_decode(file_get_contents("php://input"));
$shakhaId = $data->shakha_id ?? null;

if (!$shakhaId) {
    sendResponse(false, 'Shakha ID is required');
}

try {
    // Total Swayamsevaks
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM swayamsevaks WHERE is_active = 1 AND shakha_id = ?");
    $stmt->execute([$shakhaId]);
    $totalSwayamsevaks = $stmt->fetchColumn();

    // Total Activities
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM activities WHERE is_active = 1 AND (shakha_id IS NULL OR shakha_id = ?)");
    $stmt->execute([$shakhaId]);
    $totalActivities = $stmt->fetchColumn();

    // Total Records
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM daily_records WHERE shakha_id = ?");
    $stmt->execute([$shakhaId]);
    $totalRecords = $stmt->fetchColumn();

    // Has Today's Record?
    $todayRecord = $pdo->prepare("SELECT id FROM daily_records WHERE record_date = CURDATE() AND shakha_id = ?");
    $todayRecord->execute([$shakhaId]);
    $hasTodayRecord = $todayRecord->fetch() ? true : false;

    // Recent Records
    $recentRecordsStmt = $pdo->prepare("SELECT dr.*, 
        (SELECT COUNT(*) FROM attendance a WHERE a.daily_record_id = dr.id AND a.is_present = 1) as present_count,
        (SELECT COUNT(*) FROM daily_activities da WHERE da.daily_record_id = dr.id AND da.is_done = 1) as activities_done
        FROM daily_records dr 
        WHERE dr.shakha_id = ?
        ORDER BY record_date DESC LIMIT 5");
    $recentRecordsStmt->execute([$shakhaId]);
    $recentRecords = $recentRecordsStmt->fetchAll(PDO::FETCH_ASSOC);

    $responseData = [
        'total_swayamsevaks' => $totalSwayamsevaks,
        'total_activities' => $totalActivities,
        'total_records' => $totalRecords,
        'has_today_record' => $hasTodayRecord,
        'recent_records' => $recentRecords
    ];

    sendResponse(true, 'Dashboard data fetched', $responseData);

} catch (Exception $e) {
    sendResponse(false, 'Database Error: ' . $e->getMessage());
}
