<?php
/**
 * Dashboard - मुख्य पृष्ठ
 */
require_once '../includes/auth.php';
require_once '../config/db.php';
requireLogin();

if (isSwayamsevak()) {
    header('Location: daily_flipbook.php');
    exit;
}

$pageTitle = 'मुख्य पृष्ठ';
require_once '../includes/header.php';

$shakhaId = getCurrentShakhaId();

// Stats
$stmt = $pdo->prepare("SELECT COUNT(*) FROM swayamsevaks WHERE is_active = 1 AND shakha_id = ?");
$stmt->execute([$shakhaId]);
$totalSwayamsevaks = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM activities WHERE is_active = 1 AND (shakha_id IS NULL OR shakha_id = ?)");
$stmt->execute([$shakhaId]);
$totalActivities = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM daily_records WHERE shakha_id = ?");
$stmt->execute([$shakhaId]);
$totalRecords = $stmt->fetchColumn();

$todayRecord = $pdo->prepare("SELECT id FROM daily_records WHERE record_date = CURDATE() AND shakha_id = ?");
$todayRecord->execute([$shakhaId]);
$hasTodayRecord = $todayRecord->fetch();

// Recent records
$recentRecordsStmt = $pdo->prepare("SELECT dr.*, 
    (SELECT COUNT(*) FROM attendance a WHERE a.daily_record_id = dr.id AND a.is_present = 1) as present_count,
    (SELECT COUNT(*) FROM daily_activities da WHERE da.daily_record_id = dr.id AND da.is_done = 1) as activities_done
    FROM daily_records dr 
    WHERE dr.shakha_id = ?
    ORDER BY record_date DESC LIMIT 5");
$recentRecordsStmt->execute([$shakhaId]);
$recentRecords = $recentRecordsStmt->fetchAll();

$hindiMonths = ['जनवरी', 'फ़रवरी', 'मार्च', 'अप्रैल', 'मई', 'जून', 'जुलाई', 'अगस्त', 'सितंबर', 'अक्टूबर', 'नवंबर', 'दिसंबर'];
$hindiDays = ['रविवार', 'सोमवार', 'मंगलवार', 'बुधवार', 'गुरुवार', 'शुक्रवार', 'शनिवार'];

function formatHindiDate($dateStr)
{
    global $hindiMonths, $hindiDays;
    $ts = strtotime($dateStr);
    $day = $hindiDays[date('w', $ts)];
    $d = date('j', $ts);
    $m = $hindiMonths[date('n', $ts) - 1];
    $y = date('Y', $ts);
    return "$day, $d $m $y";
}
?>

<?php
// Phase 3: Dashboard Hero Component
require_once '../includes/PanchangCalculator.php';
$calc = new PanchangCalculator();
$panchang = $calc->getPanchang(date('Y-m-d'));
$tithiDisplay = $panchang['tithi'] . ' ' . $panchang['paksha'] . ', ' . $panchang['maah'] . ' ' . $panchang['vikram_samvat'];

// Fetch today's attendance count if record exists
$todayPresentCount = 0;
if ($hasTodayRecord) {
    $stmtA = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE daily_record_id = ? AND is_present = 1");
    $stmtA->execute([$hasTodayRecord['id']]);
    $todayPresentCount = $stmtA->fetchColumn();
}
?>

<div class="dashboard-hero">
    <div class="hero-date-box">
        <div class="hero-label">आज का पंचांग</div>
        <div class="hero-vikram"><?php echo "वि. सं. " . $panchang['vikram_samvat']; ?></div>
        <div class="hero-tithi"><?php echo $tithiDisplay; ?></div>
    </div>
    
    <div class="hero-stats-box">
        <div class="hero-label">आज की उपस्थिति</div>
        <div class="hero-count"><?php echo $todayPresentCount; ?></div>
    </div>

    <div class="hero-cta">
        <?php if (!$hasTodayRecord): ?>
            <a href="../pages/daily_record.php?date=<?php echo date('Y-m-d'); ?>" class="btn-cta-green">आज की शाखा दर्ज करें →</a>
        <?php else: ?>
            <span class="badge-completed">✓ आज पूर्ण</span>
        <?php endif; ?>
    </div>
</div>

<div class="page-header">
    <h1>🙏 नमस्ते, <?php echo htmlspecialchars(getAdminName()); ?></h1>
</div>

<!-- Stats -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon">👥</div>
        <div class="stat-number">
            <?php echo $totalSwayamsevaks; ?>
        </div>
        <div class="stat-label">कुल स्वयंसेवक</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">📋</div>
        <div class="stat-number">
            <?php echo $totalActivities; ?>
        </div>
        <div class="stat-label">गतिविधियाँ</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">📊</div>
        <div class="stat-number">
            <?php echo $totalRecords; ?>
        </div>
        <div class="stat-label">कुल रिकॉर्ड</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">
            <?php echo $hasTodayRecord ? '✅' : '⏳'; ?>
        </div>
        <div class="stat-number">
            <?php echo $hasTodayRecord ? 'हाँ' : 'नहीं'; ?>
        </div>
        <div class="stat-label">आज का रिकॉर्ड</div>
    </div>
</div>

<!-- Recent Records -->
<div class="card">
    <div class="card-header">📋 हाल के रिकॉर्ड</div>
    <?php if (empty($recentRecords)): ?>
        <div class="empty-state">
            <div class="icon">📭</div>
            <p>अभी तक कोई रिकॉर्ड नहीं है।</p>
            <a href="../pages/daily_record.php" class="btn btn-primary">📝 पहला रिकॉर्ड बनाएँ</a>
        </div>
    <?php else: ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>तारीख</th>
                        <th>उपस्थिति</th>
                        <th>गतिविधियाँ</th>
                        <th>कार्रवाई</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentRecords as $rec): ?>
                        <tr>
                            <td>
                                <?php echo formatHindiDate($rec['record_date']); ?>
                            </td>
                            <td><span class="badge badge-green">
                                    <?php echo $rec['present_count']; ?> उपस्थित
                                </span></td>
                            <td><span class="badge badge-saffron">
                                    <?php echo $rec['activities_done']; ?> पूर्ण
                                </span></td>
                            <td>
                                <div class="table-actions">
                                    <a href="../pages/record_detail.php?id=<?php echo $rec['id']; ?>" class="btn btn-sm btn-outline">👁️
                                        देखें</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
