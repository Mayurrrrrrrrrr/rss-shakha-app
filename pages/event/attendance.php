<?php
session_start();
require_once '../../config/db.php';

$event_id = $_SESSION['event_id'] ?? 1;
$marked_by = $_SESSION['event_user_id'] ?? 0;

// Seed defaults if no sessions exist for this event
$sessionCheckStmt = $pdo->prepare("SELECT COUNT(*) FROM em_attendance_sessions WHERE event_id = ?");
$sessionCheckStmt->execute([$event_id]);
$sessionCount = $sessionCheckStmt->fetchColumn();

if ($sessionCount == 0) {
    // Auto-seed
    $defaultSessions = ['प्रातः सत्र (Morning Session)', 'सायं सत्र (Evening Session)'];
    $insertSessStmt = $pdo->prepare("INSERT INTO em_attendance_sessions (event_id, session_name, session_date) VALUES (?, ?, CURDATE())");
    foreach ($defaultSessions as $sname) {
        $insertSessStmt->execute([$event_id, $sname]);
    }
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['attendance_session_id'])) {
    $attendance_session_id = $_POST['attendance_session_id'];
    $participants_present = $_POST['is_present'] ?? []; // Array of participant IDs who are present

    // Get all participants to mark absent for those unchecked
    $allParticipantsStmt = $pdo->prepare("SELECT id FROM em_participants WHERE event_id = ?");
    $allParticipantsStmt->execute([$event_id]);
    $all_participants = $allParticipantsStmt->fetchAll(PDO::FETCH_COLUMN);

    $insertAttStmt = $pdo->prepare("
        INSERT INTO em_participant_attendance (event_id, attendance_session_id, participant_id, is_present, marked_by, marked_at) 
        VALUES (?, ?, ?, ?, ?, NOW()) 
        ON DUPLICATE KEY UPDATE 
        is_present = VALUES(is_present), 
        marked_by = VALUES(marked_by), 
        marked_at = NOW()
    ");

    $pdo->beginTransaction();
    try {
        foreach ($all_participants as $pid) {
            $is_present = in_array($pid, $participants_present) ? 1 : 0;
            $insertAttStmt->execute([$event_id, $attendance_session_id, $pid, $is_present, $marked_by]);
        }
        $pdo->commit();
        $success_msg = "हाजिरी सफलतापूर्वक सहेज ली गई है (Attendance saved successfully).";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_msg = "त्रुटि (Error): " . $e->getMessage();
    }
}

// Fetch Sessions
$sessionsStmt = $pdo->prepare("SELECT id, session_name FROM em_attendance_sessions WHERE event_id = ?");
$sessionsStmt->execute([$event_id]);
$sessions = $sessionsStmt->fetchAll(PDO::FETCH_ASSOC);

// Selected Session
$selected_session_id = $_POST['attendance_session_id'] ?? ($_GET['session_id'] ?? ($sessions[0]['id'] ?? 0));

// Fetch Participants and their current attendance status for the selected session
$participantsQuery = "
    SELECT p.id, p.name, p.city, a.is_present
    FROM em_participants p
    LEFT JOIN em_participant_attendance a ON p.id = a.participant_id AND a.attendance_session_id = ?
    WHERE p.event_id = ?
    ORDER BY p.name ASC
";
$partStmt = $pdo->prepare($participantsQuery);
$partStmt->execute([$selected_session_id, $event_id]);
$participantsList = $partStmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<div class="card">
    <h2>हाजिरी (Attendance)</h2>
    
    <?php if (isset($success_msg)): ?>
        <div style="background: #4caf50; color: white; padding: 1rem; border-radius: 4px; margin-bottom: 1rem;">
            <?= htmlspecialchars($success_msg) ?>
        </div>
    <?php endif; ?>
    <?php if (isset($error_msg)): ?>
        <div style="background: #f44336; color: white; padding: 1rem; border-radius: 4px; margin-bottom: 1rem;">
            <?= htmlspecialchars($error_msg) ?>
        </div>
    <?php endif; ?>

    <form method="GET" action="" style="margin-bottom: 1.5rem; display: flex; gap: 1rem; align-items: center;">
        <label for="session_id" style="font-weight: bold;">सत्र चुनें (Select Session):</label>
        <select name="session_id" id="session_id" class="form-control" style="width: auto;" onchange="this.form.submit()">
            <?php foreach ($sessions as $sess): ?>
                <option value="<?= $sess['id'] ?>" <?= $sess['id'] == $selected_session_id ? 'selected' : '' ?>>
                    <?= htmlspecialchars($sess['session_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>

    <form method="POST" action="">
        <input type="hidden" name="attendance_session_id" value="<?= htmlspecialchars($selected_session_id) ?>">
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th style="width: 50px; text-align: center;">#</th>
                        <th>नाम (Name)</th>
                        <th>शहर (City)</th>
                        <th style="text-align: center;">उपस्थित? (Present?)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($participantsList) > 0): ?>
                        <?php foreach ($participantsList as $index => $p): ?>
                            <tr>
                                <td style="text-align: center;"><?= $index + 1 ?></td>
                                <td><?= htmlspecialchars($p['name']) ?></td>
                                <td><?= htmlspecialchars($p['city']) ?></td>
                                <td style="text-align: center;">
                                    <input type="checkbox" name="is_present[]" value="<?= $p['id'] ?>" <?= $p['is_present'] ? 'checked' : '' ?> style="transform: scale(1.5);">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center;">कोई प्रतिभागी नहीं मिला (No participants found).</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div style="margin-top: 1.5rem; text-align: right;">
            <button type="submit" class="btn">सहेजें (Save Attendance)</button>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
