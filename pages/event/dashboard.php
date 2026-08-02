<?php
session_start();
require_once '../../config/db.php';
include 'includes/header.php';

$total_participants = 0;
$total_organizers = 0;
$rooms_filled = 0;
$total_rooms = 0;
$recent_participants = [];

try {
    $total_participants = $pdo->query("SELECT COUNT(*) FROM em_participants")->fetchColumn() ?: 0;
    $total_organizers = $pdo->query("SELECT COUNT(*) FROM em_organizers")->fetchColumn() ?: 0;
    $rooms_filled = $pdo->query("SELECT COUNT(*) FROM em_rooms WHERE occupancy > 0")->fetchColumn() ?: 0;
    $total_rooms = $pdo->query("SELECT COUNT(*) FROM em_rooms")->fetchColumn() ?: 0;
    $recent_participants = $pdo->query("SELECT name, city, phone FROM em_participants ORDER BY id DESC LIMIT 5")->fetchAll();
} catch (Exception $e) {
    // If tables don't exist yet, ignore errors for dashboard stats
}

$meal_forecast = $total_participants + $total_organizers + 20;
?>

<h2>डैशबोर्ड (Dashboard)</h2>

<div class="grid">
    <div class="card" style="border-top: 4px solid var(--saffron);">
        <h3>कुल प्रतिभागी</h3>
        <p style="font-size: 2rem; font-weight: bold; color: var(--saffron);"><?= $total_participants ?></p>
    </div>
    <div class="card" style="border-top: 4px solid var(--amber);">
        <h3>प्रबंधक</h3>
        <p style="font-size: 2rem; font-weight: bold; color: var(--amber);"><?= $total_organizers ?></p>
    </div>
    <div class="card" style="border-top: 4px solid #4caf50;">
        <h3>आवास स्थिति</h3>
        <p style="font-size: 2rem; font-weight: bold; color: #4caf50;"><?= $rooms_filled ?> / <?= $total_rooms ?></p>
    </div>
    <div class="card" style="border-top: 4px solid #2196f3;">
        <h3>आज का भोजन अनुमान</h3>
        <p style="font-size: 2rem; font-weight: bold; color: #2196f3;"><?= $meal_forecast ?></p>
    </div>
</div>

<div class="grid" style="grid-template-columns: 2fr 1fr; margin-top: 1rem;">
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h3>नवीनतम पंजीकरण (Recent Registrations)</h3>
            <a href="participants.php" class="btn btn-outline">सभी देखें</a>
        </div>
        <table>
            <thead>
                <tr>
                    <th>नाम (Name)</th>
                    <th>नगर (City)</th>
                    <th>संपर्क (Phone)</th>
                </tr>
            </thead>
            <tbody>
                <?php if($recent_participants): foreach($recent_participants as $p): ?>
                <tr>
                    <td><?= htmlspecialchars($p['name']) ?></td>
                    <td><?= htmlspecialchars($p['city']) ?></td>
                    <td><?= htmlspecialchars($p['phone']) ?></td>
                </tr>
                <?php endforeach; else: ?>
                <tr><td colspan="3">कोई रिकॉर्ड नहीं</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <div class="card">
        <h3>त्वरित क्रिया (Quick Actions)</h3>
        <a href="participants.php?action=add" class="btn" style="display: block; margin-bottom: 1rem; text-align: center;">स्पॉट एंट्री (Spot Entry)</a>
        <button onclick="window.print()" class="btn btn-outline" style="display: block; width: 100%;">रिपोर्ट प्रिंट करें (Print Report)</button>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
