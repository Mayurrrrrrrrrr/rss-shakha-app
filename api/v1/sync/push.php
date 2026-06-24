<?php
/**
 * Sync Push API - स्थानीय रिकॉर्ड्स सर्वर पर अपलोड करें (Versioned API)
 */
require_once __DIR__ . '/auth_api.php';
require_once __DIR__ . '/../../../config/db.php';

// Authenticate user request
$userContext = authenticateAPIRequest();
$shakhaId = $userContext['shakha_id'];

if (($userContext['user_type'] ?? '') === 'swayamsevak') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized: Swayamsevaks cannot push updates.']);
    exit;
}

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
    exit;
}

$response = [
    'success' => true,
    'swayamsevak_mappings' => [],
    'record_mappings' => [],
    'errors' => []
];

// Auto-create column in case missing (run outside transaction to avoid implicit commits)
try {
    $pdo->exec("ALTER TABLE daily_records ADD COLUMN utsav VARCHAR(255) DEFAULT NULL AFTER tithi");
} catch (PDOException $e) { }

try {
    $pdo->beginTransaction();

    // 1. Process Swayamsevaks created/edited offline
    $swayamsevakMappings = []; // Maps offline_id -> server_id
    if (isset($input['swayamsevaks']) && is_array($input['swayamsevaks'])) {
        foreach ($input['swayamsevaks'] as $s) {
            $offlineId = $s['offline_id'] ?? null;
            $name = trim($s['name'] ?? '');
            $address = trim($s['address'] ?? '');
            $phone = trim($s['phone'] ?? '');
            $age = isset($s['age']) ? intval($s['age']) : null;
            $category = $s['category'] ?? 'Tarun';
            $gat = trim($s['gat'] ?? '');
            if ($gat === '') $gat = null;
            $isGatNayak = isset($s['is_gat_nayak']) ? intval($s['is_gat_nayak']) : 0;
            $isActive = isset($s['is_active']) ? intval($s['is_active']) : 1;

            if (empty($name)) {
                $response['errors'][] = "Swayamsevak name is empty";
                continue;
            }

            // Check if it is a new Swayamsevak or an edit
            if (!$offlineId || strpos((string)$offlineId, 'temp_') === 0 || intval($offlineId) <= 0) {
                // Insert new record
                $clientCreatedAt = $s['created_at'] ?? date('Y-m-d H:i:s');
                $clientUpdatedAt = $s['updated_at'] ?? date('Y-m-d H:i:s');
                $isDeleted = isset($s['is_deleted']) ? intval($s['is_deleted']) : 0;

                $stmt = $pdo->prepare("INSERT INTO swayamsevaks (name, address, phone, age, shakha_id, category, gat, is_gat_nayak, is_active, is_deleted, created_at, updated_at) 
                                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $address, $phone, $age, $shakhaId, $category, $gat, $isGatNayak, $isActive, $isDeleted, $clientCreatedAt, $clientUpdatedAt]);
                $serverId = $pdo->lastInsertId();
                if ($offlineId) {
                    $swayamsevakMappings[$offlineId] = (int)$serverId;
                }
            } else {
                // Update existing record - Last Write Wins
                $serverId = intval($offlineId);
                $clientUpdatedAt = $s['updated_at'] ?? date('Y-m-d H:i:s');
                $isDeleted = isset($s['is_deleted']) ? intval($s['is_deleted']) : 0;

                // Fetch server's updated_at
                $existStmt = $pdo->prepare("SELECT updated_at FROM swayamsevaks WHERE id = ? AND shakha_id = ?");
                $existStmt->execute([$serverId, $shakhaId]);
                $serverRow = $existStmt->fetch();

                if (!$serverRow || strtotime($clientUpdatedAt) > strtotime($serverRow['updated_at'])) {
                    $stmt = $pdo->prepare("UPDATE swayamsevaks SET name = ?, address = ?, phone = ?, age = ?, category = ?, gat = ?, is_gat_nayak = ?, is_active = ?, is_deleted = ?, updated_at = ? 
                                           WHERE id = ? AND shakha_id = ?");
                    $stmt->execute([$name, $address, $phone, $age, $category, $gat, $isGatNayak, $isActive, $isDeleted, $clientUpdatedAt, $serverId, $shakhaId]);
                }
                $swayamsevakMappings[$offlineId] = $serverId;
            }
        }
    }
    $response['swayamsevak_mappings'] = (object)$swayamsevakMappings;

    // 2. Process Daily Records saved offline
    $recordMappings = [];
    if (isset($input['daily_records']) && is_array($input['daily_records'])) {
        foreach ($input['daily_records'] as $rec) {
            $offlineId = $rec['offline_id'] ?? null;
            $recordDate = $rec['record_date'] ?? date('Y-m-d');
            $yugabdh = trim($rec['yugabdh'] ?? '');
            $vikram_samvat = trim($rec['vikram_samvat'] ?? '');
            $shaka_samvat = trim($rec['shaka_samvat'] ?? '');
            $hindi_month = trim($rec['hindi_month'] ?? '');
            $paksh = trim($rec['paksh'] ?? '');
            $tithi = trim($rec['tithi'] ?? '');
            $utsav = trim($rec['utsav'] ?? '');
            $customMessage = trim($rec['custom_message'] ?? '');
            $attendanceData = $rec['attendance'] ?? [];
            $activitiesData = $rec['activities'] ?? [];
            $isActive = isset($rec['is_active']) ? intval($rec['is_active']) : 1;

            // Insert or Update daily record base - Last Write Wins
            $clientUpdatedAt = $rec['updated_at'] ?? date('Y-m-d H:i:s');
            $clientCreatedAt = $rec['created_at'] ?? date('Y-m-d H:i:s');
            $isDeleted = isset($rec['is_deleted']) ? intval($rec['is_deleted']) : 0;

            $stmt = $pdo->prepare("SELECT id, updated_at FROM daily_records WHERE record_date = ? AND shakha_id = ?");
            $stmt->execute([$recordDate, $shakhaId]);
            $existing = $stmt->fetch();

            if ($existing) {
                $recordId = $existing['id'];
                if (strtotime($clientUpdatedAt) > strtotime($existing['updated_at'])) {
                    $stmt = $pdo->prepare("UPDATE daily_records SET yugabdh = ?, vikram_samvat = ?, shaka_samvat = ?, hindi_month = ?, paksh = ?, tithi = ?, utsav = ?, custom_message = ?, is_active = ?, is_deleted = ?, updated_at = ? WHERE id = ?");
                    $stmt->execute([$yugabdh, $vikram_samvat, $shaka_samvat, $hindi_month, $paksh, $tithi, $utsav, $customMessage, $isActive, $isDeleted, $clientUpdatedAt, $recordId]);
                }
            } else {
                $stmt = $pdo->prepare("INSERT INTO daily_records (record_date, yugabdh, vikram_samvat, shaka_samvat, hindi_month, paksh, tithi, utsav, custom_message, shakha_id, is_active, is_deleted, created_at, updated_at) 
                                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$recordDate, $yugabdh, $vikram_samvat, $shaka_samvat, $hindi_month, $paksh, $tithi, $utsav, $customMessage, $shakhaId, $isActive, $isDeleted, $clientCreatedAt, $clientUpdatedAt]);
                $recordId = $pdo->lastInsertId();
            }

            if ($offlineId) {
                $recordMappings[$offlineId] = (int)$recordId;
            }

            // Save Attendance details
            $pdo->prepare("DELETE FROM attendance WHERE daily_record_id = ?")->execute([$recordId]);
            $attStmt = $pdo->prepare("INSERT INTO attendance (daily_record_id, swayamsevak_id, is_present, created_at, updated_at, is_deleted) VALUES (?, ?, ?, ?, ?, ?)");
            
            foreach ($attendanceData as $swayId => $isPresent) {
                $mappedSwayId = $swayId;
                if (isset($swayamsevakMappings[$swayId])) {
                    $mappedSwayId = $swayamsevakMappings[$swayId];
                }

                if (strpos((string)$mappedSwayId, 'temp_') === 0 || intval($mappedSwayId) <= 0) {
                    continue;
                }

                $attStmt->execute([$recordId, intval($mappedSwayId), $isPresent ? 1 : 0, $clientCreatedAt, $clientUpdatedAt, $isDeleted]);
            }

            // Save Activity details
            $pdo->prepare("DELETE FROM daily_activities WHERE daily_record_id = ?")->execute([$recordId]);
            $actStmt = $pdo->prepare("INSERT INTO daily_activities (daily_record_id, activity_id, is_done, conducted_by, created_at, updated_at, is_deleted) VALUES (?, ?, ?, ?, ?, ?, ?)");
            
            foreach ($activitiesData as $actId => $details) {
                $isDone = isset($details['is_done']) ? ($details['is_done'] ? 1 : 0) : 0;
                $conductor = isset($details['conducted_by']) ? $details['conducted_by'] : null;

                if ($conductor && isset($swayamsevakMappings[$conductor])) {
                    $conductor = $swayamsevakMappings[$conductor];
                }
                if ($conductor && (strpos((string)$conductor, 'temp_') === 0 || intval($conductor) <= 0)) {
                    $conductor = null;
                }

                $actStmt->execute([$recordId, intval($actId), $isDone, $conductor ? intval($conductor) : null, $clientCreatedAt, $clientUpdatedAt, $isDeleted]);
            }
        }
    }
    $response['record_mappings'] = (object)$recordMappings;

    $pdo->commit();
    echo json_encode($response);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Transaction failed: ' . $e->getMessage()
    ]);
}
?>
