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

// Handle AJAX attendance marking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'mark_present' || $_POST['action'] === 'mark_absent') {
        $participant_id = $_POST['participant_id'] ?? 0;
        $attendance_session_id = $_POST['attendance_session_id'] ?? 0;
        $is_present = ($_POST['action'] === 'mark_present') ? 1 : 0;
        
        if ($participant_id && $attendance_session_id) {
            $stmt = $pdo->prepare("
                INSERT INTO em_participant_attendance (event_id, attendance_session_id, participant_id, is_present, marked_by, marked_at) 
                VALUES (?, ?, ?, ?, ?, NOW()) 
                ON DUPLICATE KEY UPDATE 
                is_present = VALUES(is_present), 
                marked_by = VALUES(marked_by), 
                marked_at = NOW()
            ");
            if ($stmt->execute([$event_id, $attendance_session_id, $participant_id, $is_present, $marked_by])) {
                // Return updated counts
                $countStmt = $pdo->prepare("SELECT COUNT(*) FROM em_participants WHERE event_id = ?");
                $countStmt->execute([$event_id]);
                $total = $countStmt->fetchColumn();
                
                $presStmt = $pdo->prepare("SELECT COUNT(*) FROM em_participant_attendance WHERE event_id = ? AND attendance_session_id = ? AND is_present = 1");
                $presStmt->execute([$event_id, $attendance_session_id]);
                $present = $presStmt->fetchColumn();
                
                echo json_encode(['success' => true, 'total' => $total, 'present' => $present]);
                exit;
            }
        }
        echo json_encode(['success' => false]);
        exit;
    }
}

// Fetch Sessions
$sessionsStmt = $pdo->prepare("SELECT id, session_name FROM em_attendance_sessions WHERE event_id = ?");
$sessionsStmt->execute([$event_id]);
$sessions = $sessionsStmt->fetchAll(PDO::FETCH_ASSOC);

// Selected Session
$selected_session_id = $_GET['session_id'] ?? ($sessions[0]['id'] ?? 0);

// Filters
$search = trim($_GET['search'] ?? '');
$nagar = trim($_GET['nagar'] ?? '');

// Fetch Distinct Cities
$citiesStmt = $pdo->prepare("SELECT DISTINCT city FROM em_participants WHERE event_id = ? AND city IS NOT NULL AND city != '' ORDER BY city");
$citiesStmt->execute([$event_id]);
$cities = $citiesStmt->fetchAll(PDO::FETCH_COLUMN);

// Fetch Participants and their current attendance status
$query = "
    SELECT p.id, p.name, p.phone, p.city, p.organization, a.is_present
    FROM em_participants p
    LEFT JOIN em_participant_attendance a ON p.id = a.participant_id AND a.attendance_session_id = :session_id
    WHERE p.event_id = :event_id
";
$params = [':session_id' => $selected_session_id, ':event_id' => $event_id];

if ($search !== '') {
    $query .= " AND (p.name LIKE :search OR p.phone LIKE :search)";
    $params[':search'] = "%$search%";
}
if ($nagar !== '') {
    $query .= " AND p.city = :nagar";
    $params[':nagar'] = $nagar;
}

$query .= " ORDER BY p.name ASC";

$partStmt = $pdo->prepare($query);
$partStmt->execute($params);
$participantsList = $partStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch global stats for header
$totalStmt = $pdo->prepare("SELECT COUNT(*) FROM em_participants WHERE event_id = ?");
$totalStmt->execute([$event_id]);
$total_count = $totalStmt->fetchColumn();

$presentStmt = $pdo->prepare("SELECT COUNT(*) FROM em_participant_attendance WHERE event_id = ? AND attendance_session_id = ? AND is_present = 1");
$presentStmt->execute([$event_id, $selected_session_id]);
$present_count = $presentStmt->fetchColumn();
$percentage = $total_count > 0 ? round(($present_count / $total_count) * 100) : 0;

include 'includes/header.php';
?>

<style>
    .stats-bar { background: var(--card-bg, #fff); padding: 1rem; border-radius: 8px; text-align: center; margin-bottom: 1rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .stats-bar h2 { margin: 0; color: var(--saffron, #ff9933); font-size: 2rem; }
    .search-bar { position: sticky; top: 0; z-index: 10; background: var(--bg-color, #f4f7f6); padding: 10px 0; }
    .participant-card { 
        background: var(--card-bg, #fff); 
        padding: 1rem; 
        border-radius: 8px; 
        margin-bottom: 1rem; 
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        border-left: 6px solid #ccc;
    }
    .participant-card.present { border-left-color: #4caf50; }
    .att-btn { width: 100%; min-height: 48px; margin-top: 10px; font-weight: bold; border-radius: 4px; border: none; cursor: pointer; font-size: 1rem; }
    .btn-present { background: #4caf50; color: white; }
    .btn-absent { background: #f44336; color: white; }
    .filter-input { min-height: 44px; margin-bottom: 0.5rem; }
</style>

<div class="stats-bar">
    <h2>उपस्थित <span id="present-count"><?= $present_count ?></span> / कुल <?= $total_count ?></h2>
    <p>(Present / Total) - <span id="present-percentage"><?= $percentage ?></span>%</p>
</div>

<div class="search-bar">
    <form id="filter-form" method="GET" action="">
        <div style="display: flex; gap: 0.5rem; flex-direction: column;">
            <select name="session_id" class="form-control filter-input" onchange="document.getElementById('filter-form').submit()">
                <?php foreach ($sessions as $sess): ?>
                    <option value="<?= $sess['id'] ?>" <?= $sess['id'] == $selected_session_id ? 'selected' : '' ?>>
                        <?= htmlspecialchars($sess['session_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <input type="text" name="search" class="form-control filter-input" placeholder="नाम या फोन से खोजें (Search by name or phone)" value="<?= htmlspecialchars($search) ?>" oninput="debounceSearch()">
            
            <select name="nagar" class="form-control filter-input" onchange="document.getElementById('filter-form').submit()">
                <option value="">सभी नगर (All Cities)</option>
                <?php foreach ($cities as $c): ?>
                    <option value="<?= htmlspecialchars($c) ?>" <?= $c === $nagar ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>
</div>

<div id="participants-list">
    <?php if (count($participantsList) > 0): ?>
        <?php foreach ($participantsList as $p): ?>
            <?php $isPresent = !empty($p['is_present']); ?>
            <div class="participant-card <?= $isPresent ? 'present' : '' ?>" id="card-<?= $p['id'] ?>">
                <div style="font-size: 1.2rem; font-weight: bold; margin-bottom: 5px;">👤 <?= htmlspecialchars($p['name']) ?></div>
                <div style="color: #666; margin-bottom: 5px;">📱 <?= htmlspecialchars($p['phone'] ?? 'N/A') ?> | 📍 <?= htmlspecialchars($p['city'] ?? 'N/A') ?></div>
                <div style="color: #666; font-size: 0.9rem;">🏢 <?= htmlspecialchars($p['organization'] ?? 'N/A') ?></div>
                
                <?php if ($isPresent): ?>
                    <button class="att-btn btn-absent" onclick="markAttendance(<?= $p['id'] ?>, 'mark_absent')">❌ अनुपस्थित करें (Mark Absent)</button>
                <?php else: ?>
                    <button class="att-btn btn-present" onclick="markAttendance(<?= $p['id'] ?>, 'mark_present')">✅ उपस्थित (Present)</button>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="card" style="text-align: center; padding: 2rem;">कोई प्रतिभागी नहीं मिला (No participants found).</div>
    <?php endif; ?>
</div>

<script>
    let searchTimeout;
    function debounceSearch() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            document.getElementById('filter-form').submit();
        }, 500);
    }

    function markAttendance(participantId, action) {
        const sessionId = document.querySelector('select[name="session_id"]').value;
        const formData = new FormData();
        formData.append('action', action);
        formData.append('participant_id', participantId);
        formData.append('attendance_session_id', sessionId);

        fetch('attendance.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Update stats
                document.getElementById('present-count').innerText = data.present;
                const pct = data.total > 0 ? Math.round((data.present / data.total) * 100) : 0;
                document.getElementById('present-percentage').innerText = pct;

                // Update card UI
                const card = document.getElementById('card-' + participantId);
                const isPresentNow = (action === 'mark_present');
                
                if (isPresentNow) {
                    card.classList.add('present');
                    card.innerHTML = card.innerHTML.replace(/<button.*<\/button>/, `<button class="att-btn btn-absent" onclick="markAttendance(${participantId}, 'mark_absent')">❌ अनुपस्थित करें (Mark Absent)</button>`);
                } else {
                    card.classList.remove('present');
                    card.innerHTML = card.innerHTML.replace(/<button.*<\/button>/, `<button class="att-btn btn-present" onclick="markAttendance(${participantId}, 'mark_present')">✅ उपस्थित (Present)</button>`);
                }
            } else {
                alert('Attendance update failed. Please try again.');
            }
        })
        .catch(err => {
            console.error(err);
            alert('Network error. Please try again.');
        });
    }
</script>

<?php include 'includes/footer.php'; ?>
