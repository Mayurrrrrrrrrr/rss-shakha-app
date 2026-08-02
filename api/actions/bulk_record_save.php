<?php
require_once '../../includes/auth.php';
/**
 * Save Bulk Daily Records - थोक दैनिक रिकॉर्ड सहेजें
 */
require_once '../../config/db.php';
require_once '../../includes/PanchangHelper.php';
requireLogin();
csrf_verify();

if (isSwayamsevak()) {
    header('Location: ../../pages/swayamsevak_dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../pages/bulk_record.php');
    exit;
}

$startDate = $_POST['start_date'] ?? date('Y-m-d');
$numDays = intval($_POST['num_days'] ?? 7);
$recordsData = $_POST['record'] ?? [];

$shakhaId = getCurrentShakhaId();

try {
    $pdo->beginTransaction();

    // Fetch active activities to build entries correctly
    $stmt = $pdo->prepare("SELECT id FROM activities WHERE is_active = 1 AND (shakha_id IS NULL OR shakha_id = ?)");
    $stmt->execute([$shakhaId]);
    $allActivities = $stmt->fetchAll();

    // Fetch all active swayamsevaks for attendance initialization if needed
    $stmt = $pdo->prepare("SELECT id FROM swayamsevaks WHERE is_active = 1 AND shakha_id = ?");
    $stmt->execute([$shakhaId]);
    $allSwayamsevaks = $stmt->fetchAll();

    foreach ($recordsData as $date => $data) {
        // Validate date format
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            continue;
        }

        $notes = trim($data['notes'] ?? '');

        // Check if daily record already exists for this date
        $stmt = $pdo->prepare("SELECT id FROM daily_records WHERE record_date = ? AND shakha_id = ?");
        $stmt->execute([$date, $shakhaId]);
        $existing = $stmt->fetch();

        if ($existing) {
            $recordId = $existing['id'];
            // Update notes/custom_message
            $stmt = $pdo->prepare("UPDATE daily_records SET custom_message = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$notes, $recordId]);
        } else {
            // Auto fetch panchang
            $autoPanchang = PanchangHelper::getForDate($pdo, $date, $shakhaId);
            $yugabdh = $autoPanchang['yugabdha'] ?? '';
            $vikram_samvat = $autoPanchang['vikram_samvat'] ?? '';
            $shaka_samvat = $autoPanchang['shaka_samvat'] ?? '';
            $hindi_month = $autoPanchang['hindi_month'] ?? '';
            $paksh = $autoPanchang['paksha'] ?? '';
            $tithi = $autoPanchang['tithi'] ?? '';
            $utsav = $autoPanchang['utsav'] ?? '';

            // Insert new daily record
            $stmt = $pdo->prepare("INSERT INTO daily_records (record_date, yugabdh, vikram_samvat, shaka_samvat, hindi_month, paksh, tithi, utsav, custom_message, shakha_id, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$date, $yugabdh, $vikram_samvat, $shaka_samvat, $hindi_month, $paksh, $tithi, $utsav, $notes, $shakhaId]);
            $recordId = $pdo->lastInsertId();
        }

        // 1. Save Activities
        // Clear old daily activities for this record
        $pdo->prepare("DELETE FROM daily_activities WHERE daily_record_id = ?")->execute([$recordId]);

        // Insert new ones
        $actStmt = $pdo->prepare("INSERT INTO daily_activities (daily_record_id, activity_id, is_done, conducted_by, updated_at) VALUES (?, ?, ?, ?, NOW())");
        foreach ($allActivities as $act) {
            $isDone = isset($data['activity_done'][$act['id']]) ? 1 : 0;
            $conductor = !empty($data['conducted_by'][$act['id']]) ? intval($data['conducted_by'][$act['id']]) : null;
            $actStmt->execute([$recordId, $act['id'], $isDone, $conductor]);
        }

        // 2. Save Attendance
        // Clear old attendance for this record
        $pdo->prepare("DELETE FROM attendance WHERE daily_record_id = ?")->execute([$recordId]);

        // Insert attendance
        $attData = $data['attendance'] ?? [];
        $attStmt = $pdo->prepare("INSERT INTO attendance (daily_record_id, swayamsevak_id, is_present, updated_at) VALUES (?, ?, ?, NOW())");
        foreach ($allSwayamsevaks as $s) {
            $isPresent = isset($attData[$s['id']]) ? 1 : 0;
            $attStmt->execute([$recordId, $s['id'], $isPresent]);
        }
    }

    $pdo->commit();
    header("Location: ../../pages/bulk_record.php?start_date=$startDate&num_days=$numDays&msg=saved");
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Save Bulk Records Error: " . $e->getMessage());
    $errMsg = urlencode($e->getMessage());
    header("Location: ../../pages/bulk_record.php?start_date=$startDate&num_days=$numDays&error=1&msg=" . $errMsg);
    exit;
}
