<?php
session_start();
require_once '../../config/db.php';
include 'includes/header.php';

$search = $_GET['search'] ?? '';

// Build query
$query = "SELECT * FROM em_participants WHERE 1=1";
$params = [];

if ($search) {
    $query .= " AND (name LIKE ? OR phone LIKE ? OR city LIKE ?)";
    $params = ["%$search%", "%$search%", "%$search%"];
}

$query .= " ORDER BY id DESC LIMIT 50";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $participants = $stmt->fetchAll();
} catch (Exception $e) {
    $participants = [];
}
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
    <h2>प्रतिभागी सूची (Participants)</h2>
    <div>
        <a href="?export=csv" class="btn btn-outline">CSV निर्यात (Export)</a>
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
                    <td><span style="background: #e8f5e9; color: #2e7d32; padding: 2px 8px; border-radius: 12px; font-size: 0.85em;">उपस्थित</span></td>
                </tr>
                <?php endforeach; else: ?>
                <tr><td colspan="6" style="text-align: center;">कोई रिकॉर्ड नहीं (No records found)</td></tr>
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
            <button type="button" class="btn">सुरक्षित करें (Save)</button>
            <p style="font-size: 0.8em; color: #666; margin-top: 1rem;">नोट: कार्यक्षमता प्रदर्शन के लिए है। (Note: Functionality is for display)</p>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
