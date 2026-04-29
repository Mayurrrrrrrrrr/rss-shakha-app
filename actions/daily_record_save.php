<?php
/**
 * Save Daily Record - दैनिक रिकॉर्ड सहेजें
 */
require_once '../includes/auth.php';
require_once '../config/db.php';
requireLogin();
csrf_verify();
if (isSwayamsevak()) {
    header('Location: ../pages/swayamsevak_dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/daily_record.php');
    exit;
}

$recordId = $_POST['record_id'] ?? null;
$recordDate = $_POST['record_date'] ?? date('Y-m-d');
$yugabdh = trim($_POST['yugabdh'] ?? '');
$vikram_samvat = trim($_POST['vikram_samvat'] ?? '');
$shaka_samvat = trim($_POST['shaka_samvat'] ?? '');
$hindi_month = trim($_POST['hindi_month'] ?? '');
$paksh = trim($_POST['paksh'] ?? '');
$tithi = trim($_POST['tithi'] ?? '');
$customMessage = trim($_POST['custom_message'] ?? '');
$attendanceData = $_POST['attendance'] ?? [];
$activityDone = $_POST['activity_done'] ?? [];
$conductedBy = $_POST['conducted_by'] ?? [];

$utsav = trim($_POST['utsav'] ?? '');

try {
    $pdo->beginTransaction();

    // Auto-create utsav column if missing
    try {
        $pdo->exec("ALTER TABLE daily_records ADD COLUMN utsav VARCHAR(255) DEFAULT NULL AFTER tithi");
    } catch (PDOException $e) { }

    $shakhaId = getCurrentShakhaId();

    // Create or update daily record
    if ($recordId) {
        $stmt = $pdo->prepare("UPDATE daily_records SET yugabdh = ?, vikram_samvat = ?, shaka_samvat = ?, hindi_month = ?, paksh = ?, tithi = ?, utsav = ?, custom_message = ? WHERE id = ? AND shakha_id = ?");
        $stmt->execute([$yugabdh, $vikram_samvat, $shaka_samvat, $hindi_month, $paksh, $tithi, $utsav, $customMessage, $recordId, $shakhaId]);
    } else {
        // Check if date already exists
        $stmt = $pdo->prepare("SELECT id FROM daily_records WHERE record_date = ? AND shakha_id = ?");
        $stmt->execute([$recordDate, $shakhaId]);
        $existing = $stmt->fetch();

        if ($existing) {
            $recordId = $existing['id'];
            $stmt = $pdo->prepare("UPDATE daily_records SET yugabdh = ?, vikram_samvat = ?, shaka_samvat = ?, hindi_month = ?, paksh = ?, tithi = ?, utsav = ?, custom_message = ? WHERE id = ?");
            $stmt->execute([$yugabdh, $vikram_samvat, $shaka_samvat, $hindi_month, $paksh, $tithi, $utsav, $customMessage, $recordId]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO daily_records (record_date, yugabdh, vikram_samvat, shaka_samvat, hindi_month, paksh, tithi, utsav, custom_message, shakha_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
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

    $attStmt = $pdo->prepare("INSERT INTO attendance (daily_record_id, swayamsevak_id, is_present) VALUES (?, ?, ?)");
    foreach ($allSwayamsevaks as $s) {
        $isPresent = isset($attendanceData[$s['id']]) ? 1 : 0;
        $attStmt->execute([$recordId, $s['id'], $isPresent]);
    }

    // Save activities
    $stmt = $pdo->prepare("SELECT id FROM activities WHERE is_active = 1 AND (shakha_id IS NULL OR shakha_id = ?)");
    $stmt->execute([$shakhaId]);
    $allActivities = $stmt->fetchAll();

    $actStmt = $pdo->prepare("INSERT INTO daily_activities (daily_record_id, activity_id, is_done, conducted_by) VALUES (?, ?, ?, ?)");
    foreach ($allActivities as $act) {
        $isDone = isset($activityDone[$act['id']]) ? 1 : 0;
        $conductor = !empty($conductedBy[$act['id']]) ? intval($conductedBy[$act['id']]) : null;
        $actStmt->execute([$recordId, $act['id'], $isDone, $conductor]);
    }

    $pdo->commit();
    header("Location: ../pages/record_detail.php?id=$recordId&msg=saved");
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    $errMsg = urlencode($e->getMessage());
    header("Location: ../pages/daily_record.php?date=$recordDate&error=1&msg=" . $errMsg);
    exit;
}
