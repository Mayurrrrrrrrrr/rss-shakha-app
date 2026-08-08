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
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO em_participant_attendance (event_id, attendance_session_id, participant_id, is_present, marked_by, marked_at) 
                    VALUES (?, ?, ?, ?, ?, NOW()) 
                    ON DUPLICATE KEY UPDATE 
                    is_present = VALUES(is_present), 
                    marked_by = VALUES(marked_by), 
                    marked_at = NOW()
                ");
                $stmt->execute([$event_id, $attendance_session_id, $participant_id, $is_present, $marked_by]);
                
                // Return updated counts
                $countStmt = $pdo->prepare("SELECT COUNT(*) FROM em_participants WHERE event_id = ?");
                $countStmt->execute([$event_id]);
                $total = $countStmt->fetchColumn();
                
                $presStmt = $pdo->prepare("SELECT COUNT(*) FROM em_participant_attendance WHERE event_id = ? AND attendance_session_id = ? AND is_present = 1");
                $presStmt->execute([$event_id, $attendance_session_id]);
                $present = $presStmt->fetchColumn();
                
                if (ob_get_length()) ob_clean();
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'total' => $total, 'present' => $present]);
                exit;
            } catch (Exception $e) {
                if (ob_get_length()) ob_clean();
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                exit;
            }
        }
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Missing ID']);
        exit;
    }
}

// Handle Participant Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_participant') {
    $participant_id = $_POST['participant_id'] ?? 0;
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $vasti = trim($_POST['vasti'] ?? '');
    $organization = trim($_POST['organization'] ?? '');
    $level_type = trim($_POST['level_type'] ?? '');
    $responsibility = trim($_POST['responsibility'] ?? '');
    $sangh_shikshan = trim($_POST['sangh_shikshan'] ?? '');
    $age_group = trim($_POST['age_group'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    
    if ($participant_id && $name) {
        $stmt = $pdo->prepare("UPDATE em_participants SET name = ?, phone = ?, city = ?, vasti = ?, organization = ?, level_type = ?, responsibility = ?, sangh_shikshan = ?, age_group = ?, email = ?, category = ?, notes = ? WHERE id = ? AND event_id = ?");
        $stmt->execute([$name, $phone, $city, $vasti, $organization, $level_type, $responsibility, $sangh_shikshan, $age_group, $email, $category, $notes, $participant_id, $event_id]);
    }
    
    // Redirect back to preserve GET params
    $queryParams = $_GET;
    // Unset action if it somehow got in GET, just in case
    unset($queryParams['action']);
    $qs = http_build_query($queryParams);
    header("Location: attendance.php" . ($qs ? '?' . $qs : ''));
    exit;
}

// Fetch Sessions
$sessionsStmt = $pdo->prepare("SELECT id, session_name FROM em_attendance_sessions WHERE event_id = ?");
$sessionsStmt->execute([$event_id]);
$sessions = $sessionsStmt->fetchAll(PDO::FETCH_ASSOC);

// Selected Session
$selected_session_id = $_GET['session_id'] ?? ($sessions[0]['id'] ?? 0);

// Filters
$search = trim($_GET['search'] ?? '');
$vyavastha = $_SESSION['event_vyavastha'] ?? '';
$is_hajiri = ($vyavastha === 'hajiri');

// Fetch Distinct Cities or Bhag
if ($is_hajiri) {
    $citiesStmt = $pdo->prepare("SELECT DISTINCT bhag FROM em_participants WHERE event_id = ? AND bhag IS NOT NULL AND bhag != '' ORDER BY bhag");
    $citiesStmt->execute([$event_id]);
    $filter_options = $citiesStmt->fetchAll(PDO::FETCH_COLUMN);
    $filter_label = "भाग चुनें (Select Bhag)";
    $filter_name = "bhag";
    $filter_value = trim($_GET['bhag'] ?? '');
} else {
    $citiesStmt = $pdo->prepare("SELECT DISTINCT city FROM em_participants WHERE event_id = ? AND city IS NOT NULL AND city != '' ORDER BY city");
    $citiesStmt->execute([$event_id]);
    $filter_options = $citiesStmt->fetchAll(PDO::FETCH_COLUMN);
    $filter_label = "सभी नगर (All Cities)";
    $filter_name = "nagar";
    $filter_value = trim($_GET['nagar'] ?? '');
}

// Fetch Participants and their current attendance status
$query = "
    SELECT p.id, p.name, p.phone, p.city, p.vasti, p.organization, p.level_type, p.responsibility, p.sangh_shikshan, p.age_group, p.email, p.category, p.notes, a.is_present
    FROM em_participants p
    LEFT JOIN em_participant_attendance a ON p.id = a.participant_id AND a.attendance_session_id = :session_id
    WHERE p.event_id = :event_id
";
$params = [':session_id' => $selected_session_id, ':event_id' => $event_id];

$assigned_bhag = $_SESSION['event_assigned_bhag'] ?? '';
$is_hajiri = ($_SESSION['event_vyavastha'] ?? '') === 'hajiri';

if ($is_hajiri && $assigned_bhag !== '') {
    $query .= " AND (p.bhag = :assigned_bhag1 OR p.city = :assigned_bhag2)";
    $params[':assigned_bhag1'] = $assigned_bhag;
    $params[':assigned_bhag2'] = $assigned_bhag;
}

$search = trim($_GET['search'] ?? '');
if ($search !== '') {
    $search_hindi = $search;
    
    // If search contains English characters, try to transliterate to Hindi on the backend
    if (preg_match('/[a-zA-Z]/', $search)) {
        $url = "https://inputtools.google.com/request?text=" . urlencode($search) . "&itc=hi-t-i0-und&num=1";
        $ctx = stream_context_create(['http' => ['timeout' => 2]]);
        $response = @file_get_contents($url, false, $ctx);
        if ($response) {
            $data = json_decode($response, true);
            if (isset($data[0]) && $data[0] === 'SUCCESS' && isset($data[1][0][1][0])) {
                $search_hindi = $data[1][0][1][0];
            }
        }
    }

    $query .= " AND (p.name LIKE :search1 OR p.name LIKE :search2 OR p.phone LIKE :search3)";
    $params[':search1'] = "%$search%";
    $params[':search2'] = "%$search_hindi%";
    $params[':search3'] = "%$search%";
}

if ($filter_value !== '') {
    if ($is_hajiri) {
        $query .= " AND p.bhag = :filter_val";
    } else {
        $query .= " AND p.city = :filter_val";
    }
    $params[':filter_val'] = $filter_value;
}

$query .= " ORDER BY p.name ASC";

// Volunteers must search first — don't load full list
if ($is_hajiri && $search === '' && $filter_value === '') {
    $participantsList = [];
} else {
    $partStmt = $pdo->prepare($query);
    $partStmt->execute($params);
    $participantsList = $partStmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch global stats for header
$totalStmt = $pdo->prepare("SELECT COUNT(*) FROM em_participants WHERE event_id = ?");
$totalStmt->execute([$event_id]);
$total_count = $totalStmt->fetchColumn();

$presentStmt = $pdo->prepare("SELECT COUNT(*) FROM em_participant_attendance WHERE event_id = ? AND attendance_session_id = ? AND is_present = 1");
$presentStmt->execute([$event_id, $selected_session_id]);
$present_count = $presentStmt->fetchColumn();
$percentage = $total_count > 0 ? round(($present_count / $total_count) * 100) : 0;

// Fetch distinct values for edit dropdowns (datalists)
$dropdown_cols = ['organization', 'level_type', 'responsibility', 'sangh_shikshan', 'age_group', 'category'];
$dropdown_options = [];
foreach($dropdown_cols as $col) {
    try {
        $optStmt = $pdo->prepare("SELECT DISTINCT `$col` FROM em_participants WHERE event_id = ? AND `$col` IS NOT NULL AND `$col` != '' ORDER BY `$col`");
        $optStmt->execute([$event_id]);
        $dropdown_options[$col] = $optStmt->fetchAll(PDO::FETCH_COLUMN);
    } catch(Exception $e) {
        $dropdown_options[$col] = [];
    }
}

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

<?php if (!$is_hajiri): ?>
<div class="stats-bar">
    <h2>उपस्थित <span id="present-count"><?= $present_count ?></span> / कुल <?= $total_count ?></h2>
    <p>(Present / Total) - <span id="present-percentage"><?= $percentage ?></span>%</p>
</div>
<?php endif; ?>

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
            
            <input type="text" id="search_input" name="search" class="form-control filter-input" placeholder="नाम या फोन से खोजें (Search by name or phone)" value="<?= htmlspecialchars($search) ?>" oninput="debounceSearch()" autofocus>
            
            <?php if (!$is_hajiri): ?>
            <select name="<?= $filter_name ?>" class="form-control filter-input" onchange="document.getElementById('filter-form').submit()">
                <option value=""><?= htmlspecialchars($filter_label) ?></option>
                <?php foreach ($filter_options as $c): ?>
                    <option value="<?= htmlspecialchars($c) ?>" <?= $c === $filter_value ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php endif; ?>
        </div>
    </form>
</div>

<div id="participants-list">
    <?php if (count($participantsList) > 0): ?>
        <?php foreach ($participantsList as $p): ?>
            <?php $isPresent = !empty($p['is_present']); ?>
            <div class="participant-card <?= $isPresent ? 'present' : '' ?>" id="card-<?= $p['id'] ?>">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 5px;">
                    <div style="font-size: 1.2rem; font-weight: bold;">👤 <?= htmlspecialchars($p['name']) ?></div>
                    <button type="button" style="background: none; border: none; cursor: pointer; font-size: 1rem; color: #007bff; padding: 0;" onclick="openEditModal(<?= htmlspecialchars(json_encode([
                        'id' => $p['id'],
                        'name' => $p['name'],
                        'phone' => $p['phone'] ?? '',
                        'city' => $p['city'] ?? '',
                        'vasti' => $p['vasti'] ?? '',
                        'organization' => $p['organization'] ?? '',
                        'level_type' => $p['level_type'] ?? '',
                        'responsibility' => $p['responsibility'] ?? '',
                        'sangh_shikshan' => $p['sangh_shikshan'] ?? '',
                        'age_group' => $p['age_group'] ?? '',
                        'email' => $p['email'] ?? '',
                        'category' => $p['category'] ?? '',
                        'notes' => $p['notes'] ?? ''
                    ])) ?>)">✏️ Edit</button>
                </div>
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
        <?php if ($is_hajiri && $search === ''): ?>
        <div class="card" style="text-align: center; padding: 3rem;">
            <div style="font-size: 3rem; margin-bottom: 1rem;">🔍</div>
            <h3 style="margin: 0 0 0.5rem;">नाम या फोन नंबर खोजें</h3>
            <p style="color: var(--text-muted); margin: 0;">Search by name or phone number to find participants</p>
        </div>
        <?php else: ?>
        <div class="card" style="text-align: center; padding: 2rem;">कोई प्रतिभागी नहीं मिला (No participants found).</div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Edit Modal -->
<div id="editModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 1000;">
    <div class="card" style="width: 90%; max-width: 400px; margin: 5% auto; position: relative; max-height: 90vh; overflow-y: auto;">
        <span onclick="document.getElementById('editModal').style.display='none'" style="position: absolute; right: 1rem; top: 1rem; cursor: pointer; font-size: 1.5rem;">&times;</span>
        <h3 style="margin-top: 0; color: var(--saffron);">संपादित करें (Edit)</h3>
        <?php
            $qs = http_build_query($_GET);
            $actionUrl = "attendance.php" . ($qs ? '?' . $qs : '');
        ?>
        <form method="POST" action="<?= htmlspecialchars($actionUrl) ?>">
            <input type="hidden" name="action" value="edit_participant">
            <input type="hidden" name="participant_id" id="edit_participant_id">
            
            <div class="form-group">
                <label>पूर्ण नाव (Name) *</label>
                <input type="text" name="name" id="edit_name" class="form-control" required>
            </div>
            <div class="form-group">
                <label>भ्रमणध्वनी (Phone)</label>
                <input type="text" name="phone" id="edit_phone" class="form-control">
            </div>
            <div class="form-group">
                <label>निवासी नगर (City)</label>
                <input type="text" name="city" id="edit_city" class="form-control">
            </div>
            <div class="form-group">
                <label>निवासी वस्ती (Vasti)</label>
                <input type="text" name="vasti" id="edit_vasti" class="form-control">
            </div>
            <div class="form-group">
                <label>संघटना (Organization)</label>
                <input type="text" name="organization" id="edit_organization" class="form-control" list="list_organization">
                <datalist id="list_organization">
                    <option value="रा.स्व.संघ"></option>
                    <?php foreach($dropdown_options['organization'] as $opt) { if($opt!=='रा.स्व.संघ') echo '<option value="'.htmlspecialchars($opt).'"></option>'; } ?>
                </datalist>
            </div>
            <div class="form-group">
                <label>स्तर / प्रकार (Level/Type)</label>
                <input type="text" name="level_type" id="edit_level_type" class="form-control" list="list_level_type">
                <datalist id="list_level_type">
                    <option value="भाग"></option>
                    <option value="नगर"></option>
                    <?php foreach($dropdown_options['level_type'] as $opt) { if($opt!=='भाग' && $opt!=='नगर') echo '<option value="'.htmlspecialchars($opt).'"></option>'; } ?>
                </datalist>
            </div>
            <div class="form-group">
                <label>दायित्व (Responsibility)</label>
                <input type="text" name="responsibility" id="edit_responsibility" class="form-control" list="list_responsibility">
                <datalist id="list_responsibility">
                    <option value="भाग सह कार्यवाह"></option>
                    <?php foreach($dropdown_options['responsibility'] as $opt) { if($opt!=='भाग सह कार्यवाह') echo '<option value="'.htmlspecialchars($opt).'"></option>'; } ?>
                </datalist>
            </div>
            <div class="form-group">
                <label>संघ शिक्षण (Sangh Shikshan)</label>
                <input type="text" name="sangh_shikshan" id="edit_sangh_shikshan" class="form-control" list="list_sangh_shikshan">
                <datalist id="list_sangh_shikshan">
                    <option value="द्वितीय"></option>
                    <?php foreach($dropdown_options['sangh_shikshan'] as $opt) { if($opt!=='द्वितीय') echo '<option value="'.htmlspecialchars($opt).'"></option>'; } ?>
                </datalist>
            </div>
            <div class="form-group">
                <label>वयोगट (Age Group)</label>
                <input type="text" name="age_group" id="edit_age_group" class="form-control" list="list_age_group">
                <datalist id="list_age_group">
                    <?php foreach($dropdown_options['age_group'] as $opt) { echo '<option value="'.htmlspecialchars($opt).'"></option>'; } ?>
                </datalist>
            </div>
            <div class="form-group">
                <label>अणुडाक (Email)</label>
                <input type="email" name="email" id="edit_email" class="form-control">
            </div>
            <div class="form-group">
                <label>श्रेणी (Category)</label>
                <input type="text" name="category" id="edit_category" class="form-control" list="list_category">
                <datalist id="list_category">
                    <?php foreach($dropdown_options['category'] as $opt) { echo '<option value="'.htmlspecialchars($opt).'"></option>'; } ?>
                </datalist>
            </div>
            <div class="form-group">
                <label>इतर माहिती (Notes)</label>
                <textarea name="notes" id="edit_notes" class="form-control" rows="2"></textarea>
            </div>
            <div style="position: sticky; bottom: -2px; background: var(--card-bg, #1A1D27); padding: 1rem 0; z-index: 10; border-top: 1px solid rgba(255,255,255,0.1); margin-top: 1rem; margin-bottom: -1rem;">
                <button type="submit" class="btn" style="width: 100%;">सुरक्षित करें (Save)</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openEditModal(data) {
        document.getElementById('edit_participant_id').value = data.id;
        document.getElementById('edit_name').value = data.name;
        document.getElementById('edit_phone').value = data.phone;
        document.getElementById('edit_city').value = data.city;
        document.getElementById('edit_vasti').value = data.vasti;
        document.getElementById('edit_organization').value = data.organization;
        document.getElementById('edit_level_type').value = data.level_type;
        document.getElementById('edit_responsibility').value = data.responsibility;
        document.getElementById('edit_sangh_shikshan').value = data.sangh_shikshan;
        document.getElementById('edit_age_group').value = data.age_group;
        document.getElementById('edit_email').value = data.email;
        document.getElementById('edit_category').value = data.category;
        document.getElementById('edit_notes').value = data.notes;
        document.getElementById('editModal').style.display = 'block';
    }

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
        .then(res => res.text()) // Get as text first to handle errors
        .then(text => {
            try {
                // Extract JSON to ignore any leading/trailing garbage (PHP notices, BOMs, newlines)
                const jsonStr = text.substring(text.indexOf('{'), text.lastIndexOf('}') + 1);
                const data = JSON.parse(jsonStr);
                if (data.success) {
                    // Update stats safely (elements might not exist for volunteers)
                    const presentEl = document.getElementById('present-count');
                    if (presentEl) presentEl.innerText = data.present;
                    
                    const pctEl = document.getElementById('present-percentage');
                    if (pctEl) {
                        const pct = data.total > 0 ? Math.round((data.present / data.total) * 100) : 0;
                        pctEl.innerText = pct;
                    }

                    // Update card UI
                    const card = document.getElementById('card-' + participantId);
                    if (card) {
                        const isPresentNow = (action === 'mark_present');
                        if (isPresentNow) {
                            card.classList.add('present');
                            card.innerHTML = card.innerHTML.replace(/<button.*<\/button>/, `<button class="att-btn btn-absent" onclick="markAttendance(${participantId}, 'mark_absent')">❌ अनुपस्थित करें (Mark Absent)</button>`);
                        } else {
                            card.classList.remove('present');
                            card.innerHTML = card.innerHTML.replace(/<button.*<\/button>/, `<button class="att-btn btn-present" onclick="markAttendance(${participantId}, 'mark_present')">✅ उपस्थित (Present)</button>`);
                        }
                    }
                } else {
                    alert('Error: ' + (data.error || 'Failed to update attendance.'));
                }
            } catch(e) {
                console.error("JavaScript Error: ", e);
                console.error("Raw response: ", text);
                alert('An error occurred updating the UI. Check console.');
            }
        })
        .catch(err => {
            console.error(err);
            alert('Network error. Please try again.');
        });
    }

    const searchInput = document.getElementById('search_input');
    if (searchInput) {
        // Simple client-side debounce for form submission
        searchInput.addEventListener('keyup', function(e) {
            debounceSearch();
        });
    }
</script>

<?php include 'includes/footer.php'; ?>
