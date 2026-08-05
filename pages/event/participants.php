<?php
session_start();
require_once '../../config/db.php';

// Handle CSV Export
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename=participants.csv');
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM for Excel Hindi
    fputcsv($output, ['भ्रमणध्वनी', 'पूर्ण नाव', 'दायित्व', 'स्तर / प्रकार', 'संघटना', 'संघ शिक्षण', 'वयोगट', 'निवासी नगर', 'निवासी वस्ती', 'अणुडाक', 'श्रेणी', 'भाग', 'संभाव्य दुहेरी नोंद']);
    
    $stmt = $pdo->query("SELECT phone, name, responsibility, level_type, organization, sangh_shikshan, age_group, city, vasti, email, category, bhag FROM em_participants WHERE is_deleted = 0 ORDER BY id DESC");
    $export_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate duplicates for export
    $phone_counts = [];
    $name_city_counts = [];
    foreach($export_data as $row) {
        if(!empty($row['phone'])) $phone_counts[$row['phone']] = ($phone_counts[$row['phone']] ?? 0) + 1;
        if(!empty($row['name']) && !empty($row['city'])) {
            $key = mb_strtolower(trim($row['name'])) . '|' . mb_strtolower(trim($row['city']));
            $name_city_counts[$key] = ($name_city_counts[$key] ?? 0) + 1;
        }
    }
    
    foreach ($export_data as $row) {
        $is_dup = false;
        if(!empty($row['phone']) && ($phone_counts[$row['phone']] > 1)) $is_dup = true;
        if(!empty($row['name']) && !empty($row['city'])) {
            $key = mb_strtolower(trim($row['name'])) . '|' . mb_strtolower(trim($row['city']));
            if(($name_city_counts[$key] ?? 0) > 1) $is_dup = true;
        }
        $row['duplicate'] = $is_dup ? 'होय (Yes)' : '-';
        fputcsv($output, array_values($row));
    }
    fclose($output);
    exit;
}

// Handle Spot Entry
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'spot_entry') {
    $organization = trim($_POST['organization'] ?? '');
    $level_type = trim($_POST['level_type'] ?? '');
    $responsibility = trim($_POST['responsibility'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $sangh_shikshan = trim($_POST['sangh_shikshan'] ?? '');
    $age_group = trim($_POST['age_group'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $vasti = trim($_POST['vasti'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $bhag = trim($_POST['bhag'] ?? '');
    $room_id = $_POST['room_id'] ?? null;
    $event_id = $_SESSION['event_id'] ?? 1;
    $registered_by = $_SESSION['event_user_id'] ?? 0;
    
    if ($name && $phone) {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("INSERT INTO em_participants (event_id, organization, level_type, responsibility, name, phone, sangh_shikshan, age_group, city, vasti, email, category, bhag, entry_type, registered_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'spot', ?)");
            $stmt->execute([$event_id, $organization, $level_type, $responsibility, $name, $phone, $sangh_shikshan, $age_group, $city, $vasti, $email, $category, $bhag, $registered_by]);
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
            $stmt = $pdo->prepare("INSERT INTO em_participants (event_id, phone, name, responsibility, level_type, organization, sangh_shikshan, age_group, city, vasti, email, category, bhag, entry_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pre-registered')");
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if (count($data) >= 4) { // requiring at least a few columns
                    $phone = $data[0] ?? '';
                    $name = $data[1] ?? '';
                    $resp = $data[2] ?? '';
                    $lvl = $data[3] ?? '';
                    $org = $data[4] ?? '';
                    $shikshan = $data[5] ?? '';
                    $age = $data[6] ?? '';
                    $city = $data[7] ?? '';
                    $vasti = $data[8] ?? '';
                    $email = $data[9] ?? '';
                    $cat = $data[10] ?? 'सामान्य';
                    $bhag = $data[11] ?? '';
                    if ($name) {
                        $stmt->execute([$event_id, $phone, $name, $resp, $lvl, $org, $shikshan, $age, $city, $vasti, $email, $cat, $bhag]);
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
    $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Duplicate calculation
    $phone_counts = [];
    $name_city_counts = [];
    foreach($participants as $p) {
        if(!empty($p['phone'])) $phone_counts[$p['phone']] = ($phone_counts[$p['phone']] ?? 0) + 1;
        if(!empty($p['name']) && !empty($p['city'])) {
            $key = mb_strtolower(trim($p['name'])) . '|' . mb_strtolower(trim($p['city']));
            $name_city_counts[$key] = ($name_city_counts[$key] ?? 0) + 1;
        }
    }
    
    foreach($participants as &$p) {
        $is_dup = false;
        if(!empty($p['phone']) && ($phone_counts[$p['phone']] > 1)) $is_dup = true;
        if(!empty($p['name']) && !empty($p['city'])) {
            $key = mb_strtolower(trim($p['name'])) . '|' . mb_strtolower(trim($p['city']));
            if(($name_city_counts[$key] ?? 0) > 1) $is_dup = true;
        }
        $p['is_duplicate'] = $is_dup;
    }
    unset($p);
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
                    <th>भ्रमणध्वनी</th>
                    <th>पूर्ण नाव</th>
                    <th>दायित्व</th>
                    <th>स्तर / प्रकार</th>
                    <th>संघटना</th>
                    <th>संघ शिक्षण</th>
                    <th>वयोगट</th>
                    <th>निवासी नगर</th>
                    <th>निवासी वस्ती</th>
                    <th>अणुडाक</th>
                    <th>श्रेणी</th>
                    <th>भाग</th>
                    <th>संभाव्य दुहेरी नोंद</th>
                </tr>
            </thead>
            <tbody>
                <?php if($participants): foreach($participants as $p): ?>
                <tr>
                    <td><?= htmlspecialchars($p['phone'] ?? '') ?></td>
                    <td><?= htmlspecialchars($p['name'] ?? '') ?></td>
                    <td><?= htmlspecialchars($p['responsibility'] ?? '') ?></td>
                    <td><?= htmlspecialchars($p['level_type'] ?? '') ?></td>
                    <td><?= htmlspecialchars($p['organization'] ?? '') ?></td>
                    <td><?= htmlspecialchars($p['sangh_shikshan'] ?? '') ?></td>
                    <td><?= htmlspecialchars($p['age_group'] ?? '') ?></td>
                    <td><?= htmlspecialchars($p['city'] ?? '') ?></td>
                    <td><?= htmlspecialchars($p['vasti'] ?? '') ?></td>
                    <td><?= htmlspecialchars($p['email'] ?? '') ?></td>
                    <td><?= htmlspecialchars($p['category'] ?? '') ?></td>
                    <td><?= htmlspecialchars($p['bhag'] ?? '') ?></td>
                    <td><?= !empty($p['is_duplicate']) ? '<span style="background: #fff3e0; color: #e65100; padding: 2px 8px; border-radius: 12px; font-size: 0.85em;">⚠️ होय (Yes)</span>' : '-' ?></td>
                </tr>
                <?php endforeach; else: ?>
                <tr><td colspan="13" style="text-align: center;">कोई रिकॉर्ड नहीं (No records found)</td></tr>
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
                <label>भ्रमणध्वनी (Phone)</label>
                <input type="text" name="phone" class="form-control" required>
            </div>
            <div class="form-group">
                <label>पूर्ण नाव (Full Name)</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="form-group">
                <label>दायित्व (Responsibility)</label>
                <input type="text" name="responsibility" class="form-control">
            </div>
            <div class="form-group">
                <label>स्तर / प्रकार (Level / Type)</label>
                <input type="text" name="level_type" class="form-control">
            </div>
            <div class="form-group">
                <label>संघटना (Organization)</label>
                <input type="text" name="organization" class="form-control">
            </div>
            <div class="form-group">
                <label>संघ शिक्षण (Sangh Shikshan)</label>
                <input type="text" name="sangh_shikshan" class="form-control">
            </div>
            <div class="form-group">
                <label>वयोगट (Age Group)</label>
                <input type="text" name="age_group" class="form-control">
            </div>
            <div class="form-group">
                <label>निवासी नगर (City)</label>
                <input type="text" name="city" class="form-control">
            </div>
            <div class="form-group">
                <label>निवासी वस्ती (Vasti)</label>
                <input type="text" name="vasti" class="form-control">
            </div>
            <div class="form-group">
                <label>अणुडाक (Email)</label>
                <input type="email" name="email" class="form-control">
            </div>
            <div class="form-group">
                <label>श्रेणी (Category)</label>
                <input type="text" name="category" class="form-control">
            </div>
            <div class="form-group">
                <label>भाग (Bhag)</label>
                <input type="text" name="bhag" class="form-control">
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
                <p style="font-size: 0.8em; margin-top: 5px;">Format: भ्रमणध्वनी, पूर्ण नाव, दायित्व, स्तर / प्रकार, संघटना, संघ शिक्षण, वयोगट, निवासी नगर, निवासी वस्ती, अणुडाक, श्रेणी, भाग</p>
            </div>
            <button type="submit" class="btn">आयात करें (Import)</button>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
