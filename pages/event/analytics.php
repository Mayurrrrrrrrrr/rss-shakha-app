<?php
session_start();
require_once '../../config/db.php';
$pageTitle = 'विश्लेषण (Executive Analytics)';
include 'includes/header.php';

$event_id = $_SESSION['event_id'] ?? 1;

// Initialize variables
$catData = [];
$ageData = [];
$shikshanData = [];
$bhagData = [];
$orgData = [];
$totalParticipants = 0;
$totalOrganizers = 0;
$todayCheckins = 0;
$activeOrganizers = 0;

try {
    // 1. Category Distribution
    $stmt = $pdo->prepare("SELECT COALESCE(category, 'अज्ञात') as category, COUNT(*) as cnt FROM em_participants WHERE event_id = ? GROUP BY category ORDER BY cnt DESC");
    $stmt->execute([$event_id]);
    $catData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Age Group Distribution
    $stmt = $pdo->prepare("SELECT COALESCE(age_group, 'अज्ञात') as age_group, COUNT(*) as cnt FROM em_participants WHERE event_id = ? GROUP BY age_group ORDER BY cnt DESC");
    $stmt->execute([$event_id]);
    $ageData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Sangh Shikshan
    $stmt = $pdo->prepare("SELECT COALESCE(sangh_shikshan, 'अज्ञात') as sangh_shikshan, COUNT(*) as cnt FROM em_participants WHERE event_id = ? GROUP BY sangh_shikshan ORDER BY cnt DESC");
    $stmt->execute([$event_id]);
    $shikshanData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. Bhag/City Distribution (Top 15)
    $stmt = $pdo->prepare("SELECT COALESCE(bhag, city, 'अज्ञात') as loc, COUNT(*) as cnt FROM em_participants WHERE event_id = ? GROUP BY loc ORDER BY cnt DESC LIMIT 15");
    $stmt->execute([$event_id]);
    $bhagData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 5. Total Stats & KPIs
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM em_participants WHERE event_id = ?"); 
    $stmt->execute([$event_id]); 
    $totalParticipants = $stmt->fetchColumn() ?: 0;

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM em_organizers WHERE event_id = ?"); 
    $stmt->execute([$event_id]); 
    $totalOrganizers = $stmt->fetchColumn() ?: 0;

    // Today's unique check-ins
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT participant_id) FROM em_participant_attendance WHERE event_id = ? AND is_present = 1 AND DATE(marked_at) = CURDATE()");
    $stmt->execute([$event_id]);
    $todayCheckins = $stmt->fetchColumn() ?: 0;
    
    // Active organizers (marked attendance today)
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT marked_by) FROM em_participant_attendance WHERE event_id = ? AND DATE(marked_at) = CURDATE() AND marked_by > 0");
    $stmt->execute([$event_id]);
    $activeOrganizers = $stmt->fetchColumn() ?: 0;

    // 6. Organization Distribution 
    $stmt = $pdo->prepare("SELECT COALESCE(organization, 'अज्ञात') as organization, COUNT(*) as cnt FROM em_participants WHERE event_id = ? GROUP BY organization ORDER BY cnt DESC LIMIT 10");
    $stmt->execute([$event_id]);
    $orgData = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Handle error silently
}

$categoriesCount = count($catData);
$ageGroupsCount = count($ageData);
$bhagCount = count($bhagData);
$shikshanLevelsCount = count($shikshanData);

$fillRate = $totalParticipants > 0 ? round(($todayCheckins / $totalParticipants) * 100) : 0;
?>

<style>
/* Executive Analytics Dashboard */
.analytics-dashboard { padding: 1.5rem; animation: fadeInUp 0.6s ease-out; }
@keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

.dashboard-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
.dashboard-header h1 { font-size: 1.8rem; font-weight: 800; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 0.75rem; }

/* KPI Grid */
.kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem; margin-bottom: 2.5rem; }
.kpi-card {
    background: var(--card-bg); border-radius: var(--radius-lg); padding: 1.5rem;
    border: 1px solid rgba(255, 255, 255, 0.05); box-shadow: 0 10px 30px -10px rgba(0,0,0,0.3);
    position: relative; overflow: hidden; display: flex; flex-direction: column; justify-content: space-between;
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.kpi-card:hover { transform: translateY(-4px); border-color: rgba(255,255,255,0.1); }
.kpi-title { font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; font-weight: 600; margin-bottom: 1rem; }
.kpi-value { font-size: 2.5rem; font-weight: 800; line-height: 1; margin-bottom: 0.5rem; }
.kpi-subtitle { font-size: 0.9rem; color: var(--text-muted); }
.kpi-icon { position: absolute; top: 1.5rem; right: 1.5rem; opacity: 0.1; color: currentColor; }
.kpi-card.accent-saffron .kpi-value { background: linear-gradient(135deg, var(--saffron), #fcd34d); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
.kpi-card.accent-saffron .kpi-icon { color: var(--saffron); }
.kpi-card.accent-green .kpi-value { background: linear-gradient(135deg, var(--success), #34d399); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
.kpi-card.accent-green .kpi-icon { color: var(--success); }
.kpi-card.accent-blue .kpi-value { background: linear-gradient(135deg, #3b82f6, #93c5fd); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
.kpi-card.accent-blue .kpi-icon { color: #3b82f6; }
.kpi-card.accent-purple .kpi-value { background: linear-gradient(135deg, #8b5cf6, #c4b5fd); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
.kpi-card.accent-purple .kpi-icon { color: #8b5cf6; }

/* Chart Grid */
.charts-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem; }
@media (max-width: 992px) { .charts-grid { grid-template-columns: 1fr; } }
.chart-card {
    background: rgba(15, 17, 26, 0.6); backdrop-filter: blur(16px);
    border-radius: var(--radius-lg); padding: 1.5rem;
    border: 1px solid rgba(255, 255, 255, 0.05);
    display: flex; flex-direction: column;
}
.chart-card.full-width { grid-column: 1 / -1; }
.chart-card h3 { font-size: 1.1rem; font-weight: 600; color: var(--text-color); margin-bottom: 1.5rem; }
.chart-container { position: relative; height: 320px; width: 100%; flex-grow: 1; display: flex; align-items: center; justify-content: center; }

/* Empty State */
.empty-state {
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    color: var(--text-muted); text-align: center; height: 100%;
}
.empty-state svg { width: 48px; height: 48px; opacity: 0.2; margin-bottom: 1rem; }
.empty-state p { margin: 0; font-size: 0.95rem; }
</style>

<div class="analytics-dashboard">
    <div class="dashboard-header">
        <h1>
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="var(--saffron)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line>
            </svg>
            <?= $pageTitle ?>
        </h1>
        <div style="color: var(--text-muted); font-size: 0.9rem;"><?= date('l, d M Y') ?></div>
    </div>

    <!-- KPI Grid -->
    <div class="kpi-grid">
        <div class="kpi-card accent-saffron">
            <div class="kpi-title">Total Registered</div>
            <div class="kpi-value"><?= number_format($totalParticipants) ?></div>
            <div class="kpi-subtitle">Across <?= $categoriesCount ?> categories</div>
            <svg class="kpi-icon" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
        </div>
        <div class="kpi-card accent-green">
            <div class="kpi-title">Today's Check-ins</div>
            <div class="kpi-value"><?= number_format($todayCheckins) ?></div>
            <div class="kpi-subtitle">Unique participants present</div>
            <svg class="kpi-icon" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
        </div>
        <div class="kpi-card accent-blue">
            <div class="kpi-title">Overall Fill Rate</div>
            <div class="kpi-value"><?= $fillRate ?>%</div>
            <div class="kpi-subtitle">Attendance completion</div>
            <svg class="kpi-icon" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
        </div>
        <div class="kpi-card accent-purple">
            <div class="kpi-title">Active Organizers</div>
            <div class="kpi-value"><?= number_format($activeOrganizers) ?></div>
            <div class="kpi-subtitle">Out of <?= $totalOrganizers ?> total organizers</div>
            <svg class="kpi-icon" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
        </div>
    </div>

    <!-- Charts Grid -->
    <div class="charts-grid">
        <!-- 1. Category -->
        <div class="chart-card">
            <h3>Category Distribution</h3>
            <div class="chart-container">
                <?php if(empty($catData)): ?>
                    <div class="empty-state"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg><p>No category data available</p></div>
                <?php else: ?>
                    <canvas id="categoryChart"></canvas>
                <?php endif; ?>
            </div>
        </div>

        <!-- 2. Age -->
        <div class="chart-card">
            <h3>Age Group Breakdown</h3>
            <div class="chart-container">
                <?php if(empty($ageData)): ?>
                    <div class="empty-state"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg><p>No age data available</p></div>
                <?php else: ?>
                    <canvas id="ageChart"></canvas>
                <?php endif; ?>
            </div>
        </div>

        <!-- 3. Shikshan -->
        <div class="chart-card">
            <h3>Sangh Shikshan Levels</h3>
            <div class="chart-container">
                <?php if(empty($shikshanData)): ?>
                    <div class="empty-state"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg><p>No shikshan data available</p></div>
                <?php else: ?>
                    <canvas id="shikshanChart"></canvas>
                <?php endif; ?>
            </div>
        </div>

        <!-- 4. Bhag -->
        <div class="chart-card">
            <h3>Top 15 Bhag / Cities</h3>
            <div class="chart-container">
                <?php if(empty($bhagData)): ?>
                    <div class="empty-state"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg><p>No location data available</p></div>
                <?php else: ?>
                    <canvas id="bhagChart"></canvas>
                <?php endif; ?>
            </div>
        </div>

        <!-- 5. Organization -->
        <div class="chart-card full-width">
            <h3>Top 10 Organizations</h3>
            <div class="chart-container">
                <?php if(empty($orgData)): ?>
                    <div class="empty-state"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg><p>No organization data available</p></div>
                <?php else: ?>
                    <canvas id="orgChart"></canvas>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    try {
        // Advanced Chart.js Config
        Chart.defaults.color = '#94A3B8';
        Chart.defaults.font.family = "'Noto Sans Devanagari', 'Inter', system-ui, sans-serif";
        
        const colors = ['#f97316', '#3b82f6', '#10b981', '#8b5cf6', '#ec4899', '#0ea5e9', '#eab308', '#ef4444', '#14b8a6', '#6366f1'];
        
        const tooltipSettings = {
            backgroundColor: 'rgba(15, 23, 42, 0.95)',
            titleColor: '#f8fafc',
            bodyColor: '#cbd5e1',
            borderColor: 'rgba(255,255,255,0.1)',
            borderWidth: 1,
            padding: 12,
            cornerRadius: 8,
            titleFont: { size: 14, weight: 'bold' },
            bodyFont: { size: 13 },
            displayColors: true,
            boxPadding: 4
        };

        const extractData = (dataArray, labelKey, valueKey) => ({
            labels: dataArray.map(item => item[labelKey] || 'अज्ञात'),
            values: dataArray.map(item => parseInt(item[valueKey]) || 0)
        });

        // Gradients
        const createGradient = (ctx, colorStart, colorEnd) => {
            const gradient = ctx.createLinearGradient(0, 0, 0, 400);
            gradient.addColorStop(0, colorStart);
            gradient.addColorStop(1, colorEnd);
            return gradient;
        };

        // 1. Category Chart (Doughnut)
        const catData = <?= json_encode($catData) ?>;
        if(catData.length > 0 && document.getElementById('categoryChart')) {
            const ext = extractData(catData, 'category', 'cnt');
            new Chart(document.getElementById('categoryChart').getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: ext.labels,
                    datasets: [{
                        data: ext.values,
                        backgroundColor: colors,
                        borderWidth: 2,
                        borderColor: 'var(--card-bg)',
                        hoverOffset: 8
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false, cutout: '75%',
                    plugins: { legend: { position: 'right', labels: { padding: 20, usePointStyle: true } }, tooltip: tooltipSettings }
                }
            });
        }

        // 2. Age Group Chart (Pie)
        const ageData = <?= json_encode($ageData) ?>;
        if(ageData.length > 0 && document.getElementById('ageChart')) {
            const ext = extractData(ageData, 'age_group', 'cnt');
            new Chart(document.getElementById('ageChart').getContext('2d'), {
                type: 'pie',
                data: {
                    labels: ext.labels,
                    datasets: [{
                        data: ext.values,
                        backgroundColor: [...colors].reverse(),
                        borderWidth: 2,
                        borderColor: 'var(--card-bg)',
                        hoverOffset: 8
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { position: 'right', labels: { padding: 20, usePointStyle: true } }, tooltip: tooltipSettings }
                }
            });
        }

        const gridOptions = {
            x: { grid: { color: 'rgba(255,255,255,0.05)', drawBorder: false }, ticks: { color: '#94A3B8' } },
            y: { grid: { color: 'rgba(255,255,255,0.05)', drawBorder: false }, ticks: { color: '#94A3B8', stepSize: 1, precision: 0 } }
        };

        // 3. Shikshan (Vertical Bar)
        const shikshanData = <?= json_encode($shikshanData) ?>;
        if(shikshanData.length > 0 && document.getElementById('shikshanChart')) {
            const ctx = document.getElementById('shikshanChart').getContext('2d');
            const ext = extractData(shikshanData, 'sangh_shikshan', 'cnt');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ext.labels,
                    datasets: [{
                        label: 'Participants', data: ext.values,
                        backgroundColor: createGradient(ctx, 'rgba(139, 92, 246, 0.9)', 'rgba(139, 92, 246, 0.1)'),
                        borderRadius: { topLeft: 6, topRight: 6 }, borderSkipped: false, barPercentage: 0.5
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: tooltipSettings }, scales: gridOptions }
            });
        }

        // 4. Bhag (Horizontal Bar)
        const bhagData = <?= json_encode($bhagData) ?>;
        if(bhagData.length > 0 && document.getElementById('bhagChart')) {
            const ctx = document.getElementById('bhagChart').getContext('2d');
            const ext = extractData(bhagData, 'loc', 'cnt');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ext.labels,
                    datasets: [{
                        label: 'Participants', data: ext.values,
                        backgroundColor: createGradient(ctx, 'rgba(16, 185, 129, 0.9)', 'rgba(16, 185, 129, 0.2)'),
                        borderRadius: { topRight: 6, bottomRight: 6 }, borderSkipped: false, barPercentage: 0.6
                    }]
                },
                options: {
                    indexAxis: 'y', responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { display: false }, tooltip: tooltipSettings },
                    scales: {
                        x: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#94A3B8', stepSize: 1, precision: 0 } },
                        y: { grid: { display: false }, ticks: { color: '#94A3B8' } }
                    }
                }
            });
        }

        // 5. Organization (Full width bar)
        const orgData = <?= json_encode($orgData) ?>;
        if(orgData.length > 0 && document.getElementById('orgChart')) {
            const ctx = document.getElementById('orgChart').getContext('2d');
            const ext = extractData(orgData, 'organization', 'cnt');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ext.labels,
                    datasets: [{
                        label: 'Participants', data: ext.values,
                        backgroundColor: createGradient(ctx, 'rgba(14, 165, 233, 0.9)', 'rgba(14, 165, 233, 0.1)'),
                        borderRadius: { topLeft: 8, topRight: 8 }, borderSkipped: false, barPercentage: 0.4
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: tooltipSettings }, scales: gridOptions }
            });
        }

    } catch(err) {
        console.error("Error rendering charts: ", err);
    }
});
</script>

<?php include 'includes/footer.php'; ?>
