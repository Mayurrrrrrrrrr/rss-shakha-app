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
                // Generate a dummy username to avoid null constraint or conflicts if any
                $dummy_username = 'evt_' . $event_id . '_p_' . $p['id'];
                $dummy_password = password_hash('123456', PASSWORD_DEFAULT); // Dummy password if required by DB schema

                // Check if already an organizer by name & phone to prevent duplicates
                $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM em_organizers WHERE event_id = ? AND name = ? AND phone = ?");
                $checkStmt->execute([$event_id, $p['name'], $p['phone'] ?? '']);
                if ($checkStmt->fetchColumn() == 0) {
                    $insertStmt->execute([
                        $event_id, 
                        $p['name'], 
                        $p['phone'] ?? '', 
                        $dummy_username,
                        $dummy_password,
                        $role,
                        $assigned_bhag,
                        $vyavastha
                    ]);
                    $count++;
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
    
    <?php if ($message): ?>
        <div style="background: rgba(16, 185, 129, 0.1); color: var(--success); padding: 1rem; border-radius: 8px; margin-bottom: 1rem; border: 1px solid var(--success);">
            <?= htmlspecialchars($message) ?>
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

            <div style="overflow-x: auto; max-height: 50vh; overflow-y: auto;">
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

<script>
function toggleAll(source) {
    checkboxes = document.querySelectorAll('.row-checkbox');
    for (var i = 0, n = checkboxes.length; i < n; i++) {
        checkboxes[i].checked = source.checked;
    }
}

function toggleVyavastha() {
    var role = document.getElementById('role_select').value;
    var vyavasthaGroup = document.getElementById('vyavastha_group');
    if (role === 'admin') {
        vyavasthaGroup.style.display = 'none';
    } else {
        vyavasthaGroup.style.display = 'block';
    }
}
</script>

<?php include 'includes/footer.php'; ?>
