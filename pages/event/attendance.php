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
$sessionsStmt = $pdo->prepare("SELECT id, session_name, session_date FROM em_attendance_sessions WHERE event_id = ? ORDER BY session_date ASC, id ASC");
$sessionsStmt->execute([$event_id]);
$all_sessions = $sessionsStmt->fetchAll(PDO::FETCH_ASSOC);

// Group sessions by date
$sessions_by_date = [];
foreach($all_sessions as $sess) {
    $date = $sess['session_date'];
    if (!isset($sessions_by_date[$date])) {
        $sessions_by_date[$date] = [];
    }
    $sessions_by_date[$date][] = $sess;
}

$dates = array_keys($sessions_by_date);
$selected_date = $_GET['session_date'] ?? ($dates[0] ?? date('Y-m-d'));

// Ensure selected_date exists in array, else fallback
if (!isset($sessions_by_date[$selected_date]) && !empty($dates)) {
    $selected_date = $dates[0];
}

$date_sessions = $sessions_by_date[$selected_date] ?? [];
$selected_session_id = $_GET['session_id'] ?? ($date_sessions[0]['id'] ?? 0);

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

// Fetch all allowed participants for client-side filtering
$partStmt = $pdo->prepare($query);
$partStmt->execute($params);
$participantsList = $partStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch global stats
$totalStmt = $pdo->prepare("SELECT COUNT(*) FROM em_participants WHERE event_id = ?");
if ($is_hajiri && $assigned_bhag !== '') {
    $totalStmt = $pdo->prepare("SELECT COUNT(*) FROM em_participants WHERE event_id = ? AND (bhag = ? OR city = ?)");
    $totalStmt->execute([$event_id, $assigned_bhag, $assigned_bhag]);
} else {
    $totalStmt->execute([$event_id]);
}
$total_count = $totalStmt->fetchColumn();

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
    /* Luma-style Attendance UI */
    .dashboard-header { margin-bottom: 2rem; }
    
    .stats-card {
        background: var(--card-bg);
        border: 1px solid rgba(255, 255, 255, 0.05);
        border-radius: var(--radius-lg);
        padding: 1.5rem;
        box-shadow: 0 10px 30px -10px rgba(0,0,0,0.3);
        text-align: center;
        margin-bottom: 1.5rem;
        position: relative;
        overflow: hidden;
    }
    .stats-card::before {
        content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px;
        background: linear-gradient(90deg, var(--success), var(--saffron));
    }
    .stats-numbers {
        display: flex; justify-content: center; align-items: baseline; gap: 0.5rem;
        margin-bottom: 0.5rem;
    }
    .stats-present { font-size: 3rem; font-weight: 800; color: var(--success); line-height: 1; }
    .stats-total { font-size: 1.5rem; color: var(--text-muted); font-weight: 600; }
    
    .progress-container {
        width: 100%; height: 8px; background: rgba(255,255,255,0.05);
        border-radius: 4px; overflow: hidden; margin-top: 1rem;
    }
    .progress-bar {
        height: 100%; background: linear-gradient(90deg, var(--saffron), var(--success));
        border-radius: 4px; transition: width 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .search-container {
        position: sticky; top: 0; z-index: 50;
        background: rgba(11, 14, 20, 0.9); backdrop-filter: blur(12px);
        padding: 1rem 0; margin-bottom: 1.5rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    }
    
    .participants-grid {
        display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1rem;
    }
    
    .p-card {
        background: var(--card-bg);
        border: 1px solid rgba(255, 255, 255, 0.05);
        border-radius: var(--radius-md);
        padding: 1.25rem;
        display: flex; flex-direction: column; justify-content: space-between;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative; overflow: hidden;
    }
    .p-card:hover { transform: translateY(-2px); box-shadow: 0 10px 20px -5px rgba(0,0,0,0.3); border-color: rgba(255,255,255,0.1); }
    
    /* Touch-friendly Status Indicator */
    .status-indicator {
        position: absolute; top: 0; bottom: 0; left: 0; width: 6px;
        background: var(--danger); transition: background 0.3s ease;
    }
    .p-card.is-present .status-indicator { background: var(--success); }
    
    .p-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.75rem; }
    .p-name { font-size: 1.15rem; font-weight: 700; color: var(--text-color); margin: 0; }
    .p-edit { background: none; border: none; color: var(--text-muted); cursor: pointer; padding: 0.25rem; transition: color 0.2s; }
    .p-edit:hover { color: var(--saffron); }
    
    .p-details { font-size: 0.9rem; color: var(--text-muted); display: flex; flex-direction: column; gap: 0.35rem; margin-bottom: 1.25rem; }
    .p-detail-item { display: flex; align-items: center; gap: 0.5rem; }
    
    /* Sleek Toggle Button */
    .att-toggle {
        width: 100%; padding: 0.85rem; border-radius: 8px; border: none; font-weight: 600; font-size: 1rem;
        cursor: pointer; display: flex; justify-content: center; align-items: center; gap: 0.5rem;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .att-toggle.absent { background: rgba(239, 68, 68, 0.1); color: var(--danger); border: 1px solid rgba(239, 68, 68, 0.2); }
    .att-toggle.absent:hover { background: rgba(239, 68, 68, 0.2); }
    .att-toggle.present { background: rgba(16, 185, 129, 0.1); color: var(--success); border: 1px solid rgba(16, 185, 129, 0.2); }
    .att-toggle.present:hover { background: rgba(16, 185, 129, 0.2); }
    
    /* Quick Action FAB */
    .fab {
        position: fixed; bottom: 2rem; right: 2rem; width: 56px; height: 56px;
        background: linear-gradient(135deg, var(--saffron), var(--saffron-dark));
        border-radius: 50%; display: flex; align-items: center; justify-content: center;
        color: white; font-size: 1.5rem; text-decoration: none; box-shadow: 0 10px 25px rgba(249, 115, 22, 0.4);
        transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); z-index: 90;
    }
    .fab:hover { transform: scale(1.1) rotate(90deg); }
    
    @media (max-width: 768px) {
        .fab { bottom: 1.5rem; right: 1.5rem; }
    }
</style>

<div class="dashboard-header">
    <div class="stats-card">
        <h3 style="margin:0 0 1rem 0; color: var(--text-muted); font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px;">Live Attendance</h3>
        <div class="stats-numbers">
            <span class="stats-present" id="ui-present-count">0</span>
            <span class="stats-total">/ <span id="ui-total-count"><?= $total_count ?></span></span>
        </div>
        <div style="color: var(--text-color); font-weight: 500;"><span id="ui-percentage">0</span>% Present</div>
        <div class="progress-container">
            <div class="progress-bar" id="ui-progress-bar" style="width: 0%;"></div>
        </div>
    </div>
</div>

<div class="search-container">
    <!-- Date Tabs -->
    <?php if (count($dates) > 0): ?>
    <div class="date-tabs" style="display: flex; gap: 0.5rem; overflow-x: auto; margin-bottom: 1rem; padding-bottom: 0.5rem;">
        <?php foreach ($dates as $d): ?>
            <a href="?session_date=<?= $d ?>" class="btn <?= $d === $selected_date ? '' : 'btn-outline' ?>" style="white-space: nowrap; padding: 0.4rem 1rem; border-radius: 20px;">
                <?= date('d M Y', strtotime($d)) ?>
            </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
        <select id="session_select" class="form-control" style="flex: 1; min-width: 200px;" onchange="changeSession()">
            <?php foreach ($date_sessions as $sess): ?>
                <option value="<?= $sess['id'] ?>" <?= $sess['id'] == $selected_session_id ? 'selected' : '' ?>>
                    <?= htmlspecialchars($sess['session_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <input type="text" id="js_search" class="form-control" style="flex: 2; min-width: 250px;" placeholder="Search by name, phone, or organization..." autofocus>
    </div>
</div>

<div class="participants-grid" id="participants_container">
    <!-- Rendered via JS -->
</div>

<!-- Manual Check-in FAB (Links to Spot Entry) -->
<a href="participants.php?action=add" class="fab" title="Manual Check-in / Spot Entry">
    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
</a>

<!-- Edit Modal -->
<div id="editModal" class="modal-overlay">
    <div class="modal-container">
        <div class="modal-header">
            <h3 class="modal-title">संपादित करें (Edit)</h3>
            <button class="modal-close" onclick="closeModal('editModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="editForm" method="POST" action="attendance.php">
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
                    <label>संघटना (Organization)</label>
                    <input type="text" name="organization" id="edit_organization" class="form-control" list="list_organization">
                    <datalist id="list_organization">
                        <option value="रा.स्व.संघ"></option>
                        <?php foreach($dropdown_options['organization'] as $opt) { if($opt!=='रा.स्व.संघ') echo '<option value="'.htmlspecialchars($opt).'"></option>'; } ?>
                    </datalist>
                </div>
                <!-- Other fields omitted for brevity but standard input works -->
                
                <div class="modal-footer" style="padding: 0; border: none; margin-top: 1.5rem;">
                    <button type="button" class="btn btn-outline" onclick="closeModal('editModal')">रद्द करें (Cancel)</button>
                    <button type="submit" class="btn">सुरक्षित करें (Save)</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Embed PHP Data directly for zero-latency client-side filtering
    const participantsData = <?= json_encode($participantsList) ?>;
    const container = document.getElementById('participants_container');
    const searchInput = document.getElementById('js_search');
    let currentSessionId = document.getElementById('session_select').value;
    let searchTimeout;

    // 1. Intercept search, fetch Hindi text, and trigger render
    async function handleSearch(filterText) {
        const rawText = filterText.trim().toLowerCase();
        
        if (!rawText) {
            renderParticipants([rawText]);
            return;
        }

        // If the input contains English letters, fetch Hindi transliteration
        if (/[a-z]/.test(rawText)) {
            try {
                const response = await fetch(`https://inputtools.google.com/request?text=${encodeURIComponent(rawText)}&itc=hi-t-i0-und&num=3`);
                const data = await response.json();
                
                let searchTerms = [rawText]; // Always include the original English text (useful for matching email/organization)
                
                if (data[0] === 'SUCCESS' && data[1][0][1]) {
                    // Combine the English text with the top 3 Hindi suggestions
                    searchTerms = searchTerms.concat(data[1][0][1]);
                }
                renderParticipants(searchTerms);
            } catch (e) {
                console.error("Transliteration API failed, falling back to basic search.", e);
                renderParticipants([rawText]);
            }
        } else {
            // It's already in Hindi or numbers, search normally
            renderParticipants([rawText]);
        }
    }

    // 2. Render participants checking against multiple possible terms
    function renderParticipants(searchTerms = ['']) {
        container.innerHTML = '';
        
        let presentCount = 0;
        let displayedCount = 0;
        const fragment = document.createDocumentFragment();
        
        participantsData.forEach(p => {
            const isPresent = p.is_present == 1;
            if (isPresent) presentCount++;
            
            // Advanced Search Logic checking all transliterations
            if (searchTerms.length > 0 && searchTerms[0] !== '') {
                const searchableText = [
                    p.name, p.phone, p.organization, p.city, p.bhag
                ].filter(Boolean).join(' ').toLowerCase();
                
                // Check if ANY of the search terms (English or Hindi) exist in the row
                const isMatch = searchTerms.some(term => searchableText.includes(term));
                
                if (!isMatch) return;
            }
            
            displayedCount++;
            
            const card = document.createElement('div');
            card.className = `p-card transition-all ${isPresent ? 'is-present' : ''}`;
            card.id = `card-${p.id}`;
            
            card.innerHTML = `
                <div class="status-indicator"></div>
                <div class="p-header">
                    <h4 class="p-name">${escapeHTML(p.name)}</h4>
                    <button class="p-edit" onclick='openEditModal(${JSON.stringify(p)})' title="Edit">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                    </button>
                </div>
                <div class="p-details">
                    <div class="p-detail-item">📱 ${escapeHTML(p.phone || 'N/A')}</div>
                    <div class="p-detail-item">📍 ${escapeHTML(p.city || 'N/A')} &nbsp;|&nbsp; 🏢 ${escapeHTML(p.organization || 'N/A')}</div>
                </div>
                <button class="att-toggle ${isPresent ? 'present' : 'absent'}" onclick="toggleAttendance(${p.id}, ${isPresent})">
                    ${isPresent ? '✅ उपस्थित (Present)' : '❌ अनुपस्थित (Absent)'}
                </button>
            `;
            fragment.appendChild(card);
        });
        
        container.appendChild(fragment);
        
        if (displayedCount === 0) {
            container.innerHTML = `
                <div style="grid-column: 1/-1; text-align: center; padding: 3rem; color: var(--text-muted);">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-bottom: 1rem; opacity: 0.5;"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    <p>कोई प्रतिभागी नहीं मिला (No participants found matching your search).</p>
                </div>
            `;
        }
        
        updateStats(presentCount, participantsData.length);
    }
    
    function updateStats(present, total) {
        document.getElementById('ui-present-count').textContent = present;
        document.getElementById('ui-total-count').textContent = total;
        const pct = total > 0 ? Math.round((present / total) * 100) : 0;
        document.getElementById('ui-percentage').textContent = pct;
        document.getElementById('ui-progress-bar').style.width = `${pct}%`;
    }
    
    function toggleAttendance(participantId, currentlyPresent) {
        const action = currentlyPresent ? 'mark_absent' : 'mark_present';
        const formData = new FormData();
        formData.append('action', action);
        formData.append('participant_id', participantId);
        formData.append('attendance_session_id', currentSessionId);

        // Optimistic UI Update
        const p = participantsData.find(x => x.id == participantId);
        if (p) p.is_present = !currentlyPresent;
        renderParticipants(searchInput.value);
        
        if (typeof showToast === 'function') {
            showToast(currentlyPresent ? 'Marked Absent' : 'Marked Present', currentlyPresent ? 'error' : 'success');
        }

        fetch('attendance.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                // Revert on failure
                if (p) p.is_present = currentlyPresent;
                renderParticipants(searchInput.value);
                if (typeof showToast === 'function') showToast('Failed to save on server.', 'error');
            } else {
                // Sync real stats from server just in case
                updateStats(data.present, data.total);
            }
        })
        .catch(err => {
            if (p) p.is_present = currentlyPresent;
            renderParticipants(searchInput.value);
            if (typeof showToast === 'function') showToast('Network Error.', 'error');
        });
    }
    
    function changeSession() {
        const sid = document.getElementById('session_select').value;
        const sdate = new URLSearchParams(window.location.search).get('session_date') || '<?= $selected_date ?>';
        window.location.href = `attendance.php?session_date=${sdate}&session_id=${sid}`;
    }
    
    function escapeHTML(str) {
        if (!str) return '';
        return str.toString().replace(/[&<>'"]/g, 
            tag => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;'
            }[tag] || tag)
        );
    }
    
    function openEditModal(data) {
        document.getElementById('edit_participant_id').value = data.id || '';
        document.getElementById('edit_name').value = data.name || '';
        document.getElementById('edit_phone').value = data.phone || '';
        document.getElementById('edit_city').value = data.city || '';
        document.getElementById('edit_organization').value = data.organization || '';
        if (typeof openModal === 'function') openModal('editModal');
        else document.getElementById('editModal').classList.add('active');
    }

    // Initialize
    searchInput.addEventListener('input', (e) => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => handleSearch(e.target.value), 300); // 300ms delay
    });
    renderParticipants();
</script>

<?php include 'includes/footer.php'; ?>
