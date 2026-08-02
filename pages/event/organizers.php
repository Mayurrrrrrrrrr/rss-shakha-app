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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_organizer') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'volunteer';

    if ($name && $username && $password && $event_id) {
        try {
            // Check if username exists
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM em_organizers WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetchColumn() > 0) {
                $error = "उपयोगकर्ता नाम पहले से मौजूद है (Username already exists)";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO em_organizers (event_id, name, phone, username, password, role) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$event_id, $name, $phone, $username, $hashed_password, $role]);
                $message = "आयोजक सफलतापूर्वक जोड़ा गया (Organizer added successfully)";
            }
        } catch (PDOException $e) {
            $error = "त्रुटि: (Error: " . $e->getMessage() . ")";
        }
    } else {
        $error = "सभी अनिवार्य फ़ील्ड भरें (Fill all mandatory fields)";
    }
}

// Fetch organizers
$organizers = [];
if ($event_id) {
    $stmt = $pdo->prepare("SELECT * FROM em_organizers WHERE event_id = ? ORDER BY role, name");
    $stmt->execute([$event_id]);
    $organizers = $stmt->fetchAll(PDO::FETCH_ASSOC);
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

    <table>
        <thead>
            <tr>
                <th>नाम (Name)</th>
                <th>फ़ोन (Phone)</th>
                <th>यूज़रनेम (Username)</th>
                <th>भूमिका (Role)</th>
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
            </tr>
            <?php endforeach; ?>
            <?php if (empty($organizers)): ?>
            <tr><td colspan="4">कोई आयोजक नहीं मिला। (No organizers found.)</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal -->
<div id="addModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 1000;">
    <div class="card" style="width: 400px; margin: 4rem auto; position: relative;">
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
                <select name="role" class="form-control" required>
                    <option value="volunteer">स्वयंसेवक (Volunteer)</option>
                    <option value="coordinator">समन्वयक (Coordinator)</option>
                    <option value="admin">प्रशासक (Admin)</option>
                </select>
            </div>
            
            <button type="submit" class="btn" style="width: 100%; margin-top: 1rem;">सुरक्षित करें (Save)</button>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
