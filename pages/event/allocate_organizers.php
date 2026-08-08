<?php
session_start();
require_once '../../config/db.php';
include 'includes/header.php';

if ($_SESSION['event_role'] !== 'admin') {
    echo "<div class='container'><div class='card'><h2 style='color:var(--danger)'>Permission Denied</h2><p>You don't have permission to allocate organizers.</p></div></div>";
    include 'includes/footer.php';
    exit;
}

$event_id = (int)($_GET['event_id'] ?? 0);
if (!$event_id) {
    echo "<div class='container'><div class='card'><h2 style='color:var(--danger)'>Invalid Event ID</h2></div></div>";
    include 'includes/footer.php';
    exit;
}

// Fetch event details
$stmt = $pdo->prepare("SELECT * FROM em_events WHERE id = ?");
$stmt->execute([$event_id]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    echo "<div class='container'><div class='card'><h2 style='color:var(--danger)'>Event Not Found</h2></div></div>";
    include 'includes/footer.php';
    exit;
}

$message = '';
$error = '';
$generated_credentials = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['allocate'])) {
    $selected_ids = $_POST['participant_ids'] ?? [];
    $role = $_POST['role'] ?? 'volunteer';
    $vyavastha = $_POST['vyavastha'] ?? null;
    $assigned_bhag = $_POST['assigned_bhag'] ?? null;
    
    if ($vyavastha === '') $vyavastha = null;
    if ($assigned_bhag === '') $assigned_bhag = null;

    if (empty($selected_ids)) {
        $error = "कोई प्रतिभागी नहीं चुना गया (No participants selected).";
    } else {
        try {
            $pdo->beginTransaction();
            
            // Get selected participants details
            $placeholders = implode(',', array_fill(0, count($selected_ids), '?'));
            $stmt = $pdo->prepare("SELECT * FROM em_participants WHERE id IN ($placeholders) AND event_id = ?");
            
            $queryParams = $selected_ids;
            $queryParams[] = $event_id;
            $stmt->execute($queryParams);
            $selected_participants = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Insert into em_organizers
            $insertStmt = $pdo->prepare("
                INSERT INTO em_organizers (event_id, name, phone, username, password, role, assigned_bhag, vyavastha) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $count = 0;
            foreach ($selected_participants as $p) {
                // Generate a unique username and secure 8-character PIN/Password
                $dummy_username = strtolower(substr(preg_replace('/[^a-zA-Z0-9]/', '', $p['name']), 0, 5)) . rand(1000, 9999);
                if (strlen($dummy_username) < 6) $dummy_username = 'user' . rand(10000, 99999);
                
                $plain_password = substr(str_shuffle('23456789ABCDEFGHJKLMNPQRSTUVWXYZ'), 0, 8); // Avoid confusing chars like 0/O, 1/I
                $hashed_password = password_hash($plain_password, PASSWORD_DEFAULT);

                // Check if already an organizer by name & phone to prevent duplicates
                $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM em_organizers WHERE event_id = ? AND name = ? AND phone = ?");
                $checkStmt->execute([$event_id, $p['name'], $p['phone'] ?? '']);
                if ($checkStmt->fetchColumn() == 0) {
                    $insertStmt->execute([
                        $event_id, 
                        $p['name'], 
                        $p['phone'] ?? '', 
                        $dummy_username,
                        $hashed_password,
                        $role,
                        $assigned_bhag,
                        $vyavastha
                    ]);
                    $count++;
                    
                    $generated_credentials[] = [
                        'name' => $p['name'],
                        'phone' => $p['phone'],
                        'username' => $dummy_username,
                        'password' => $plain_password,
                        'role' => $role
                    ];
                }
            }
            
            $pdo->commit();
            $message = "$count आयोजकों को सफलतापूर्वक आवंटित किया गया (organizers successfully allocated)!";
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// Search
$search = $_GET['search'] ?? '';
$query = "SELECT * FROM em_participants WHERE event_id = ? AND is_deleted = 0";
$params = [$event_id];
if ($search) {
    $query .= " AND (name LIKE ? OR phone LIKE ? OR city LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
$query .= " ORDER BY name ASC LIMIT 200";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$participantsList = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch distinct locs for Bhag dropdown
$bhagStmt = $pdo->prepare("SELECT DISTINCT COALESCE(bhag, city) as loc FROM em_participants WHERE event_id = ? AND COALESCE(bhag, city) IS NOT NULL AND COALESCE(bhag, city) != '' ORDER BY loc ASC");
$bhagStmt->execute([$event_id]);
$bhagList = $bhagStmt->fetchAll(PDO::FETCH_COLUMN);

?>

<div class="container">
    <div class="page-header" style="display:flex; justify-content: space-between; align-items:center; margin-bottom: 2rem;">
        <h2 style="margin:0; color:var(--saffron);">आयोजक आवंटित करें (Allocate Organizers)</h2>
        <a href="create_event.php" class="btn btn-outline">वापस जाएं (Back)</a>
    </div>

    <div class="card" style="margin-bottom: 1rem;">
        <h3 style="margin-top:0;">आयोजन: <?= htmlspecialchars($event['name']) ?></h3>
        <p style="color: var(--text-muted); margin:0;">यहाँ आप इस आयोजन के पंजीकृत प्रतिभागियों में से आयोजक/कार्यकर्ता नियुक्त कर सकते हैं। (Allocate Organizers from the event's participants.)</p>
    </div>

    <?php if ($error): ?>
        <div style="background: rgba(239, 68, 68, 0.1); color: var(--danger); padding: 1rem; border-radius: 8px; margin-bottom: 1rem; border: 1px solid var(--danger);">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <form method="GET" action="" style="display: flex; gap: 1rem; margin-bottom: 1rem;">
            <input type="hidden" name="event_id" value="<?= $event_id ?>">
            <input type="text" name="search" class="form-control" placeholder="नाम, फोन, या नगर से खोजें..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit" class="btn">खोजें (Search)</button>
            <?php if($search): ?>
                <a href="allocate_organizers.php?event_id=<?= $event_id ?>" class="btn btn-outline">रीसेट</a>
            <?php endif; ?>
        </form>

        <form method="POST">
            <div style="display: flex; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.5rem; background: rgba(0,0,0,0.05); padding: 1rem; border-radius: 8px;">
                <div style="flex: 1; min-width: 200px;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: bold;">भूमिका (Role) *</label>
                    <select name="role" id="role_select" class="form-control" onchange="toggleVyavastha()" required>
                        <option value="volunteer">स्वयंसेवक (Volunteer)</option>
                        <option value="coordinator">समन्वयक (Coordinator)</option>
                        <option value="admin">प्रशासक (Admin)</option>
                    </select>
                </div>
                
                <div style="flex: 1; min-width: 200px;" id="vyavastha_group">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: bold;">व्यवस्था (Vyavastha)</label>
                    <select name="vyavastha" class="form-control">
                        <option value=''>-- चुनें --</option>
                        <option value='hajiri'>हाजिरी (Attendance)</option>
                        <option value='all'>सर्व (All)</option>
                    </select>
                </div>

                <div style="flex: 1; min-width: 200px;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: bold;">नियुक्त भाग (Assigned Bhag)</label>
                    <select name="assigned_bhag" class="form-control">
                        <option value=''>-- कोई नहीं (None) --</option>
                        <?php foreach ($bhagList as $bhag): ?>
                            <option value="<?= htmlspecialchars($bhag) ?>"><?= htmlspecialchars($bhag) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div style="overflow-x: auto; max-height: 50vh; overflow-y: auto; border: 1px solid var(--border-color); border-radius: 8px;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead style="position: sticky; top: 0; background: var(--card-bg); z-index: 1;">
                        <tr style="border-bottom: 2px solid var(--border-color); text-align: left;">
                            <th style="padding: 1rem;"><input type="checkbox" id="selectAll" onclick="toggleAll(this)"></th>
                            <th style="padding: 1rem;">नाम (Name)</th>
                            <th style="padding: 1rem;">फोन (Phone)</th>
                            <th style="padding: 1rem;">नगर (City)</th>
                            <th style="padding: 1rem;">दायित्व (Responsibility)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($participantsList as $p): ?>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 1rem;"><input type="checkbox" name="participant_ids[]" value="<?= $p['id'] ?>" class="row-checkbox"></td>
                            <td style="padding: 1rem; font-weight: bold;"><?= htmlspecialchars($p['name']) ?></td>
                            <td style="padding: 1rem;"><?= htmlspecialchars($p['phone'] ?? '-') ?></td>
                            <td style="padding: 1rem;"><?= htmlspecialchars($p['city'] ?? '-') ?></td>
                            <td style="padding: 1rem;"><?= htmlspecialchars($p['responsibility'] ?? '-') ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($participantsList)): ?>
                        <tr><td colspan="5" style="padding: 1rem; text-align: center;">इस आयोजन में कोई प्रतिभागी पंजीकृत नहीं है (No participants in this event)</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div style="margin-top: 1.5rem; display: flex; justify-content: flex-end;">
                <button type="submit" name="allocate" class="btn" style="padding: 0.8rem 1.5rem; font-size: 1.1rem;">चुने हुए आयोजकों को आवंटित करें (Allocate Selected)</button>
            </div>
        </form>
    </div>
</div>

<!-- Credentials Modal -->
<div id="credentialsModal" class="modal-overlay" style="display: none;">
    <div class="modal-container" style="max-width: 600px;">
        <div class="modal-header">
            <h3 class="modal-title" style="color: var(--success); display: flex; align-items: center; gap: 0.5rem;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                आवंटन सफल (Allocation Successful)
            </h3>
            <button class="modal-close" onclick="closeCredModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p style="margin-top: 0; color: var(--text-muted);">निम्नलिखित आयोजकों के खाते सफलतापूर्वक बना दिए गए हैं। कृपया यह विवरण उनके साथ साझा करें:</p>
            
            <div id="credentialsList" style="background: rgba(0,0,0,0.2); padding: 1rem; border-radius: 8px; max-height: 300px; overflow-y: auto; font-family: monospace; white-space: pre-wrap; margin-bottom: 1.5rem; border: 1px solid var(--border-color);"></div>
            
            <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                <button type="button" class="btn btn-outline" onclick="closeCredModal()">बंद करें (Close)</button>
                <button type="button" class="btn" onclick="copyCredentials()" style="display: flex; align-items: center; gap: 0.5rem;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                    क्लिपबोर्ड पर कॉपी करें (Copy for WhatsApp)
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function toggleAll(source) {
    let checkboxes = document.querySelectorAll('.row-checkbox');
    for (var i = 0, n = checkboxes.length; i < n; i++) {
        checkboxes[i].checked = source.checked;
    }
}

function toggleVyavastha() {
    let role = document.getElementById('role_select').value;
    let vyavasthaGroup = document.getElementById('vyavastha_group');
    if (role === 'admin') {
        vyavasthaGroup.style.display = 'none';
    } else {
        vyavasthaGroup.style.display = 'block';
    }
}

function closeCredModal() {
    document.getElementById('credentialsModal').classList.remove('active');
    setTimeout(() => { document.getElementById('credentialsModal').style.display = 'none'; }, 300);
}

// Handle Auto-generated Credentials display
const generatedCredentials = <?= json_encode($generated_credentials) ?>;
let copyText = '';

if (generatedCredentials && generatedCredentials.length > 0) {
    const eventName = <?= json_encode($event['name']) ?>;
    const siteUrl = window.location.origin + window.location.pathname.replace('/pages/event/allocate_organizers.php', '');
    
    let displayHtml = '';
    copyText = `*${eventName} - आयोजक लॉगिन विवरण*\n\nलॉगिन लिंक: ${siteUrl}\n\n`;
    
    generatedCredentials.forEach(cred => {
        const msg = `👤 *${cred.name}* (${cred.role})\nयूज़रनेम: ${cred.username}\nपासवर्ड: ${cred.password}\n`;
        displayHtml += `<strong>👤 ${cred.name}</strong> (${cred.role})<br>यूज़रनेम (Username): <span style="color:var(--saffron)">${cred.username}</span><br>पासवर्ड (Password): <span style="color:var(--saffron)">${cred.password}</span><br><br>`;
        copyText += msg + '\n';
    });
    
    document.getElementById('credentialsList').innerHTML = displayHtml;
    
    // Show Modal
    const modal = document.getElementById('credentialsModal');
    modal.style.display = 'flex';
    // Trigger reflow for animation
    void modal.offsetWidth;
    modal.classList.add('active');
    
    if (typeof showToast === 'function') {
        showToast('<?= htmlspecialchars($message) ?>', 'success');
    }
}

function copyCredentials() {
    navigator.clipboard.writeText(copyText).then(function() {
        if (typeof showToast === 'function') {
            showToast('विवरण कॉपी हो गया! (Copied to clipboard)', 'success');
        } else {
            alert('विवरण कॉपी हो गया! (Copied to clipboard)');
        }
    }, function(err) {
        console.error('Could not copy text: ', err);
        alert('Copy failed. Please manually select and copy the text.');
    });
}
</script>

<?php include 'includes/footer.php'; ?>
