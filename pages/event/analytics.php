<?php
session_start();
require_once '../../config/db.php';
$pageTitle = 'प्रतिभागी विश्लेषण (Participant Analytics)';
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

    // 5. Total Stats
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM em_participants WHERE event_id = ?"); 
    $stmt->execute([$event_id]); 
    $totalParticipants = $stmt->fetchColumn() ?: 0;

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM em_organizers WHERE event_id = ?"); 
    $stmt->execute([$event_id]); 
    $totalOrganizers = $stmt->fetchColumn() ?: 0;

    // 6. Organization Distribution 
    $stmt = $pdo->prepare("SELECT COALESCE(organization, 'अज्ञात') as organization, COUNT(*) as cnt FROM em_participants WHERE event_id = ? GROUP BY organization ORDER BY cnt DESC LIMIT 10");
    $stmt->execute([$event_id]);
    $orgData = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Handle error silently, e.g. if column does not exist
}

$categoriesCount = count($catData);
$ageGroupsCount = count($ageData);
$bhagCount = count($bhagData);
$shikshanLevelsCount = count($shikshanData);
?>

<style>
/* Premium Analytics Dashboard Styles */
.analytics-dashboard {
    padding: 1.5rem;
    animation: fadeInUp 0.6s ease-out;
}

@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.dashboard-header {
    margin-bottom: 2rem;
}

.dashboard-header h1 {
    font-size: 1.8rem;
    font-weight: 700;
    color: var(--text-color);
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

/* Stat Cards */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2.5rem;
}

.stat-card {
    background: var(--card-bg);
    border-radius: 16px;
    padding: 1.5rem;
    position: relative;
    border: 1px solid var(--border-color);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.3);
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--saffron), #8B5CF6);
    opacity: 0.8;
}

.stat-card h3 {
    font-size: 0.9rem;
    color: var(--text-muted);
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 0.5rem;
}

.stat-card .value {
    font-size: 2rem;
    font-weight: 700;
    color: var(--text-color);
    background: linear-gradient(135deg, #fff, #cbd5e1);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

/* Chart Grid */
.charts-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

@media (max-width: 992px) {
    .charts-grid {
        grid-template-columns: 1fr;
    }
}

.chart-card {
    background: rgba(26, 29, 39, 0.6);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border-radius: 16px;
    padding: 1.5rem;
    border: 1px solid rgba(255, 255, 255, 0.05);
    box-shadow: 0 4px 24px rgba(0, 0, 0, 0.15);
}

.chart-card h3 {
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--text-color);
    margin-bottom: 1.5rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid var(--border-color);
}

.chart-container {
    position: relative;
    height: 300px;
    width: 100%;
}

.full-width-chart {
    grid-column: 1 / -1;
}

</style>

<div class="analytics-dashboard">
    <div class="dashboard-header">
        <h1>
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--saffron);">
                <line x1="18" y1="20" x2="18" y2="10"></line>
                <line x1="12" y1="20" x2="12" y2="4"></line>
                <line x1="6" y1="20" x2="6" y2="14"></line>
            </svg>
            <?= $pageTitle ?>
        </h1>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <h3>Total Participants</h3>
            <div class="value"><?= number_format($totalParticipants) ?></div>
        </div>
        <div class="stat-card">
            <h3>Total Organizers</h3>
            <div class="value"><?= number_format($totalOrganizers) ?></div>
        </div>
        <div class="stat-card">
            <h3>Categories</h3>
            <div class="value"><?= number_format($categoriesCount) ?></div>
        </div>
        <div class="stat-card">
            <h3>Age Groups</h3>
            <div class="value"><?= number_format($ageGroupsCount) ?></div>
        </div>
        <div class="stat-card">
            <h3>Bhags / Cities</h3>
            <div class="value"><?= number_format($bhagCount) ?></div>
        </div>
        <div class="stat-card">
            <h3>Shikshan Levels</h3>
            <div class="value"><?= number_format($shikshanLevelsCount) ?></div>
        </div>
    </div>

    <div class="charts-grid">
        <!-- 1. Category Distribution -->
        <div class="chart-card">
            <h3>Category Distribution</h3>
            <div class="chart-container">
                <canvas id="categoryChart"></canvas>
            </div>
        </div>

        <!-- 2. Age Group Breakdown -->
        <div class="chart-card">
            <h3>Age Group Breakdown</h3>
            <div class="chart-container">
                <canvas id="ageChart"></canvas>
            </div>
        </div>

        <!-- 3. Sangh Shikshan Levels -->
        <div class="chart-card">
            <h3>Sangh Shikshan Levels</h3>
            <div class="chart-container">
                <canvas id="shikshanChart"></canvas>
            </div>
        </div>

        <!-- 4. Bhag/City Distribution -->
        <div class="chart-card">
            <h3>Top 15 Bhag/City Distribution</h3>
            <div class="chart-container">
                <canvas id="bhagChart"></canvas>
            </div>
        </div>

        <!-- 5. Organization Distribution -->
        <div class="chart-card full-width-chart">
            <h3>Top 10 Organizations</h3>
            <div class="chart-container">
                <canvas id="orgChart"></canvas>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    try {
        // Dark theme config
        Chart.defaults.color = '#94A3B8';
        Chart.defaults.font.family = "'Noto Sans Devanagari', 'Inter', sans-serif";
        
        const colors = ['#0D9488', '#F97316', '#8B5CF6', '#EC4899', '#06B6D4', '#84CC16', '#F59E0B', '#EF4444', '#6366F1', '#14B8A6', '#F43F5E', '#A855F7', '#3B82F6', '#22C55E', '#E11D48'];
        const tooltipSettings = {
            backgroundColor: 'rgba(21, 24, 33, 0.9)',
            titleColor: '#fff',
            bodyColor: '#cbd5e1',
            borderColor: '#2D3748',
            borderWidth: 1,
            padding: 10,
            cornerRadius: 8
        };

        // Helper to get labels and data (Parse strings to integers!)
        const extractData = (dataArray, labelKey, valueKey) => {
            return {
                labels: dataArray.map(item => item[labelKey] || 'अज्ञात'),
                values: dataArray.map(item => parseInt(item[valueKey]) || 0)
            };
        };

        // 1. Category Chart (Doughnut)
        const catData = <?= json_encode($catData) ?>;
        const catExtracted = extractData(catData, 'category', 'cnt');
        new Chart(document.getElementById('categoryChart'), {
            type: 'doughnut',
            data: {
                labels: catExtracted.labels,
                datasets: [{
                    data: catExtracted.values,
                    backgroundColor: colors,
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'right' },
                    tooltip: tooltipSettings
                },
                cutout: '70%'
            }
        });

        // 2. Age Group Chart (Pie)
        const ageData = <?= json_encode($ageData) ?>;
        const ageExtracted = extractData(ageData, 'age_group', 'cnt');
        new Chart(document.getElementById('ageChart'), {
            type: 'pie',
            data: {
                labels: ageExtracted.labels,
                datasets: [{
                    data: ageExtracted.values,
                    backgroundColor: colors.slice(3).concat(colors.slice(0, 3)),
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'right' },
                    tooltip: tooltipSettings
                }
            }
        });

        // Grid settings for Bar charts
        const gridOptions = {
            x: { 
                grid: { color: 'rgba(255, 255, 255, 0.05)', drawBorder: false },
                ticks: { color: '#94A3B8' }
            },
            y: {
                grid: { color: 'rgba(255, 255, 255, 0.05)', drawBorder: false },
                ticks: { color: '#94A3B8', stepSize: 1 }
            }
        };

        // 3. Sangh Shikshan Chart (Vertical Bar)
        const shikshanData = <?= json_encode($shikshanData) ?>;
        const shikshanExtracted = extractData(shikshanData, 'sangh_shikshan', 'cnt');
        
        // Create gradient
        const ctxShikshan = document.getElementById('shikshanChart').getContext('2d');
        let shikshanGradient = ctxShikshan.createLinearGradient(0, 0, 0, 400);
        shikshanGradient.addColorStop(0, 'rgba(139, 92, 246, 0.8)');
        shikshanGradient.addColorStop(1, 'rgba(139, 92, 246, 0.2)');

        new Chart(ctxShikshan, {
            type: 'bar',
            data: {
                labels: shikshanExtracted.labels,
                datasets: [{
                    label: 'Participants',
                    data: shikshanExtracted.values,
                    backgroundColor: shikshanGradient,
                    borderRadius: 6,
                    borderWidth: 0,
                    barPercentage: 0.6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: tooltipSettings
                },
                scales: gridOptions
            }
        });

        // 4. Bhag/City Chart (Horizontal Bar)
        const bhagData = <?= json_encode($bhagData) ?>;
        const bhagExtracted = extractData(bhagData, 'loc', 'cnt');
        new Chart(document.getElementById('bhagChart'), {
            type: 'bar',
            data: {
                labels: bhagExtracted.labels,
                datasets: [{
                    label: 'Participants',
                    data: bhagExtracted.values,
                    backgroundColor: colors.slice(5).concat(colors.slice(0, 5)),
                    borderRadius: 4,
                    borderWidth: 0
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: tooltipSettings
                },
                scales: {
                    x: {
                        grid: { color: 'rgba(255, 255, 255, 0.05)' },
                        ticks: { color: '#94A3B8', stepSize: 1 }
                    },
                    y: {
                        grid: { display: false },
                        ticks: { color: '#94A3B8' }
                    }
                }
            }
        });

        // 5. Organization Chart (Bar)
        const orgData = <?= json_encode($orgData) ?>;
        const orgExtracted = extractData(orgData, 'organization', 'cnt');
        
        const ctxOrg = document.getElementById('orgChart').getContext('2d');
        let orgGradient = ctxOrg.createLinearGradient(0, 0, 0, 400);
        orgGradient.addColorStop(0, 'rgba(14, 165, 233, 0.8)'); // Light blue
        orgGradient.addColorStop(1, 'rgba(14, 165, 233, 0.2)');

        new Chart(ctxOrg, {
            type: 'bar',
            data: {
                labels: orgExtracted.labels,
                datasets: [{
                    label: 'Participants',
                    data: orgExtracted.values,
                    backgroundColor: orgGradient,
                    borderRadius: 6,
                    borderWidth: 0,
                    barPercentage: 0.5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: tooltipSettings
                },
                scales: gridOptions
            }
        });
    } catch(err) {
        console.error("Error rendering charts: ", err);
    }
});
</script>

<?php include 'includes/footer.php'; ?>
