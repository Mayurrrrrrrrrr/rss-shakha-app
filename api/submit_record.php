<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, 'Invalid request method');
}

$data = json_decode(file_get_contents("php://input"), true);
$shakhaId = $data['shakha_id'] ?? null;

if (!$shakhaId || empty($data['record_date'])) {
    sendResponse(false, 'Shakha ID and Record Date are required');
}

try {
    $pdo->beginTransaction();

    $recordDate = $data['record_date'];
    $userId = $data['user_id'] ?? null;
    $customMessage = trim($data['custom_message'] ?? '');
    $attendance = $data['attendance'] ?? []; // Map of [swayamsevak_id => is_present]
    $activities = $data['activities'] ?? []; // Map of [activity_id => [is_done, conducted_by]]

    // See if record already exists
    $stmt = $pdo->prepare("SELECT id FROM daily_records WHERE record_date = ? AND shakha_id = ?");
    $stmt->execute([$recordDate, $shakhaId]);
    $existing = $stmt->fetch();

    if ($existing) {
        $recordId = $existing['id'];
        $stmt = $pdo->prepare("UPDATE daily_records 
                               SET yugabdh = ?, vikram_samvat = ?, hindi_month = ?, paksh = ?, tithi = ?, custom_message = ?, updated_by = ? 
                               WHERE id = ?");
        $stmt->execute([
            $data['yugabdh'] ?? null,
            $data['vikram_samvat'] ?? null,
            $data['hindi_month'] ?? null,
            $data['paksh'] ?? null,
            $data['tithi'] ?? null,
            $customMessage,
            $userId,
            $recordId
        ]);

        // Clear children
        $pdo->prepare("DELETE FROM attendance WHERE daily_record_id = ?")->execute([$recordId]);
        $pdo->prepare("DELETE FROM daily_activities WHERE daily_record_id = ?")->execute([$recordId]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO daily_records (shakha_id, record_date, yugabdh, vikram_samvat, hindi_month, paksh, tithi, custom_message, created_by) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $shakhaId,
            $recordDate,
            $data['yugabdh'] ?? null,
            $data['vikram_samvat'] ?? null,
            $data['hindi_month'] ?? null,
            $data['paksh'] ?? null,
            $data['tithi'] ?? null,
            $customMessage,
            $userId
        ]);
        $recordId = $pdo->lastInsertId();
    }

    // Insert Attendance
    if (!empty($attendance)) {
        $attStmt = $pdo->prepare("INSERT INTO attendance (daily_record_id, swayamsevak_id, is_present) VALUES (?, ?, ?)");
        foreach ($attendance as $swayamsevakId => $isPresent) {
            $attStmt->execute([$recordId, $swayamsevakId, $isPresent ? 1 : 0]);
        }
    }

    // Insert Activities
    if (!empty($activities)) {
        $actStmt = $pdo->prepare("INSERT INTO daily_activities (daily_record_id, activity_id, is_done, conducted_by) VALUES (?, ?, ?, ?)");
        foreach ($activities as $actId => $details) {
            $isDone = !empty($details['is_done']) ? 1 : 0;
            $conductedBy = !empty($details['conducted_by']) ? $details['conducted_by'] : null;
            $actStmt->execute([$recordId, $actId, $isDone, $conductedBy]);
        }
    }

    $pdo->commit();
    sendResponse(true, 'Record saved successfully');

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    sendResponse(false, 'Database Error: ' . $e->getMessage());
}
