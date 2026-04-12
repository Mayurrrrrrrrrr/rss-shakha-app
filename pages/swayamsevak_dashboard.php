<?php
/**
 * Swayamsevak Dashboard - स्वयंसेवक डैशबोर्ड (Full Home Page)
 */
$pageTitle = 'मुख्य पृष्ठ';
require_once '../includes/header.php';
require_once '../config/db.php';
requireLogin();

if (!isSwayamsevak()) {
    header('Location: dashboard.php');
    exit;
}

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

// ── Recent Records ──
$recentRecordsStmt = $pdo->prepare("SELECT dr.*, 
    (SELECT COUNT(*) FROM attendance a WHERE a.daily_record_id = dr.id AND a.is_present = 1) as present_count,
    (SELECT COUNT(*) FROM daily_activities da WHERE da.daily_record_id = dr.id AND da.is_done = 1) as activities_done
    FROM daily_records dr 
    WHERE dr.shakha_id = ?
    ORDER BY record_date DESC LIMIT 5");
$recentRecordsStmt->execute([$shakhaId]);
$recentRecords = $recentRecordsStmt->fetchAll();

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
$hindiDaysFull = ['रविवार', 'सोमवार', 'मंगलवार', 'बुधवार', 'गुरुवार', 'शुक्रवार', 'शनिवार'];

$prevMonth = $month - 1; $prevYear = $year;
if ($prevMonth < 1) { $prevMonth = 12; $prevYear--; }
$nextMonth = $month + 1; $nextYear = $year;
if ($nextMonth > 12) { $nextMonth = 1; $nextYear++; }

$today = date('Y-m-d');
$todayDow = date('w');

// ── Timetable Data for indicators ──
$ttDefaultDays = [];
$ttOverrideDates = [];
$todayTimetable = [];
$isTodayOverride = false;
try {
    $stmtTTDef = $pdo->prepare("SELECT day_of_week, slots FROM timetable_defaults WHERE shakha_id = ?");
    $stmtTTDef->execute([$shakhaId]);
    foreach ($stmtTTDef->fetchAll() as $row) {
        $ttDefaultDays[] = $row['day_of_week'];
        if ($row['day_of_week'] == $todayDow) {
            $todayTimetable = json_decode($row['slots'], true) ?: [];
        }
    }
    
    $stmtTTOvr = $pdo->prepare("SELECT override_date, slots FROM timetable_overrides WHERE shakha_id = ? AND MONTH(override_date) >= ? - 1 AND YEAR(override_date) <= ? + 1");
    $stmtTTOvr->execute([$shakhaId, $month, $year]);
    foreach ($stmtTTOvr->fetchAll() as $row) {
        $ttOverrideDates[] = $row['override_date'];
        if ($row['override_date'] === $today) {
            $todayTimetable = json_decode($row['slots'], true) ?: [];
            $isTodayOverride = true;
        }
    }
} catch (PDOException $e) {}

function formatHindiDateDash($dateStr) {
    global $hindiMonths, $hindiDaysFull;
    $ts = strtotime($dateStr);
    $day = $hindiDaysFull[date('w', $ts)];
    $d = date('j', $ts);
    $m = $hindiMonths[date('n', $ts) - 1];
    $y = date('Y', $ts);
    return "$day, $d $m $y";
}
?>

<div class="page-header">
    <h1>🙏 नमस्ते, <?php echo htmlspecialchars(getUserName()); ?></h1>
</div>

<!-- ═══════ LATEST SUBHASHIT ═══════ -->
<?php if ($latestSub): ?>
<div class="card fade-in" style="padding: 0; overflow: hidden; border: 1px solid rgba(233, 30, 99, 0.2); margin-bottom: 24px;">
    <div style="background: linear-gradient(135deg, #C2185B, #AD1457); color: #FCE4EC; padding: 12px 20px; display: flex; justify-content: space-between; align-items: center;">
        <span style="font-weight: 700; font-size: 1.1rem;">📜 आज का सुभाषित</span>
        <span style="font-size: 0.85rem; opacity: 0.8;"><?php echo date('d M Y', strtotime($latestSub['subhashit_date'])); ?></span>
    </div>
    <div style="background: linear-gradient(180deg, #FFF9E6 0%, #FFF5F5 50%, #F0FFF4 100%); padding: 20px; text-align: center;">
        <div style="font-size: 1.2rem; font-weight: 600; color: #1B5E20; line-height: 1.9; white-space: pre-wrap; margin-bottom: 12px;">
            <?php echo nl2br(htmlspecialchars($latestSub['sanskrit_text'])); ?>
        </div>
        <?php if (!empty($latestSub['hindi_meaning'])): ?>
            <div style="background: rgba(255,249,196,0.5); border: 1px dashed #F9A825; border-radius: 10px; padding: 12px 16px; margin-top: 10px;">
                <div style="font-weight: 700; color: #E65100; font-size: 0.85rem; margin-bottom: 6px;">— हिंदी अर्थ —</div>
                <div style="color: #3E2723; font-size: 0.95rem; line-height: 1.6;"><?php echo nl2br(htmlspecialchars($latestSub['hindi_meaning'])); ?></div>
            </div>
        <?php endif; ?>
        <?php if (!empty($shabdarth)): ?>
            <div style="background: rgba(232,245,233,0.5); border: 1px dashed #81C784; border-radius: 10px; padding: 12px 16px; margin-top: 10px;">
                <div style="font-weight: 700; color: #E65100; font-size: 0.85rem; margin-bottom: 6px;">— चुनिंदा शब्दार्थ —</div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 4px 16px;">
                    <?php foreach ($shabdarth as $p): ?>
                        <div style="font-size: 0.9rem; color: #2E7D32; padding: 2px 0;">
                            <strong style="color: #1B5E20;"><?php echo htmlspecialchars($p['shabd']); ?></strong>
                            <span style="color: #81C784;">—</span>
                            <span style="color: #33691E;"><?php echo htmlspecialchars($p['arth']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <div style="text-align: center; padding: 10px 20px; background: rgba(0,0,0,0.02); border-top: 1px solid rgba(233,30,99,0.1);">
        <a href="../pages/subhashit_view.php" style="color: #C2185B; font-size: 0.9rem; font-weight: 600; text-decoration: none;">सभी सुभाषित देखें →</a>
    </div>
</div>
<?php endif; ?>

<!-- ═══════ TODAY'S TIMETABLE ═══════ -->
<?php if (!empty($todayTimetable)): ?>
<div class="card fade-in" style="animation-delay: 0.05s; margin-bottom: 24px; border: 1px solid rgba(255,107,0,0.2);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
        <strong style="color: var(--saffron-light); font-size: 1.1rem;">📋 आज की समय-सारणी</strong>
        <?php if ($isTodayOverride): ?>
            <span class="badge badge-saffron" style="font-size: 0.75rem;">विशेष दिन</span>
        <?php else: ?>
            <span class="badge badge-success" style="font-size: 0.75rem;">साप्ताहिक डिफ़ॉल्ट</span>
        <?php endif; ?>
    </div>
    <div style="display: grid; gap: 8px;">
        <?php foreach ($todayTimetable as $slot): ?>
            <div style="display: flex; align-items: stretch; background: rgba(255,255,255,0.04); border-radius: 8px; overflow: hidden; border: 1px solid var(--border-color);">
                <div style="background: var(--saffron); color: white; padding: 10px; font-weight: 700; font-size: 0.85rem; min-width: 80px; text-align: center;">
                    <?php echo $slot['start_min']; ?>–<?php echo $slot['end_min']; ?> मि.
                </div>
                <div style="padding: 10px 14px; font-size: 0.95rem; display: flex; align-items: center; color: var(--text-primary);">
                    <?php echo htmlspecialchars($slot['topic']); ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <div style="text-align: center; margin-top: 15px;">
        <a href="../pages/timetable_view.php?date=<?php echo $today; ?>" class="btn btn-outline btn-sm">पूरा विवरण देखें</a>
    </div>
</div>
<?php endif; ?>

<!-- ═══════ STATS ═══════ -->
<div class="stats-grid fade-in" style="animation-delay: 0.05s;">
    <div class="stat-card">
        <div class="stat-icon">👥</div>
        <div class="stat-number"><?php echo $totalSwayamsevaks; ?></div>
        <div class="stat-label">कुल स्वयंसेवक</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">📋</div>
        <div class="stat-number"><?php echo $totalActivities; ?></div>
        <div class="stat-label">गतिविधियाँ</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">📊</div>
        <div class="stat-number"><?php echo $totalRecords; ?></div>
        <div class="stat-label">कुल रिकॉर्ड</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><?php echo $hasTodayRecord ? '✅' : '⏳'; ?></div>
        <div class="stat-number"><?php echo $hasTodayRecord ? 'हाँ' : 'नहीं'; ?></div>
        <div class="stat-label">आज का रिकॉर्ड</div>
    </div>
</div>

<!-- ═══════ CALENDAR ═══════ -->
<div class="calendar-container fade-in" style="animation-delay: 0.1s; margin-bottom: 24px;">
    <div class="calendar-header">
        <a href="../pages/swayamsevak_dashboard.php?month=<?php echo $prevMonth; ?>&year=<?php echo $prevYear; ?>" class="btn btn-outline btn-sm">◀ पिछला</a>
        <h2><?php echo $hindiMonths[$month - 1] . ' ' . $year; ?></h2>
        <a href="../pages/swayamsevak_dashboard.php?month=<?php echo $nextMonth; ?>&year=<?php echo $nextYear; ?>" class="btn btn-outline btn-sm">अगला ▶</a>
    </div>
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
                <a href="../pages/record_detail.php?id=<?php echo $calRecords[$d]['id']; ?>" class="<?php echo $classes; ?>"
                    title="<?php echo $calRecords[$d]['present_count']; ?> उपस्थित">
                    <?php echo $d; ?>
                    <?php
                    $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $d);
                    $dow = date('w', strtotime($dateStr));
                    if (in_array($dateStr, $ttOverrideDates) || in_array($dow, $ttDefaultDays)): ?>
                        <span style="font-size: 0.5rem; display: block; margin-top: 1px;">📋</span>
                    <?php endif; ?>
                </a>
            <?php else: ?>
                <a href="../pages/timetable_view.php?date=<?php echo sprintf('%04d-%02d-%02d', $year, $month, $d); ?>" class="<?php echo $classes; ?>" style="text-decoration:none; color:inherit;">
                    <?php echo $d; ?>
                    <?php
                    $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $d);
                    $dow = date('w', strtotime($dateStr));
                    if (in_array($dateStr, $ttOverrideDates) || in_array($dow, $ttDefaultDays)): ?>
                        <span style="font-size: 0.5rem; display: block; margin-top: 1px;">📋</span>
                    <?php endif; ?>
                </a>
            <?php endif; ?>
        <?php endfor; ?>
        <?php
        $totalCells = $startDayOfWeek + $daysInMonth;
        $remaining = (7 - ($totalCells % 7)) % 7;
        for ($i = 0; $i < $remaining; $i++): ?>
            <div class="calendar-day other-month"></div>
        <?php endfor; ?>
    </div>
</div>

<!-- ═══════ RECENT NOTICES ═══════ -->
<?php if (!empty($recentNotices)): ?>
<div class="card fade-in" style="animation-delay: 0.15s; margin-bottom: 24px;">
    <div class="card-header">📢 हाल की सूचनाएं</div>
    <?php foreach ($recentNotices as $notice): ?>
        <div style="padding: 14px 0; border-bottom: 1px solid var(--border-color);">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; flex-wrap: wrap;">
                <div>
                    <strong style="color: var(--saffron-light); font-size: 1.05rem;"><?php echo htmlspecialchars($notice['subject']); ?></strong>
                    <?php if (!empty($notice['tithi'])): ?>
                        <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 4px;">📿 <?php echo htmlspecialchars($notice['tithi']); ?></div>
                    <?php endif; ?>
                </div>
                <span style="font-size: 0.8rem; color: var(--text-muted); white-space: nowrap;"><?php echo date('d M Y', strtotime($notice['notice_date'])); ?></span>
            </div>
            <p style="color: var(--text-secondary); margin-top: 8px; font-size: 0.95rem; line-height: 1.6; white-space: pre-wrap;"><?php echo htmlspecialchars($notice['message']); ?></p>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- ═══════ RECENT RECORDS ═══════ -->
<div class="card fade-in" style="animation-delay: 0.2s;">
    <div class="card-header">📋 हाल के रिकॉर्ड</div>
    <?php if (empty($recentRecords)): ?>
        <div class="empty-state">
            <div class="icon">📭</div>
            <p>अभी तक कोई रिकॉर्ड नहीं है।</p>
        </div>
    <?php else: ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>तारीख</th>
                        <th>उपस्थिति</th>
                        <th>गतिविधियाँ</th>
                        <th>देखें</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentRecords as $rec): ?>
                        <tr>
                            <td><?php echo formatHindiDateDash($rec['record_date']); ?></td>
                            <td><span class="badge badge-green"><?php echo $rec['present_count']; ?> उपस्थित</span></td>
                            <td><span class="badge badge-saffron"><?php echo $rec['activities_done']; ?> पूर्ण</span></td>
                            <td>
                                <a href="../pages/record_detail.php?id=<?php echo $rec['id']; ?>" class="btn btn-sm btn-outline">👁️ देखें</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="card fade-in" style="animation-delay: 0.25s; margin-top: 20px;">
    <div class="card-header">ℹ️ कैलेंडर गाइड</div>
    <p style="color: var(--text-secondary); font-size: 0.9rem;">
        🟠 <strong>भगवा हाइलाइट</strong> = उस दिन का रिकॉर्ड है (क्लिक करें विवरण के लिए)<br>
        🔶 <strong>बॉर्डर</strong> = आज की तारीख<br>
        📋 <strong>छोटा आइकन</strong> = समय-सारणी मौजूद है (क्लिक करें देखने के लिए)
    </p>
</div>

<?php require_once '../includes/footer.php'; ?>
