<?php
session_start();
require_once '../../config/db.php';

// Handle CSV Export
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename=participants.csv');
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM for Excel Hindi
    fputcsv($output, ['ID', 'Name', 'Category', 'City', 'Phone', 'Entry Type', 'Event ID']);
    $stmt = $pdo->query("SELECT id, name, category, city, phone, entry_type, event_id FROM em_participants ORDER BY id DESC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}

// Handle Spot Entry
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'spot_entry') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $room_id = $_POST['room_id'] ?? null;
    $event_id = $_SESSION['event_id'] ?? 1;
    $registered_by = $_SESSION['event_user_id'] ?? 0;
    
    if ($name && $phone) {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("INSERT INTO em_participants (event_id, name, phone, city, entry_type, registered_by) VALUES (?, ?, ?, ?, 'spot', ?)");
            $stmt->execute([$event_id, $name, $phone, $city, $registered_by]);
            $participant_id = $pdo->lastInsertId();

            if ($room_id) {
                $stmtAllot = $pdo->prepare("INSERT INTO em_room_allotments (event_id, room_id, allottee_type, allottee_id, allotted_by) VALUES (?, ?, 'participant', ?, ?)");
                $stmtAllot->execute([$event_id, $room_id, $participant_id, $registered_by]);
                
                $pdo->prepare("UPDATE em_rooms SET occupancy = occupancy + 1 WHERE id = ?")->execute([$room_id]);
            }
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
        }
    }
    header("Location: participants.php");
    exit;
}

// Handle CSV Import
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'import_csv' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file']['tmp_name'];
    $event_id = $_SESSION['event_id'] ?? 1;
    if ($file && is_uploaded_file($file)) {
        if (($handle = fopen($file, "r")) !== FALSE) {
            $header = fgetcsv($handle, 1000, ","); // skip header
            $stmt = $pdo->prepare("INSERT INTO em_participants (event_id, name, category, city, phone, entry_type) VALUES (?, ?, ?, ?, ?, 'pre-registered')");
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if (count($data) >= 2) {
                    $name = $data[0] ?? '';
                    $category = $data[1] ?? 'सामान्य';
                    $city = $data[2] ?? '';
                    $phone = $data[3] ?? '';
                    if ($name) {
                        $stmt->execute([$event_id, $name, $category, $city, $phone]);
                    }
                }
            }
            fclose($handle);
        }
    }
    header("Location: participants.php");
    exit;
}

include 'includes/header.php';

$search = $_GET['search'] ?? '';

// Build query
$query = "SELECT p.*, r.room_name FROM em_participants p 
          LEFT JOIN em_room_allotments ra ON ra.allottee_id = p.id AND ra.allottee_type = 'participant' 
          LEFT JOIN em_rooms r ON r.id = ra.room_id 
          WHERE p.is_deleted = 0";
$params = [];

if ($search) {
    $query .= " AND (p.name LIKE ? OR p.phone LIKE ? OR p.city LIKE ?)";
    $params = ["%$search%", "%$search%", "%$search%"];
}

$query .= " ORDER BY p.id DESC LIMIT 50";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $participants = $stmt->fetchAll();
} catch (Exception $e) {
    $participants = [];
}

// Fetch rooms with remaining capacity for allotment selection dropdown
try {
    $rooms = $pdo->query("SELECT id, room_name, capacity, occupancy FROM em_rooms WHERE occupancy < capacity")->fetchAll();
} catch (Exception $e) {
    $rooms = [];
}
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
    <h2>प्रतिभागी सूची (Participants)</h2>
    <div style="display: flex; gap: 0.5rem;">
        <a href="?export=csv" class="btn btn-outline">CSV निर्यात (Export)</a>
        <button class="btn btn-outline" onclick="document.getElementById('importModal').style.display='block'">CSV आयात (Import)</button>
        <button class="btn" onclick="document.getElementById('addModal').style.display='block'">नया पंजीकरण (New Registration)</button>
    </div>
</div>

<div class="card">
    <form method="GET" action="" style="display: flex; gap: 1rem; margin-bottom: 1rem;">
        <input type="text" name="search" class="form-control" placeholder="नाम, फोन, या नगर से खोजें..." value="<?= htmlspecialchars($search) ?>">
        <button type="submit" class="btn">खोजें (Search)</button>
        <?php if($search): ?>
            <a href="participants.php" class="btn btn-outline">रीसेट</a>
        <?php endif; ?>
    </form>

    <div style="overflow-x: auto;">
        <table>
            <thead>
                <tr>
                    <th>आईडी (ID)</th>
                    <th>नाम (Name)</th>
                    <th>श्रेणी (Category)</th>
                    <th>नगर (City)</th>
                    <th>संपर्क (Phone)</th>
                    <th>आवास आवंटन (Room Allotment)</th>
                    <th>स्थिति (Status)</th>
                </tr>
            </thead>
            <tbody>
                <?php if($participants): foreach($participants as $p): ?>
                <tr>
                    <td><?= htmlspecialchars($p['id'] ?? '') ?></td>
                    <td><?= htmlspecialchars($p['name'] ?? '') ?></td>
                    <td><?= htmlspecialchars($p['category'] ?? 'सामान्य') ?></td>
                    <td><?= htmlspecialchars($p['city'] ?? '') ?></td>
                    <td><?= htmlspecialchars($p['phone'] ?? '') ?></td>
                    <td><?= htmlspecialchars($p['room_name'] ?? 'आवंटित नहीं (Not Allotted)') ?></td>
                    <td><span style="background: #e8f5e9; color: #2e7d32; padding: 2px 8px; border-radius: 12px; font-size: 0.85em;">उपस्थित</span></td>
                </tr>
                <?php endforeach; else: ?>
                <tr><td colspan="7" style="text-align: center;">कोई रिकॉर्ड नहीं (No records found)</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal -> simple implementation -->
<div id="addModal" style="display:<?= isset($_GET['action']) && $_GET['action'] == 'add' ? 'block' : 'none' ?>; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000;">
    <div class="card" style="max-width:500px; margin: 10% auto; position:relative;">
        <h3>स्पॉट एंट्री (Spot Entry)</h3>
        <span onclick="document.getElementById('addModal').style.display='none'" style="position:absolute; right:1.5rem; top:1.5rem; cursor:pointer; font-size:1.5rem;">&times;</span>
        <form method="POST" action="participants.php">
            <input type="hidden" name="action" value="spot_entry">
            <div class="form-group">
                <label>नाम (Name)</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="form-group">
                <label>संपर्क (Phone)</label>
                <input type="text" name="phone" class="form-control" required>
            </div>
            <div class="form-group">
                <label>नगर (City)</label>
                <input type="text" name="city" class="form-control">
            </div>
            <div class="form-group">
                <label>आवास पूर्व आवंटन (Pre-Allot Room)</label>
                <select name="room_id" class="form-control">
                    <option value="">-- आवंटित न करें (Do Not Allot) --</option>
                    <?php foreach($rooms as $r): ?>
                        <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['room_name']) ?> (बचा स्थान: <?= $r['capacity'] - $r['occupancy'] ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn">सुरक्षित करें (Save)</button>
        </form>
    </div>
</div>

<div id="importModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000;">
    <div class="card" style="max-width:500px; margin: 10% auto; position:relative;">
        <h3>CSV आयात (CSV Import)</h3>
        <span onclick="document.getElementById('importModal').style.display='none'" style="position:absolute; right:1.5rem; top:1.5rem; cursor:pointer; font-size:1.5rem;">&times;</span>
        <form method="POST" action="participants.php" enctype="multipart/form-data">
            <input type="hidden" name="action" value="import_csv">
            <div class="form-group">
                <label>CSV फाइल चुनें (Select CSV File)</label>
                <input type="file" name="csv_file" accept=".csv" class="form-control" required>
                <p style="font-size: 0.8em; margin-top: 5px;">Format: Name, Category, City, Phone</p>
            </div>
            <button type="submit" class="btn">आयात करें (Import)</button>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
