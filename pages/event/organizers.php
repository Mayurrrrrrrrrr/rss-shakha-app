<?php
session_start();
require_once '../../config/db.php';

if (!isset($_SESSION['event_user_id']) || $_SESSION['event_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$event_id = $_SESSION['event_id'] ?? null;
$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_organizer') {
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'volunteer';
        $assigned_bhag = $_POST['assigned_bhag'] ?? null;
        if ($assigned_bhag === '') {
            $assigned_bhag = null;
        }
        $vyavastha = $_POST['vyavastha'] ?? null;
        if ($vyavastha === '') {
            $vyavastha = null;
        }

        if ($name && $username && $password && $event_id) {
            try {
                // Check if username exists
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM em_organizers WHERE username = ?");
                $stmt->execute([$username]);
                if ($stmt->fetchColumn() > 0) {
                    $error = "उपयोगकर्ता नाम पहले से मौजूद है (Username already exists)";
                } else {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO em_organizers (event_id, name, phone, username, password, role, assigned_bhag, vyavastha) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$event_id, $name, $phone, $username, $hashed_password, $role, $assigned_bhag, $vyavastha]);
                    $message = "आयोजक सफलतापूर्वक जोड़ा गया (Organizer added successfully)";
                }
            } catch (PDOException $e) {
                $error = "त्रुटि: (Error: " . $e->getMessage() . ")";
            }
        } else {
            $error = "सभी अनिवार्य फ़ील्ड भरें (Fill all mandatory fields)";
        }
    } elseif ($_POST['action'] === 'edit_organizer') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $role = $_POST['role'] ?? 'volunteer';
        $assigned_bhag = $_POST['assigned_bhag'] ?? null;
        if ($assigned_bhag === '') {
            $assigned_bhag = null;
        }
        $vyavastha = $_POST['vyavastha'] ?? null;
        if ($vyavastha === '') {
            $vyavastha = null;
        }
        $password = $_POST['password'] ?? '';

        if ($id && $name && $event_id) {
            try {
                if ($password) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE em_organizers SET name = ?, phone = ?, role = ?, assigned_bhag = ?, vyavastha = ?, password = ? WHERE id = ? AND event_id = ?");
                    $stmt->execute([$name, $phone, $role, $assigned_bhag, $vyavastha, $hashed_password, $id, $event_id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE em_organizers SET name = ?, phone = ?, role = ?, assigned_bhag = ?, vyavastha = ? WHERE id = ? AND event_id = ?");
                    $stmt->execute([$name, $phone, $role, $assigned_bhag, $vyavastha, $id, $event_id]);
                }
                $message = "आयोजक सफलतापूर्वक अद्यतन किया गया (Organizer updated successfully)";
            } catch (PDOException $e) {
                $error = "त्रुटि: (Error: " . $e->getMessage() . ")";
            }
        } else {
            $error = "अनिवार्य फ़ील्ड भरें (Fill required fields)";
        }
    } elseif ($_POST['action'] === 'delete_organizer') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id && $id !== (int)$_SESSION['event_user_id']) {
            try {
                $stmt = $pdo->prepare("DELETE FROM em_organizers WHERE id = ? AND event_id = ?");
                $stmt->execute([$id, $event_id]);
                $message = "आयोजक सफलतापूर्वक हटाया गया (Organizer deleted successfully)";
            } catch (PDOException $e) {
                $error = "त्रुटि: (Error: " . $e->getMessage() . ")";
            }
        } elseif ($id === (int)$_SESSION['event_user_id']) {
            $error = "आप स्वयं को नहीं हटा सकते (You cannot delete yourself)";
        }
    }
}

// Fetch organizers
$organizers = [];
if ($event_id) {
    $stmt = $pdo->prepare("SELECT * FROM em_organizers WHERE event_id = ? ORDER BY role, name");
    $stmt->execute([$event_id]);
    $organizers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch distinct locs for Bhag dropdown
    $bhagStmt = $pdo->prepare("SELECT DISTINCT COALESCE(bhag, city) as loc FROM em_participants WHERE event_id = ? AND COALESCE(bhag, city) IS NOT NULL AND COALESCE(bhag, city) != '' ORDER BY loc ASC");
    $bhagStmt->execute([$event_id]);
    $bhagList = $bhagStmt->fetchAll(PDO::FETCH_COLUMN);
}
?>
<?php include 'includes/header.php'; ?>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <h2 style="margin: 0; color: var(--saffron);">आयोजक प्रबंध (Organizers Management)</h2>
        <button class="btn" onclick="document.getElementById('addModal').style.display='block'">+ नया आयोजक (New Organizer)</button>
    </div>

    <?php if ($message): ?><div style="color: #4caf50; margin-bottom: 1rem;"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div style="color: #f44336; margin-bottom: 1rem;"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div class="table-responsive" style="overflow-x: auto;">
        <table style="min-width: 800px;">
            <thead>
                <tr>
                    <th>नाम (Name)</th>
                    <th>फ़ोन (Phone)</th>
                    <th>यूज़रनेम (Username)</th>
                    <th>भूमिका (Role)</th>
                    <th>भाग (Bhag)</th>
                    <th>व्यवस्था (Vyavastha)</th>
                    <th>कार्रवाई (Actions)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($organizers as $org): ?>
                <tr>
                    <td><?= htmlspecialchars($org['name']) ?></td>
                    <td><?= htmlspecialchars($org['phone']) ?></td>
                    <td><?= htmlspecialchars($org['username']) ?></td>
                    <td>
                        <?php
                            if ($org['role'] === 'admin') echo 'प्रशासक (Admin)';
                            elseif ($org['role'] === 'coordinator') echo 'समन्वयक (Coordinator)';
                            else echo 'स्वयंसेवक (Volunteer)';
                        ?>
                    </td>
                    <td><?= htmlspecialchars($org['assigned_bhag'] ?? '-') ?></td>
                    <td>
                        <?php
                            if (($org['vyavastha'] ?? '') === 'hajiri') echo 'हाजिरी (Attendance)';
                            elseif (($org['vyavastha'] ?? '') === 'bhojan') echo 'भोजन (Food)';
                            elseif (($org['vyavastha'] ?? '') === 'nivas') echo 'निवास (Rooms)';
                            elseif (($org['vyavastha'] ?? '') === 'all') echo 'सर्व (All)';
                            else echo '-';
                        ?>
                    </td>
                    <td>
                        <button class="btn" style="padding: 0.25rem 0.5rem; font-size: 0.85rem; margin-right: 0.5rem;" onclick="editOrganizer(<?= $org['id'] ?>, '<?= htmlspecialchars(addslashes($org['name'])) ?>', '<?= htmlspecialchars(addslashes($org['phone'])) ?>', '<?= htmlspecialchars(addslashes($org['role'])) ?>', '<?= htmlspecialchars(addslashes($org['vyavastha'] ?? '')) ?>', '<?= htmlspecialchars(addslashes($org['assigned_bhag'] ?? '')) ?>')">संपादित करें (Edit)</button>
                        <?php if ($org['id'] != $_SESSION['event_user_id']): ?>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('क्या आप वाकई इस आयोजक को हटाना चाहते हैं? (Are you sure you want to delete this organizer?)');">
                            <input type="hidden" name="action" value="delete_organizer">
                            <input type="hidden" name="id" value="<?= $org['id'] ?>">
                            <button type="submit" class="btn" style="padding: 0.25rem 0.5rem; font-size: 0.85rem; background-color: #f44336;">हटाएं (Delete)</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($organizers)): ?>
                <tr><td colspan="6">कोई आयोजक नहीं मिला। (No organizers found.)</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal -->
<div id="addModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 1000;">
    <div class="card" style="width: 90%; max-width: 400px; margin: 4rem auto; position: relative;">
        <span onclick="document.getElementById('addModal').style.display='none'" style="position: absolute; right: 1rem; top: 1rem; cursor: pointer; font-size: 1.5rem;">&times;</span>
        <h3 style="margin-top: 0; color: var(--saffron);">नया आयोजक जोड़ें</h3>
        <form method="POST">
            <input type="hidden" name="action" value="add_organizer">
            
            <div class="form-group">
                <label>नाम (Name) *</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label>फ़ोन नंबर (Phone)</label>
                <input type="text" name="phone" class="form-control">
            </div>
            
            <div class="form-group">
                <label>उपयोगकर्ता नाम (Username) *</label>
                <input type="text" name="username" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label>पासवर्ड (Password) *</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label>भूमिका (Role) *</label>
                <select name="role" id="role_select" class="form-control" onchange="toggleVyavastha()" required>
                    <option value="volunteer">स्वयंसेवक (Volunteer)</option>
                    <option value="coordinator">समन्वयक (Coordinator)</option>
                    <option value="admin">प्रशासक (Admin)</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>नियुक्त भाग (Assigned Bhag)</label>
                <select name="assigned_bhag" class="form-control">
                    <option value=''>-- कोई नहीं (None) --</option>
                    <?php foreach ($bhagList as $bhag): ?>
                        <option value="<?= htmlspecialchars($bhag) ?>"><?= htmlspecialchars($bhag) ?></option>
                    <?php endforeach; ?>
                </select>
                <small style="color:#888;">केवल स्वयंसेवकों के लिए (Only for volunteers attending specific bhag)</small>
            </div>
            
            <div class="form-group" id="vyavastha_group" style="display: none;">
                <label>व्यवस्था (Vyavastha)</label>
                <select name="vyavastha" class="form-control">
                    <option value=''>-- चुनें --</option>
                    <option value='hajiri'>हाजिरी (Attendance)</option>
                    <option value='bhojan'>भोजन (Food)</option>
                    <option value='nivas'>निवास (Rooms)</option>
                    <option value='all'>सर्व (All)</option>
                </select>
            </div>
            
            <button type="submit" class="btn" style="width: 100%; margin-top: 1rem;">सुरक्षित करें (Save)</button>
        </form>
    </div>
</div>

<script>
function toggleVyavastha() {
    var role = document.getElementById('role_select').value;
    var vyavasthaGroup = document.getElementById('vyavastha_group');
    if (role === 'coordinator') {
        vyavasthaGroup.style.display = 'block';
    } else {
        vyavasthaGroup.style.display = 'none';
    }
}

function toggleEditVyavastha() {
    var role = document.getElementById('edit_role_select').value;
    var vyavasthaGroup = document.getElementById('edit_vyavastha_group');
    if (role === 'coordinator') {
        vyavasthaGroup.style.display = 'block';
    } else {
        vyavasthaGroup.style.display = 'none';
    }
}

function editOrganizer(id, name, phone, role, vyavastha, assigned_bhag) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_phone').value = phone;
    document.getElementById('edit_role_select').value = role;
    document.getElementById('edit_vyavastha').value = vyavastha;
    document.getElementById('edit_assigned_bhag').value = assigned_bhag;
    toggleEditVyavastha();
    document.getElementById('editModal').style.display = 'block';
}
</script>

<!-- Edit Modal -->
<div id="editModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 1000;">
    <div class="card" style="width: 90%; max-width: 400px; margin: 4rem auto; position: relative;">
        <span onclick="document.getElementById('editModal').style.display='none'" style="position: absolute; right: 1rem; top: 1rem; cursor: pointer; font-size: 1.5rem;">&times;</span>
        <h3 style="margin-top: 0; color: var(--saffron);">आयोजक संपादित करें (Edit Organizer)</h3>
        <form method="POST">
            <input type="hidden" name="action" value="edit_organizer">
            <input type="hidden" name="id" id="edit_id">
            
            <div class="form-group">
                <label>नाम (Name) *</label>
                <input type="text" name="name" id="edit_name" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label>फ़ोन नंबर (Phone)</label>
                <input type="text" name="phone" id="edit_phone" class="form-control">
            </div>
            
            <div class="form-group">
                <label>नया पासवर्ड (New Password)</label>
                <input type="password" name="password" class="form-control" placeholder="Leave empty to keep current">
            </div>
            
            <div class="form-group">
                <label>भूमिका (Role) *</label>
                <select name="role" id="edit_role_select" class="form-control" onchange="toggleEditVyavastha()" required>
                    <option value="volunteer">स्वयंसेवक (Volunteer)</option>
                    <option value="coordinator">समन्वयक (Coordinator)</option>
                    <option value="admin">प्रशासक (Admin)</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>नियुक्त भाग (Assigned Bhag)</label>
                <select name="assigned_bhag" id="edit_assigned_bhag" class="form-control">
                    <option value=''>-- कोई नहीं (None) --</option>
                    <?php foreach ($bhagList as $bhag): ?>
                        <option value="<?= htmlspecialchars($bhag) ?>"><?= htmlspecialchars($bhag) ?></option>
                    <?php endforeach; ?>
                </select>
                <small style="color:#888;">केवल स्वयंसेवकों के लिए</small>
            </div>
            
            <div class="form-group" id="edit_vyavastha_group" style="display: none;">
                <label>व्यवस्था (Vyavastha)</label>
                <select name="vyavastha" id="edit_vyavastha" class="form-control">
                    <option value=''>-- चुनें --</option>
                    <option value='hajiri'>हाजिरी (Attendance)</option>
                    <option value='bhojan'>भोजन (Food)</option>
                    <option value='nivas'>निवास (Rooms)</option>
                    <option value='all'>सर्व (All)</option>
                </select>
            </div>
            
            <button type="submit" class="btn" style="width: 100%; margin-top: 1rem;">सुरक्षित करें (Save)</button>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
