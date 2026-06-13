<?php
require_once '../../includes/auth.php';
/**
 * Save Daily Record - दैनिक रिकॉर्ड सहेजें
 */
require_once '../../config/db.php';
requireLogin();
csrf_verify();
if (isSwayamsevak()) {
    header('Location: ../../pages/swayamsevak_dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../pages/daily_record.php');
    exit;
}

$inputs = getRequestInputs();
$recordId = $inputs['record_id'] ?? null;
$recordDate = $inputs['record_date'] ?? date('Y-m-d');
$yugabdh = trim($inputs['yugabdh'] ?? '');
$vikram_samvat = trim($inputs['vikram_samvat'] ?? '');
$shaka_samvat = trim($inputs['shaka_samvat'] ?? '');
$hindi_month = trim($inputs['hindi_month'] ?? '');
$paksh = trim($inputs['paksh'] ?? '');
$tithi = trim($inputs['tithi'] ?? '');
$customMessage = trim($inputs['custom_message'] ?? '');
$attendanceData = $inputs['attendance'] ?? [];
$activityDone = $inputs['activity_done'] ?? [];
$conductedBy = $inputs['conducted_by'] ?? [];

$utsav = trim($inputs['utsav'] ?? '');

    // Auto-create utsav column if missing (run outside transaction to avoid implicit commit)
    try {
        $pdo->exec("ALTER TABLE daily_records ADD COLUMN utsav VARCHAR(255) DEFAULT NULL AFTER tithi");
    } catch (PDOException $e) { }

try {
    $pdo->beginTransaction();

    $shakhaId = getCurrentShakhaId();

    // Create or update daily record
    if ($recordId) {
        $stmt = $pdo->prepare("UPDATE daily_records SET yugabdh = ?, vikram_samvat = ?, shaka_samvat = ?, hindi_month = ?, paksh = ?, tithi = ?, utsav = ?, custom_message = ?, updated_at = NOW() WHERE id = ? AND shakha_id = ?");
        $stmt->execute([$yugabdh, $vikram_samvat, $shaka_samvat, $hindi_month, $paksh, $tithi, $utsav, $customMessage, $recordId, $shakhaId]);
    } else {
        // Check if date already exists
        $stmt = $pdo->prepare("SELECT id FROM daily_records WHERE record_date = ? AND shakha_id = ?");
        $stmt->execute([$recordDate, $shakhaId]);
        $existing = $stmt->fetch();

        if ($existing) {
            $recordId = $existing['id'];
            $stmt = $pdo->prepare("UPDATE daily_records SET yugabdh = ?, vikram_samvat = ?, shaka_samvat = ?, hindi_month = ?, paksh = ?, tithi = ?, utsav = ?, custom_message = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$yugabdh, $vikram_samvat, $shaka_samvat, $hindi_month, $paksh, $tithi, $utsav, $customMessage, $recordId]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO daily_records (record_date, yugabdh, vikram_samvat, shaka_samvat, hindi_month, paksh, tithi, utsav, custom_message, shakha_id, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$recordDate, $yugabdh, $vikram_samvat, $shaka_samvat, $hindi_month, $paksh, $tithi, $utsav, $customMessage, $shakhaId]);
            $recordId = $pdo->lastInsertId();
        }
    }

    // Clear old attendance and activities
    $pdo->prepare("DELETE FROM attendance WHERE daily_record_id = ?")->execute([$recordId]);
    $pdo->prepare("DELETE FROM daily_activities WHERE daily_record_id = ?")->execute([$recordId]);

    // Save attendance
    $stmt = $pdo->prepare("SELECT id FROM swayamsevaks WHERE is_active = 1 AND shakha_id = ?");
    $stmt->execute([$shakhaId]);
    $allSwayamsevaks = $stmt->fetchAll();

    $attStmt = $pdo->prepare("INSERT INTO attendance (daily_record_id, swayamsevak_id, is_present, updated_at) VALUES (?, ?, ?, NOW())");
    foreach ($allSwayamsevaks as $s) {
        $isPresent = isset($attendanceData[$s['id']]) ? 1 : 0;
        $attStmt->execute([$recordId, $s['id'], $isPresent]);
    }

    // Save activities
    $stmt = $pdo->prepare("SELECT id FROM activities WHERE is_active = 1 AND (shakha_id IS NULL OR shakha_id = ?)");
    $stmt->execute([$shakhaId]);
    $allActivities = $stmt->fetchAll();

    $actStmt = $pdo->prepare("INSERT INTO daily_activities (daily_record_id, activity_id, is_done, conducted_by, updated_at) VALUES (?, ?, ?, ?, NOW())");
    foreach ($allActivities as $act) {
        $isDone = isset($activityDone[$act['id']]) ? 1 : 0;
        $conductor = !empty($conductedBy[$act['id']]) ? intval($conductedBy[$act['id']]) : null;
        $actStmt->execute([$recordId, $act['id'], $isDone, $conductor]);
    }

    $pdo->commit();
    header("Location: ../../pages/snapshot.php?id=$recordId&msg=saved");
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Save Daily Record Error: " . $e->getMessage());
    $errMsg = urlencode($e->getMessage());
    header("Location: ../../pages/daily_record.php?date=$recordDate&error=1&msg=" . $errMsg);
    exit;
}
