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
    
    // Analytics queries
    $catData = [];
    $ageData = [];
    $shikshanData = [];
    $bhagData = [];
    
    if ($event_id) {
        // Categories
        $stmt = $pdo->prepare("SELECT category, COUNT(*) as cnt FROM em_participants WHERE event_id = ? GROUP BY category");
        $stmt->execute([$event_id]);
        $catData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Age Groups
        $stmt = $pdo->prepare("SELECT age_group, COUNT(*) as cnt FROM em_participants WHERE event_id = ? GROUP BY age_group");
        $stmt->execute([$event_id]);
        $ageData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Sangh Shikshan
        $stmt = $pdo->prepare("SELECT sangh_shikshan, COUNT(*) as cnt FROM em_participants WHERE event_id = ? GROUP BY sangh_shikshan");
        $stmt->execute([$event_id]);
        $shikshanData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Bhag
        $stmt = $pdo->prepare("SELECT COALESCE(bhag, city) as loc, COUNT(*) as cnt FROM em_participants WHERE event_id = ? AND COALESCE(bhag, city) IS NOT NULL AND COALESCE(bhag, city) != '' GROUP BY loc ORDER BY cnt DESC LIMIT 10");
        $stmt->execute([$event_id]);
        $bhagData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    // If tables don't exist yet, ignore errors for dashboard stats
}
?>

<h2>डैशबोर्ड (Dashboard)</h2>
<p style="color: var(--saffron); margin-top: -10px; margin-bottom: 20px; font-weight: 500;">
    आयोजन: <?= htmlspecialchars($_SESSION['event_name'] ?? 'Unknown Event') ?>
</p>

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

<div class="card" style="margin-top: 2rem;">
    <h2 style="margin-bottom: 1rem;">प्रतिभागी विश्लेषण (Participants Analytics)</h2>
    <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 2rem;">
        
        <!-- Category Chart -->
        <div>
            <h4 style="text-align: center;">श्रेणी (Category)</h4>
            <canvas id="categoryChart"></canvas>
        </div>
        
        <!-- Age Group Chart -->
        <div>
            <h4 style="text-align: center;">आयु वर्ग (Age Group)</h4>
            <canvas id="ageChart"></canvas>
        </div>
        
        <!-- Sangh Shikshan Chart -->
        <div>
            <h4 style="text-align: center;">संघ शिक्षा (Sangh Shikshan)</h4>
            <canvas id="shikshanChart"></canvas>
        </div>
        
        <!-- Bhag Chart -->
        <div>
            <h4 style="text-align: center;">भाग/नगर (Bhag/City)</h4>
            <canvas id="bhagChart"></canvas>
        </div>
        
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Prepare Data
    const catData = <?= json_encode($catData) ?>;
    const ageData = <?= json_encode($ageData) ?>;
    const shikshanData = <?= json_encode($shikshanData) ?>;
    const bhagData = <?= json_encode($bhagData) ?>;

    const chartColors = ['#0D9488', '#FF6B00', '#4CAF50', '#9C27B0', '#FFC107', '#2196F3', '#F44336', '#E91E63', '#795548', '#607D8B'];

    // Helper to render chart
    function renderChart(ctxId, type, dataArray, labelField, valueField, title) {
        if (!dataArray || dataArray.length === 0) return;
        const labels = dataArray.map(d => d[labelField] || 'N/A');
        const values = dataArray.map(d => d[valueField]);
        
        new Chart(document.getElementById(ctxId), {
            type: type,
            data: {
                labels: labels,
                datasets: [{
                    label: 'प्रतिभागी संख्या',
                    data: values,
                    backgroundColor: chartColors,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: (type === 'pie' || type === 'doughnut') ? 'bottom' : 'none',
                    }
                },
                scales: (type === 'bar') ? {
                    y: { beginAtZero: true }
                } : {}
            }
        });
    }

    // Render all 4 charts
    window.onload = function() {
        renderChart('categoryChart', 'pie', catData, 'category', 'cnt', 'Category');
        renderChart('ageChart', 'doughnut', ageData, 'age_group', 'cnt', 'Age Group');
        renderChart('shikshanChart', 'bar', shikshanData, 'sangh_shikshan', 'cnt', 'Sangh Shikshan');
        
        // Custom horizontal bar for Bhag
        if (bhagData && bhagData.length > 0) {
            new Chart(document.getElementById('bhagChart'), {
                type: 'bar',
                data: {
                    labels: bhagData.map(d => d.loc || 'N/A'),
                    datasets: [{
                        label: 'प्रतिभागी संख्या',
                        data: bhagData.map(d => d.cnt),
                        backgroundColor: chartColors,
                        borderWidth: 1
                    }]
                },
                options: {
                    indexAxis: 'y', // horizontal
                    responsive: true,
                    plugins: { legend: { display: false } },
                    scales: { x: { beginAtZero: true } }
                }
            });
        }
    };
</script>

<?php include 'includes/footer.php'; ?>
