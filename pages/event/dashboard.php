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
    
    $event_id = $_SESSION['event_id'] ?? null;
    
    $today_attendance = 0;
    if ($event_id) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM em_attendance a JOIN em_sessions s ON a.session_id = s.id WHERE s.event_id = ? AND DATE(s.start_time) = CURDATE() AND a.status = 'present'");
        $stmt->execute([$event_id]);
        $today_attendance = $stmt->fetchColumn() ?: 0;
    }
    
    $meal_forecast = 0;
    if ($event_id) {
        $stmt = $pdo->prepare("SELECT SUM(expected_upcoming) FROM em_meals WHERE event_id = ? AND meal_date = CURDATE()");
        $stmt->execute([$event_id]);
        $meal_forecast = $stmt->fetchColumn() ?: 0;
    }
} catch (Exception $e) {
    // If tables don't exist yet, ignore errors for dashboard stats
}
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
    <div class="card" style="border-top: 4px solid #9c27b0;">
        <h3>आज की हाजिरी (Today's Attendance)</h3>
        <p style="font-size: 2rem; font-weight: bold; color: #9c27b0;"><?= $today_attendance ?></p>
    </div>
    <div class="card" style="border-top: 4px solid #4caf50;">
        <h3>आवास स्थिति</h3>
        <p style="font-size: 2rem; font-weight: bold; color: #4caf50;"><?= $rooms_filled ?> / <?= $total_rooms ?></p>
        <?php 
            $occupancy_pct = $total_rooms > 0 ? round(($rooms_filled / $total_rooms) * 100) : 0;
            $status_color = $occupancy_pct > 80 ? 'status-red' : ($occupancy_pct > 50 ? 'status-yellow' : 'status-green');
        ?>
        <div class="status-bar">
            <div class="status-fill <?= $status_color ?>" style="width: <?= $occupancy_pct ?>%;"></div>
        </div>
        <p style="font-size: 0.85rem; margin-top: 0.5rem; text-align: right;"><?= $occupancy_pct ?>% भरा हुआ</p>
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
        <?php 
        $vyavastha = $_SESSION['event_vyavastha'] ?? 'all';
        $role = $_SESSION['event_role'] ?? '';
        
        if ($role === 'admin' || $vyavastha === 'all'):
        ?>
        <a href="participants.php?action=add" class="btn" style="display: block; margin-bottom: 1rem; text-align: center;">स्पॉट एंट्री (Spot Entry)</a>
        <button onclick="window.print()" class="btn btn-outline" style="display: block; width: 100%;">रिपोर्ट प्रिंट करें (Print Report)</button>
        <?php elseif ($vyavastha === 'hajiri'): ?>
        <a href="attendance.php" class="btn" style="display: block; margin-bottom: 1rem; text-align: center;">हाजिरी लें (Take Attendance)</a>
        <?php elseif ($vyavastha === 'bhojan'): ?>
        <a href="food.php" class="btn" style="display: block; margin-bottom: 1rem; text-align: center;">भोजन अपडेट करें (Update Meals)</a>
        <?php elseif ($vyavastha === 'nivas'): ?>
        <a href="rooms.php" class="btn" style="display: block; margin-bottom: 1rem; text-align: center;">कमरा आबंटन (Room Allocation)</a>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
