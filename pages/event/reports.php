<?php
session_start();
require_once '../../config/db.php';

$event_id = $_SESSION['event_id'] ?? 1;
$role = $_SESSION['event_role'] ?? '';

// Restrict to admin only
if ($role !== 'admin') {
    include 'includes/header.php';
    echo "<div class='container'><div class='card'><h2 style='color:var(--danger)'>Permission Denied</h2><p>You do not have permission to access the reporting engine.</p></div></div>";
    include 'includes/footer.php';
    exit;
}

// All available columns in the participants table
$available_columns = [
    'name' => 'पूर्ण नाव (Name)',
    'phone' => 'भ्रमणध्वनी (Phone)',
    'city' => 'निवासी नगर (City)',
    'bhag' => 'भाग (Bhag)',
    'vasti' => 'वस्ती (Vasti)',
    'organization' => 'संघटना (Organization)',
    'category' => 'श्रेणी (Category)',
    'level_type' => 'स्तर (Level)',
    'responsibility' => 'दायित्व (Responsibility)',
    'sangh_shikshan' => 'संघ शिक्षण (Sangh Shikshan)',
    'age_group' => 'गट (Age Group)',
    'email' => 'ईमेल (Email)',
    'notes' => 'नोंद (Notes)',
    'is_spot_entry' => 'स्पॉट एंट्री (Spot Entry)',
    'created_at' => 'पंजीकरण समय (Registration Time)'
];

// Handle Export Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'export_csv') {
    
    // 1. Validate selected columns
    $selected_cols = $_POST['columns'] ?? [];
    $valid_cols = [];
    $headers = [];
    
    foreach ($selected_cols as $col) {
        if (array_key_exists($col, $available_columns)) {
            $valid_cols[] = "`$col`";
            $headers[] = $available_columns[$col]; // Native name for CSV header
        }
    }
    
    // Always fetch ID for attendance matching
    $sql_cols = empty($valid_cols) ? "`id`, `name`" : "`id`, " . implode(", ", $valid_cols);
    if (empty($valid_cols)) {
        $headers = ['पूर्ण नाव (Name)'];
    }
    
    $include_attendance = isset($_POST['include_attendance']) && $_POST['include_attendance'] == '1';
    
    // 2. Fetch Sessions if attendance is included
    $sessions = [];
    if ($include_attendance) {
        $sessStmt = $pdo->prepare("SELECT id, session_name, session_date FROM em_attendance_sessions WHERE event_id = ? ORDER BY session_date ASC, id ASC");
        $sessStmt->execute([$event_id]);
        $sessions = $sessStmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($sessions as $sess) {
            $dateFmt = date('d M', strtotime($sess['session_date']));
            $headers[] = $dateFmt . ' - ' . $sess['session_name'];
        }
    }
    
    // 3. Fetch Participants
    $partStmt = $pdo->prepare("SELECT $sql_cols FROM em_participants WHERE event_id = ? AND is_deleted = 0 ORDER BY name ASC");
    $partStmt->execute([$event_id]);
    $participants = $partStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 4. Fetch Attendance and pivot if needed
    $attendance_data = [];
    if ($include_attendance && !empty($participants)) {
        $attStmt = $pdo->prepare("SELECT participant_id, attendance_session_id, is_present FROM em_participant_attendance WHERE event_id = ?");
        $attStmt->execute([$event_id]);
        while ($row = $attStmt->fetch(PDO::FETCH_ASSOC)) {
            $pid = $row['participant_id'];
            $sid = $row['attendance_session_id'];
            $attendance_data[$pid][$sid] = $row['is_present'] == 1 ? 'Present' : 'Absent';
        }
    }
    
    // 5. Output CSV
    ob_end_clean(); // Clean any previous output buffer to avoid corrupted CSV
    
    $filename = "Event_Export_" . date('Y-m-d_H-i-s') . ".csv";
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    $output = fopen('php://output', 'w');
    
    // Add BOM for Excel UTF-8 display
    fputs($output, $bom =(chr(0xEF) . chr(0xBB) . chr(0xBF)));
    
    // Write Headers
    fputcsv($output, $headers);
    
    // Write Data
    foreach ($participants as $p) {
        $row = [];
        
        // Match selected valid columns
        if (empty($valid_cols)) {
            $row[] = $p['name'];
        } else {
            foreach ($selected_cols as $col) {
                if (array_key_exists($col, $available_columns)) {
                    $row[] = $p[$col] ?? '';
                }
            }
        }
        
        // Append attendance columns
        if ($include_attendance) {
            foreach ($sessions as $sess) {
                $status = $attendance_data[$p['id']][$sess['id']] ?? 'Not Marked';
                $row[] = $status;
            }
        }
        
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit;
}

// For UI Rendering
include 'includes/header.php';
?>

<style>
.report-card {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-lg);
    padding: 2rem;
    box-shadow: 0 10px 30px -10px rgba(0,0,0,0.3);
    margin-bottom: 2rem;
}
.report-card h3 {
    margin-top: 0;
    margin-bottom: 1.5rem;
    color: var(--text-color);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.report-card h3 svg {
    color: var(--saffron);
}

.checkbox-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
    background: rgba(0,0,0,0.2);
    padding: 1.5rem;
    border-radius: var(--radius-md);
    border: 1px solid rgba(255,255,255,0.05);
}

.checkbox-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    cursor: pointer;
}
.checkbox-item input[type="checkbox"] {
    width: 18px;
    height: 18px;
    accent-color: var(--saffron);
    cursor: pointer;
}
.checkbox-item span {
    font-size: 0.95rem;
    color: var(--text-color);
}

.toggle-container {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.5rem;
    background: rgba(16, 185, 129, 0.05);
    border: 1px solid rgba(16, 185, 129, 0.2);
    border-radius: var(--radius-md);
    margin-bottom: 2rem;
}
.toggle-switch {
    position: relative;
    display: inline-block;
    width: 50px;
    height: 26px;
}
.toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}
.slider {
    position: absolute;
    cursor: pointer;
    top: 0; left: 0; right: 0; bottom: 0;
    background-color: var(--border-color);
    transition: .4s;
    border-radius: 34px;
}
.slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 4px;
    bottom: 4px;
    background-color: white;
    transition: .4s;
    border-radius: 50%;
}
input:checked + .slider {
    background-color: var(--success);
}
input:checked + .slider:before {
    transform: translateX(24px);
}
.toggle-label {
    font-weight: 600;
    color: var(--text-color);
}

.action-bar {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    border-top: 1px solid rgba(255,255,255,0.05);
    padding-top: 1.5rem;
}
</style>

<div class="container">
    <div class="page-header" style="margin-bottom: 2rem;">
        <h2 style="margin:0; font-size: 1.8rem; background: linear-gradient(135deg, var(--text-color), var(--text-muted)); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">डेटा रिपोर्ट और एक्सपोर्ट (Data Reports)</h2>
        <p style="color: var(--text-muted); margin-top: 0.5rem;">Create customized CSV exports of participants and their attendance.</p>
    </div>
    
    <div class="report-card">
        <h3>
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
            कस्टम रिपोर्ट जेनरेट करें (Generate Custom Report)
        </h3>
        
        <form method="POST" id="exportForm">
            <input type="hidden" name="action" value="export_csv">
            
            <p style="color: var(--text-muted); margin-bottom: 1rem;">1. निर्यात करने के लिए कॉलम चुनें (Select Columns to Export):</p>
            
            <div style="margin-bottom: 1rem;">
                <button type="button" class="btn btn-outline" style="padding: 0.3rem 0.8rem; font-size: 0.85rem;" onclick="selectAll(true)">Select All</button>
                <button type="button" class="btn btn-outline" style="padding: 0.3rem 0.8rem; font-size: 0.85rem;" onclick="selectAll(false)">Deselect All</button>
            </div>
            
            <div class="checkbox-grid">
                <?php foreach ($available_columns as $key => $label): ?>
                <label class="checkbox-item">
                    <input type="checkbox" name="columns[]" value="<?= $key ?>" class="col-checkbox" checked>
                    <span><?= htmlspecialchars($label) ?></span>
                </label>
                <?php endforeach; ?>
            </div>
            
            <p style="color: var(--text-muted); margin-bottom: 1rem;">2. उपस्थिति डेटा (Attendance Data):</p>
            
            <div class="toggle-container">
                <label class="toggle-switch">
                    <input type="checkbox" name="include_attendance" value="1" checked>
                    <span class="slider"></span>
                </label>
                <div>
                    <div class="toggle-label">सत्र उपस्थिति शामिल करें (Include Session Attendance)</div>
                    <div style="font-size: 0.85rem; color: var(--text-muted);">This will add columns for every session in the event showing 'Present', 'Absent', or 'Not Marked'.</div>
                </div>
            </div>
            
            <div class="action-bar">
                <button type="submit" class="btn" style="padding: 0.8rem 2rem; font-size: 1.05rem;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:0.5rem; vertical-align:text-bottom;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                    CSV डाउनलोड करें (Download CSV)
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function selectAll(check) {
    const checkboxes = document.querySelectorAll('.col-checkbox');
    checkboxes.forEach(cb => {
        cb.checked = check;
    });
}
</script>

<?php include 'includes/footer.php'; ?>
