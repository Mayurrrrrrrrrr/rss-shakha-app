<?php
/**
 * Swayamsevak Dashboard - स्वयंसेवक डैशबोर्ड (Modernized)
 */
require_once '../includes/auth.php';
require_once '../config/db.php';
requireLogin();

if (!isSwayamsevak()) {
    header('Location: dashboard.php');
    exit;
}

$pageTitle = 'मुख्य पृष्ठ';
require_once '../includes/header.php';

$shakhaId = getCurrentShakhaId();

// ── Fetch shakha name ──
$stmtShakha = $pdo->prepare("SELECT name FROM shakhas WHERE id = ?");
$stmtShakha->execute([$shakhaId]);
$shakhaName = $stmtShakha->fetchColumn() ?: 'शाखा';

// ── Latest Subhashit ──
$latestSub = null;
$shabdarth = [];
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS subhashits (
        id INT AUTO_INCREMENT PRIMARY KEY, shakha_id INT NOT NULL, sanskrit_text TEXT NOT NULL,
        hindi_meaning TEXT, shabdarth JSON, subhashit_date DATE NOT NULL, created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (shakha_id) REFERENCES shakhas(id) ON DELETE CASCADE,
        FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
} catch (PDOException $e) {}
$stmtSub = $pdo->prepare("SELECT * FROM subhashits WHERE shakha_id = ? ORDER BY subhashit_date DESC LIMIT 1");
$stmtSub->execute([$shakhaId]);
$latestSub = $stmtSub->fetch();
if ($latestSub) {
    $shabdarth = json_decode($latestSub['shabdarth'], true) ?: [];
}

// ── Stats ──
$todayRecord = $pdo->prepare("SELECT id FROM daily_records WHERE record_date = CURDATE() AND shakha_id = ?");
$todayRecord->execute([$shakhaId]);
$hasTodayRecord = $todayRecord->fetch();

// ── Recent Notices ──
$stmtNotice = $pdo->prepare("SELECT * FROM notices WHERE shakha_id = ? ORDER BY created_at DESC LIMIT 3");
$stmtNotice->execute([$shakhaId]);
$recentNotices = $stmtNotice->fetchAll();

// ── Calendar Data ──
$month = intval($_GET['month'] ?? date('n'));
$year = intval($_GET['year'] ?? date('Y'));
if ($month < 1) { $month = 12; $year--; }
if ($month > 12) { $month = 1; $year++; }

$firstDay = mktime(0, 0, 0, $month, 1, $year);
$daysInMonth = date('t', $firstDay);
$startDayOfWeek = date('w', $firstDay);

$stmtCal = $pdo->prepare("SELECT id, record_date, 
    (SELECT COUNT(*) FROM attendance a WHERE a.daily_record_id = daily_records.id AND a.is_present = 1) as present_count
    FROM daily_records 
    WHERE MONTH(record_date) = ? AND YEAR(record_date) = ? AND shakha_id = ?");
$stmtCal->execute([$month, $year, $shakhaId]);
$calRecords = [];
foreach ($stmtCal->fetchAll() as $r) {
    $calRecords[intval(date('j', strtotime($r['record_date'])))] = $r;
}

$hindiMonths = ['जनवरी', 'फ़रवरी', 'मार्च', 'अप्रैल', 'मई', 'जून', 'जुलाई', 'अगस्त', 'सितंबर', 'अक्टूबर', 'नवंबर', 'दिसंबर'];
$hindiDays = ['रवि', 'सोम', 'मंगल', 'बुध', 'गुरु', 'शुक्र', 'शनि'];

$prevMonth = $month - 1; $prevYear = $year;
if ($prevMonth < 1) { $prevMonth = 12; $prevYear--; }
$nextMonth = $month + 1; $nextYear = $year;
if ($nextMonth > 12) { $nextMonth = 1; $nextYear++; }

$today = date('Y-m-d');
$todayDow = date('w');

// ── Timetable Data ──
$ttDefaultDays = [];
$ttOverrideDates = [];
try {
    $stmtTTDef = $pdo->prepare("SELECT day_of_week FROM timetable_defaults WHERE shakha_id = ?");
    $stmtTTDef->execute([$shakhaId]);
    foreach ($stmtTTDef->fetchAll() as $row) { $ttDefaultDays[] = $row['day_of_week']; }
    
    $stmtTTOvr = $pdo->prepare("SELECT override_date FROM timetable_overrides WHERE shakha_id = ? AND MONTH(override_date) >= ? - 1 AND YEAR(override_date) <= ? + 1");
    $stmtTTOvr->execute([$shakhaId, $month, $year]);
    foreach ($stmtTTOvr->fetchAll() as $row) { $ttOverrideDates[] = $row['override_date']; }
} catch (PDOException $e) {}

?>
<style>
/* Modern Overrides */
.hero-flipbook {
    background: linear-gradient(135deg, #FF6B00 0%, #FF9800 100%);
    border-radius: 16px;
    padding: 24px;
    text-align: center;
    color: white;
    box-shadow: 0 8px 20px rgba(255,107,0,0.3);
    margin-bottom: 30px;
    position: relative;
    overflow: hidden;
}
.hero-flipbook::after {
    content: '📖';
    position: absolute;
    right: -20px;
    top: -10px;
    font-size: 100px;
    opacity: 0.2;
    transform: rotate(15deg);
}
.hero-flipbook h2 { margin: 0 0 10px 0; font-family: 'Tiro Devanagari Hindi', serif; font-size: 28px; }
.hero-flipbook p { margin: 0 0 20px 0; font-size: 15px; opacity: 0.9; }
.btn-flipbook {
    background: white;
    color: #FF6B00;
    padding: 12px 25px;
    border-radius: 30px;
    font-weight: 700;
    font-size: 16px;
    text-decoration: none;
    display: inline-block;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    transition: transform 0.2s;
}
.btn-flipbook:hover { transform: scale(1.05); }

.glass-card {
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(0,0,0,0.05);
    border-radius: 16px;
    padding: 20px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.03);
    margin-bottom: 24px;
}
.glass-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    border-bottom: 1px dashed rgba(0,0,0,0.1);
    padding-bottom: 10px;
}
.glass-header h3 { margin: 0; font-size: 18px; color: #C2185B; font-family: 'Tiro Devanagari Hindi', serif; }
</style>

<div class="page-header" style="margin-bottom: 20px;">
    <h1>🙏 नमस्ते, <?php echo htmlspecialchars(getUserName()); ?></h1>
</div>

<!-- ═══════ HERO FLIPBOOK ═══════ -->
<div class="hero-flipbook fade-in">
    <h2>आज का डिजिटल वृत्त</h2>
    <p>उपस्थिति, सुभाषित, गीत और आज के सभी कार्यक्रम एक ही स्थान पर देखें।</p>
    <a href="../pages/daily_flipbook.php?date=<?php echo $today; ?>" class="btn-flipbook">📖 3D फ्लिपबुक खोलें</a>
</div>

<!-- ═══════ STATS MINI ═══════ -->
<div style="display: flex; gap: 15px; margin-bottom: 24px; flex-wrap: wrap;">
    <div style="flex: 1; background: #E8F5E9; padding: 15px; border-radius: 12px; border: 1px dashed #81C784; text-align: center;">
        <div style="font-size: 24px; margin-bottom: 5px;"><?php echo $hasTodayRecord ? '✅' : '⏳'; ?></div>
        <div style="font-size: 14px; color: #2E7D32; font-weight: bold;"><?php echo $hasTodayRecord ? 'आज का रिकॉर्ड तैयार है' : 'रिकॉर्ड की प्रतीक्षा है'; ?></div>
    </div>
</div>

<!-- ═══════ LATEST SUBHASHIT ═══════ -->
<?php if ($latestSub): ?>
<div class="glass-card fade-in" style="animation-delay: 0.1s; background: linear-gradient(180deg, #FFF9E6 0%, #FFF5F5 100%);">
    <div class="glass-header">
        <h3 style="color: #880E4F;">📜 आज का सुभाषित</h3>
        <span style="font-size: 12px; opacity: 0.8; color: #880E4F;"><?php echo date('d M Y', strtotime($latestSub['subhashit_date'])); ?></span>
    </div>
    
    <div style="font-size: 1.1rem; font-weight: 600; color: #1B5E20; line-height: 1.8; white-space: pre-wrap; text-align: center; margin-bottom: 15px;">
        <?php echo nl2br(htmlspecialchars($latestSub['sanskrit_text'])); ?>
    </div>
    
    <?php if (!empty($latestSub['hindi_meaning'])): ?>
        <div style="background: rgba(255,255,255,0.7); border-radius: 10px; padding: 12px; margin-top: 10px; border-left: 4px solid #FF9800;">
            <div style="font-weight: 700; color: #E65100; font-size: 0.85rem; margin-bottom: 4px;">भावार्थ:</div>
            <div style="color: #3E2723; font-size: 0.95rem; line-height: 1.6;"><?php echo nl2br(htmlspecialchars($latestSub['hindi_meaning'])); ?></div>
        </div>
    <?php endif; ?>
    
    <div style="text-align: center; margin-top: 15px;">
        <a href="../pages/subhashit_view.php" style="color: #C2185B; font-size: 14px; font-weight: bold; text-decoration: none;">सभी सुभाषित देखें →</a>
    </div>
</div>
<?php endif; ?>

<!-- ═══════ CALENDAR ═══════ -->
<div class="glass-card fade-in" style="animation-delay: 0.2s; padding: 0; overflow: hidden;">
    <div style="background: #3E2723; color: white; padding: 15px; display: flex; justify-content: space-between; align-items: center;">
        <a href="../pages/swayamsevak_dashboard.php?month=<?php echo $prevMonth; ?>&year=<?php echo $prevYear; ?>" style="color: white; text-decoration: none; padding: 5px 10px; background: rgba(255,255,255,0.2); border-radius: 5px;">◀</a>
        <h3 style="margin: 0; font-size: 18px; font-weight: normal;"><?php echo $hindiMonths[$month - 1] . ' ' . $year; ?></h3>
        <a href="../pages/swayamsevak_dashboard.php?month=<?php echo $nextMonth; ?>&year=<?php echo $nextYear; ?>" style="color: white; text-decoration: none; padding: 5px 10px; background: rgba(255,255,255,0.2); border-radius: 5px;">▶</a>
    </div>
    
    <div style="padding: 15px;">
        <div class="calendar-grid">
            <?php foreach ($hindiDays as $dayName): ?>
                <div class="calendar-day-header"><?php echo $dayName; ?></div>
            <?php endforeach; ?>
            <?php for ($i = 0; $i < $startDayOfWeek; $i++): ?>
                <div class="calendar-day other-month"></div>
            <?php endfor; ?>
            <?php for ($d = 1; $d <= $daysInMonth; $d++):
                $currentDate = sprintf('%04d-%02d-%02d', $year, $month, $d);
                $isToday = ($currentDate === $today);
                $hasRecord = isset($calRecords[$d]);
                $classes = 'calendar-day';
                if ($isToday) $classes .= ' today';
                if ($hasRecord) $classes .= ' has-record';
            ?>
                <?php if ($hasRecord): ?>
                    <a href="../pages/daily_flipbook.php?date=<?php echo $currentDate; ?>" class="<?php echo $classes; ?>" title="<?php echo $calRecords[$d]['present_count']; ?> उपस्थित">
                        <?php echo $d; ?>
                        <?php
                        $dow = date('w', strtotime($currentDate));
                        if (in_array($currentDate, $ttOverrideDates) || in_array($dow, $ttDefaultDays)): ?>
                            <span style="font-size: 0.5rem; display: block; margin-top: 1px;">📋</span>
                        <?php endif; ?>
                    </a>
                <?php else: ?>
                    <div class="<?php echo $classes; ?>" style="color: inherit;">
                        <?php echo $d; ?>
                        <?php
                        $dow = date('w', strtotime($currentDate));
                        if (in_array($currentDate, $ttOverrideDates) || in_array($dow, $ttDefaultDays)): ?>
                            <span style="font-size: 0.5rem; display: block; margin-top: 1px; color: #999;">📋</span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endfor; ?>
            <?php
            $totalCells = $startDayOfWeek + $daysInMonth;
            $remaining = (7 - ($totalCells % 7)) % 7;
            for ($i = 0; $i < $remaining; $i++): ?>
                <div class="calendar-day other-month"></div>
            <?php endfor; ?>
        </div>
        <div style="font-size: 12px; color: #666; text-align: center; margin-top: 15px;">
            🟠 भगवा हाइलाइट वाले दिन पर क्लिक करके उस दिन का वृत्त (Flipbook) देखें।
        </div>
    </div>
</div>

<!-- ═══════ RECENT NOTICES ═══════ -->
<?php if (!empty($recentNotices)): ?>
<div class="glass-card fade-in" style="animation-delay: 0.3s; border-color: rgba(33,150,243,0.2);">
    <div class="glass-header">
        <h3 style="color: #1976D2;">📢 सूचनाएं</h3>
    </div>
    <?php foreach ($recentNotices as $notice): ?>
        <div style="padding: 10px; background: rgba(33,150,243,0.05); border-radius: 8px; margin-bottom: 10px;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                <strong style="color: #1565C0;"><?php echo htmlspecialchars($notice['subject']); ?></strong>
                <span style="font-size: 12px; color: #666;"><?php echo date('d M', strtotime($notice['notice_date'])); ?></span>
            </div>
            <div style="font-size: 14px; color: #333; line-height: 1.5; white-space: pre-wrap;"><?php echo htmlspecialchars($notice['message']); ?></div>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
