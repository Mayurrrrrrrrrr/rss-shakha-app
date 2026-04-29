<?php
require_once '../includes/auth.php';
/**
 * Analytics Dashboard - एनालिटिक्स डैशबोर्ड
 * Compact, fixed-height, single-viewport layout
 */
$pageTitle = 'एनालिटिक्स';
require_once '../includes/header.php';
require_once '../config/db.php';
requireLogin();

if (isSwayamsevak()) {
    header('Location: swayamsevak_dashboard.php');
    exit;
}

$shakhaId = getCurrentShakhaId();
$startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-3 months'));
$endDate = $_GET['end_date'] ?? date('Y-m-d');

// Activity Frequency
$stmt = $pdo->prepare("SELECT act.name as activity_name, 
    COUNT(CASE WHEN da.is_done = 1 THEN 1 END) as done_count
    FROM activities act
    LEFT JOIN daily_activities da ON da.activity_id = act.id 
        AND da.daily_record_id IN (SELECT id FROM daily_records WHERE shakha_id = ? AND record_date BETWEEN ? AND ?)
    WHERE act.is_active = 1 AND (act.shakha_id IS NULL OR act.shakha_id = ?)
    GROUP BY act.id, act.name ORDER BY done_count DESC");
$stmt->execute([$shakhaId, $startDate, $endDate, $shakhaId]);
$activityStats = $stmt->fetchAll();
$actNames = [];
$actCounts = [];
foreach ($activityStats as $r) {
    $actNames[] = $r['activity_name'];
    $actCounts[] = (int) $r['done_count'];
}

// Day-of-week attendance
$stmt = $pdo->prepare("SELECT DAYOFWEEK(dr.record_date) as dow, AVG(sub.pc) as avg_p
    FROM daily_records dr
    JOIN (SELECT daily_record_id, COUNT(*) as pc FROM attendance WHERE is_present=1 GROUP BY daily_record_id) sub ON sub.daily_record_id=dr.id
    WHERE dr.shakha_id=? AND dr.record_date BETWEEN ? AND ?
    GROUP BY DAYOFWEEK(dr.record_date) ORDER BY DAYOFWEEK(dr.record_date)");
$stmt->execute([$shakhaId, $startDate, $endDate]);
$dowStats = $stmt->fetchAll();
$dayMap = [1 => 'रवि', 2 => 'सोम', 3 => 'मंगल', 4 => 'बुध', 5 => 'गुरु', 6 => 'शुक्र', 7 => 'शनि'];
$dowL = [];
$dowV = [];
foreach ($dowStats as $r) {
    $dowL[] = $dayMap[(int) $r['dow']];
    $dowV[] = round((float) $r['avg_p'], 1);
}

// Summary
$stmt = $pdo->prepare("SELECT COUNT(*) FROM daily_records WHERE shakha_id=? AND record_date BETWEEN ? AND ?");
$stmt->execute([$shakhaId, $startDate, $endDate]);
$totalDays = $stmt->fetchColumn();
$stmt = $pdo->prepare("SELECT AVG(sub.pc) FROM (SELECT COUNT(*) as pc FROM attendance a JOIN daily_records dr ON dr.id=a.daily_record_id WHERE a.is_present=1 AND dr.shakha_id=? AND dr.record_date BETWEEN ? AND ? GROUP BY a.daily_record_id) sub");
$stmt->execute([$shakhaId, $startDate, $endDate]);
$avgP = round((float) $stmt->fetchColumn(), 1);

// Best day
$bestDay = '-';
$bestAvg = 0;
foreach ($dowStats as $r) {
    if ((float) $r['avg_p'] > $bestAvg) {
        $bestAvg = round((float) $r['avg_p'], 1);
        $bestDay = $dayMap[(int) $r['dow']];
    }
}

$hM = ['जन', 'फ़र', 'मार्च', 'अप्रै', 'मई', 'जून', 'जुला', 'अग', 'सित', 'अक्टू', 'नव', 'दिस'];
$sd = date('j', $t = strtotime($startDate)) . ' ' . $hM[date('n', $t) - 1];
$ed = date('j', $t = strtotime($endDate)) . ' ' . $hM[date('n', $t) - 1];
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<style>
    .a-page {
        max-width: 1100px;
        margin: 0 auto
    }

    .a-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 12px
    }

    .a-top h1 {
        font-size: 1.2rem;
        margin: 0
    }

    .a-filter {
        display: flex;
        gap: 8px;
        align-items: center
    }

    .a-filter input {
        padding: 6px 8px;
        font-size: 0.8rem;
        background: var(--bg-input);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-sm);
        color: var(--text-primary);
        font-family: inherit
    }

    .a-filter button {
        padding: 6px 14px;
        font-size: 0.8rem
    }

    .a-stats {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 10px;
        margin-bottom: 14px
    }

    .a-stat {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 10px;
        padding: 12px 8px;
        text-align: center;
        position: relative;
        overflow: hidden
    }

    .a-stat::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, var(--saffron), var(--saffron-light))
    }

    .a-stat .n {
        font-size: 1.6rem;
        font-weight: 700;
        color: var(--saffron);
        line-height: 1
    }

    .a-stat .l {
        font-size: 0.75rem;
        color: var(--text-secondary);
        margin-top: 3px
    }

    .a-charts {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 14px
    }

    .a-box {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 10px;
        padding: 14px
    }

    .a-box h3 {
        font-size: 0.9rem;
        font-weight: 600;
        color: var(--saffron-light);
        margin: 0 0 8px 0;
        padding-bottom: 6px;
        border-bottom: 1px solid var(--border-color)
    }

    .a-box-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .a-box-row {
        display: flex;
        justify-content: space-between;
        padding: 8px;
        background: var(--bg-input);
        border-radius: var(--radius-sm);
        border: 1px solid var(--border-color);
    }

    .a-box-row .n {
        font-weight: 600;
    }

    @media(max-width:768px) {
        .a-stats {
            grid-template-columns: repeat(2, 1fr)
        }

        .a-charts {
            grid-template-columns: 1fr
        }

        .a-box canvas {
            max-height: 180px !important
        }
    }
</style>

<div class="a-page">
    <div class="a-top">
        <h1>📈 एनालिटिक्स</h1>
        <form method="GET" action="analytics.php" class="a-filter">
            <input type="date" name="start_date" value="<?php echo htmlspecialchars($startDate); ?>">
            <span style="color:var(--text-muted);font-size:0.8rem">से</span>
            <input type="date" name="end_date" value="<?php echo htmlspecialchars($endDate); ?>">
            <button type="submit" class="btn btn-primary btn-sm">देखें</button>
        </form>
    </div>

    <div class="a-stats">
        <div class="a-stat">
            <div class="n"><?php echo $totalDays; ?></div>
            <div class="l">शाखा दिवस</div>
        </div>
        <div class="a-stat">
            <div class="n"><?php echo $avgP; ?></div>
            <div class="l">औसत उपस्थिति</div>
        </div>
        <div class="a-stat">
            <div class="n"><?php echo count($activityStats); ?></div>
            <div class="l">गतिविधियाँ</div>
        </div>
    </div>

    <div class="a-charts">
        <div class="a-box">
            <h3>📋 गतिविधि — कितने दिन हुई</h3>
            <div class="a-box-list">
                <?php foreach ($activityStats as $act): ?>
                    <div class="a-box-row">
                        <span><?php echo htmlspecialchars($act['activity_name']); ?></span>
                        <span class="n"><?php echo $act['done_count']; ?>/<?php echo $totalDays; ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="a-box">
            <h3>📅 दिन — औसत उपस्थिति</h3>
            <div class="a-box-list">
                <?php foreach ($dowStats as $r): ?>
                    <div class="a-box-row">
                        <span><?php echo $dayMap[(int) $r['dow']]; ?></span>
                        <span class="n"><?php echo round((float) $r['avg_p'], 1); ?> व्यक्ति</span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>