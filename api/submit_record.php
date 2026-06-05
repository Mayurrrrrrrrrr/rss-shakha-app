<?php
require_once '../includes/auth.php';
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, 'Invalid request method');
}

$data = json_decode(file_get_contents("php://input"), true);
if (!is_array($data)) {
    sendResponse(false, 'Invalid input data provided.');
}

// Validate shakha_id (expected integer)
$shakhaIdRaw = $data['shakha_id'] ?? null;
$shakhaId = filter_var($shakhaIdRaw, FILTER_VALIDATE_INT);
if ($shakhaId === false) {
    sendResponse(false, 'Invalid input data provided.');
}

// Validate record_date
$recordDateRaw = $data['record_date'] ?? '';
if (!is_string($recordDateRaw) || empty(trim($recordDateRaw))) {
    sendResponse(false, 'Invalid input data provided.');
}
$recordDate = htmlspecialchars(trim($recordDateRaw), ENT_QUOTES, 'UTF-8');

try {
    $pdo->beginTransaction();

    $userId = null;
    if (isset($data['user_id']) && $data['user_id'] !== '') {
        $userId = filter_var($data['user_id'], FILTER_VALIDATE_INT);
        if ($userId === false) {
            sendResponse(false, 'Invalid input data provided.');
        }
    }

    $customMessage = isset($data['custom_message']) && is_string($data['custom_message']) ? htmlspecialchars(trim($data['custom_message']), ENT_QUOTES, 'UTF-8') : '';
    
    // Validate Attendance array keys and values
    $attendance = [];
    if (isset($data['attendance'])) {
        if (!is_array($data['attendance'])) {
            sendResponse(false, 'Invalid input data provided.');
        }
        foreach ($data['attendance'] as $swayamsevakIdRaw => $isPresent) {
            $swayamsevakId = filter_var($swayamsevakIdRaw, FILTER_VALIDATE_INT);
            if ($swayamsevakId === false) {
                sendResponse(false, 'Invalid input data provided.');
            }
            $attendance[$swayamsevakId] = $isPresent ? 1 : 0;
        }
    }

    // Validate Activities array keys and structure
    $activities = [];
    if (isset($data['activities'])) {
        if (!is_array($data['activities'])) {
            sendResponse(false, 'Invalid input data provided.');
        }
        foreach ($data['activities'] as $actIdRaw => $details) {
            $actId = filter_var($actIdRaw, FILTER_VALIDATE_INT);
            if ($actId === false || !is_array($details)) {
                sendResponse(false, 'Invalid input data provided.');
            }
            $isDone = !empty($details['is_done']) ? 1 : 0;
            $conductedBy = null;
            if (!empty($details['conducted_by'])) {
                $conductedByVal = filter_var($details['conducted_by'], FILTER_VALIDATE_INT);
                if ($conductedByVal === false) {
                    sendResponse(false, 'Invalid input data provided.');
                }
                $conductedBy = $conductedByVal;
            }
            $activities[$actId] = [
                'is_done' => $isDone,
                'conducted_by' => $conductedBy
            ];
        }
    }

    // Sanitize other string inputs
    $yugabdh = isset($data['yugabdh']) && is_string($data['yugabdh']) ? htmlspecialchars(trim($data['yugabdh']), ENT_QUOTES, 'UTF-8') : null;
    $vikram_samvat = isset($data['vikram_samvat']) && is_string($data['vikram_samvat']) ? htmlspecialchars(trim($data['vikram_samvat']), ENT_QUOTES, 'UTF-8') : null;
    $hindi_month = isset($data['hindi_month']) && is_string($data['hindi_month']) ? htmlspecialchars(trim($data['hindi_month']), ENT_QUOTES, 'UTF-8') : null;
    $paksh = isset($data['paksh']) && is_string($data['paksh']) ? htmlspecialchars(trim($data['paksh']), ENT_QUOTES, 'UTF-8') : null;
    $tithi = isset($data['tithi']) && is_string($data['tithi']) ? htmlspecialchars(trim($data['tithi']), ENT_QUOTES, 'UTF-8') : null;

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
            $yugabdh,
            $vikram_samvat,
            $hindi_month,
            $paksh,
            $tithi,
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
            $yugabdh,
            $vikram_samvat,
            $hindi_month,
            $paksh,
            $tithi,
            $customMessage,
            $userId
        ]);
        $recordId = $pdo->lastInsertId();
    }

    // Insert Attendance
    if (!empty($attendance)) {
        $attStmt = $pdo->prepare("INSERT INTO attendance (daily_record_id, swayamsevak_id, is_present) VALUES (?, ?, ?)");
        foreach ($attendance as $swayamsevakId => $isPresent) {
            $attStmt->execute([$recordId, $swayamsevakId, $isPresent]);
        }
    }

    // Insert Activities
    if (!empty($activities)) {
        $actStmt = $pdo->prepare("INSERT INTO daily_activities (daily_record_id, activity_id, is_done, conducted_by) VALUES (?, ?, ?, ?)");
        foreach ($activities as $actId => $details) {
            $actStmt->execute([$recordId, $actId, $details['is_done'], $details['conducted_by']]);
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
