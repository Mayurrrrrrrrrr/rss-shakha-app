<?php
require_once '../includes/auth.php';
require_once '../config/db.php';
csrf_verify();

if (!isLoggedIn() || (!isAdmin() && !isMukhyashikshak())) {
    header("Location: ../index.php");
    exit;
}

$shakhaId = getCurrentShakhaId();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $saveType = $_POST['save_type'] ?? ''; // 'default' or 'override'
    $slotsRaw = $_POST['slots'] ?? [];
    
    // Build clean slots array
    $slots = [];
    $startMins = $slotsRaw['start_min'] ?? [];
    $endMins = $slotsRaw['end_min'] ?? [];
    $topics = $slotsRaw['topic'] ?? [];
    
    for ($i = 0; $i < count($topics); $i++) {
        $s = floatval($startMins[$i] ?? 0);
        $e = floatval($endMins[$i] ?? 0);
        $t = trim($topics[$i] ?? '');
        if ($t !== '' && $e > $s) {
            $slots[] = ['start_min' => $s, 'end_min' => $e, 'topic' => $t];
        }
    }
    
    $slotsJson = json_encode($slots, JSON_UNESCAPED_UNICODE);
    
    if ($saveType === 'default') {
        $dayOfWeek = intval($_POST['day_of_week'] ?? 0);
        
        // Upsert: delete old + insert new
        $stmt = $pdo->prepare("DELETE FROM timetable_defaults WHERE shakha_id = ? AND day_of_week = ?");
        $stmt->execute([$shakhaId, $dayOfWeek]);
        
        $stmt = $pdo->prepare("INSERT INTO timetable_defaults (shakha_id, day_of_week, slots, updated_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$shakhaId, $dayOfWeek, $slotsJson]);
        
        header("Location: ../pages/timetable.php?tab=default&day=" . $dayOfWeek . "&msg=Default+timetable+saved!");
        
    } elseif ($saveType === 'override') {
        $overrideDate = $_POST['override_date'] ?? date('Y-m-d');
        
        // Upsert
        $stmt = $pdo->prepare("DELETE FROM timetable_overrides WHERE shakha_id = ? AND override_date = ?");
        $stmt->execute([$shakhaId, $overrideDate]);
        
        $stmt = $pdo->prepare("INSERT INTO timetable_overrides (shakha_id, override_date, slots) VALUES (?, ?, ?)");
        $stmt->execute([$shakhaId, $overrideDate, $slotsJson]);
        
        header("Location: ../pages/timetable.php?tab=override&msg=Date+override+saved!");
        
    } elseif ($saveType === 'override_to_default') {
        // Save current override data as the new default for that day_of_week
        $overrideDate = $_POST['override_date'] ?? date('Y-m-d');
        $dayOfWeek = date('w', strtotime($overrideDate));
        
        $stmt = $pdo->prepare("DELETE FROM timetable_defaults WHERE shakha_id = ? AND day_of_week = ?");
        $stmt->execute([$shakhaId, $dayOfWeek]);
        
        $stmt = $pdo->prepare("INSERT INTO timetable_defaults (shakha_id, day_of_week, slots, updated_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$shakhaId, $dayOfWeek, $slotsJson]);
        
        header("Location: ../pages/timetable.php?tab=default&day=" . $dayOfWeek . "&msg=Saved+as+default+for+all+future+" . urlencode(date('l', strtotime($overrideDate))) . "s!");
    } elseif ($saveType === 'copy_all') {
        // Copy the current slot configuration to all 7 days
        for ($dw = 0; $dw < 7; $dw++) {
            $stmt = $pdo->prepare("DELETE FROM timetable_defaults WHERE shakha_id = ? AND day_of_week = ?");
            $stmt->execute([$shakhaId, $dw]);
            
            $stmt = $pdo->prepare("INSERT INTO timetable_defaults (shakha_id, day_of_week, slots, updated_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$shakhaId, $dw, $slotsJson]);
        }
        header("Location: ../pages/timetable.php?tab=default&msg=" . urlencode("समय-सारणी सभी 7 दिनों में सफलतापूर्वक कॉपी की गई!"));
    }
    exit;
}

header("Location: ../pages/timetable.php");
