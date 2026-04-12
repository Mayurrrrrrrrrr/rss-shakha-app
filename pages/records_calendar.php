<?php
/**
 * Calendar View - कैलेंडर दृश्य
 */
$pageTitle = 'कैलेंडर';
require_once '../includes/header.php';
require_once '../config/db.php';
requireLogin();

if (isSwayamsevak()) {
    header('Location: swayamsevak_dashboard.php');
    exit;
}

$month = intval($_GET['month'] ?? date('n'));
$year = intval($_GET['year'] ?? date('Y'));

// Clamp
if ($month < 1) {
    $month = 12;
    $year--;
}
if ($month > 12) {
    $month = 1;
    $year++;
}

$firstDay = mktime(0, 0, 0, $month, 1, $year);
$daysInMonth = date('t', $firstDay);
$startDayOfWeek = date('w', $firstDay); // 0=Sunday

// Get records for this month
$shakhaId = getCurrentShakhaId();
$stmt = $pdo->prepare("SELECT id, record_date, 
    (SELECT COUNT(*) FROM attendance a WHERE a.daily_record_id = daily_records.id AND a.is_present = 1) as present_count
    FROM daily_records 
    WHERE MONTH(record_date) = ? AND YEAR(record_date) = ? AND shakha_id = ?");
$stmt->execute([$month, $year, $shakhaId]);
$records = [];
foreach ($stmt->fetchAll() as $r) {
    $day = intval(date('j', strtotime($r['record_date'])));
    $records[$day] = $r;
}

$hindiMonths = ['जनवरी', 'फ़रवरी', 'मार्च', 'अप्रैल', 'मई', 'जून', 'जुलाई', 'अगस्त', 'सितंबर', 'अक्टूबर', 'नवंबर', 'दिसंबर'];
$hindiDays = ['रवि', 'सोम', 'मंगल', 'बुध', 'गुरु', 'शुक्र', 'शनि'];

$prevMonth = $month - 1;
$prevYear = $year;
if ($prevMonth < 1) {
    $prevMonth = 12;
    $prevYear--;
}

$nextMonth = $month + 1;
$nextYear = $year;
if ($nextMonth > 12) {
    $nextMonth = 1;
    $nextYear++;
}

$today = date('Y-m-d');

// Get timetable data for indicator on calendar
try {
    $stmtTTDef = $pdo->prepare("SELECT day_of_week FROM timetable_defaults WHERE shakha_id = ?");
    $stmtTTDef->execute([$shakhaId]);
    $ttDefaultDays = array_column($stmtTTDef->fetchAll(), 'day_of_week');
    
    $stmtTTOvr = $pdo->prepare("SELECT override_date FROM timetable_overrides WHERE shakha_id = ? AND MONTH(override_date) = ? AND YEAR(override_date) = ?");
    $stmtTTOvr->execute([$shakhaId, $month, $year]);
    $ttOverrideDates = array_column($stmtTTOvr->fetchAll(), 'override_date');
} catch (PDOException $e) {
    $ttDefaultDays = [];
    $ttOverrideDates = [];
}
?>

<div class="page-header">
    <h1>📅 कैलेंडर दृश्य</h1>
    <a href="../pages/daily_record.php" class="btn btn-primary">📝 नया रिकॉर्ड</a>
</div>

<div class="calendar-container">
    <div class="calendar-header">
        <a href="../pages/records_calendar.php?month=<?php echo $prevMonth; ?>&year=<?php echo $prevYear; ?>"
            class="btn btn-outline btn-sm">◀ पिछला</a>
        <h2>
            <?php echo $hindiMonths[$month - 1] . ' ' . $year; ?>
        </h2>
        <a href="../pages/records_calendar.php?month=<?php echo $nextMonth; ?>&year=<?php echo $nextYear; ?>"
            class="btn btn-outline btn-sm">अगला ▶</a>
    </div>

    <div class="calendar-grid">
        <!-- Day headers -->
        <?php foreach ($hindiDays as $dayName): ?>
            <div class="calendar-day-header">
                <?php echo $dayName; ?>
            </div>
        <?php endforeach; ?>

        <!-- Empty cells before first day -->
        <?php for ($i = 0; $i < $startDayOfWeek; $i++): ?>
            <div class="calendar-day other-month"></div>
        <?php endfor; ?>

        <!-- Days -->
        <?php for ($d = 1; $d <= $daysInMonth; $d++):
            $currentDate = sprintf('%04d-%02d-%02d', $year, $month, $d);
            $isToday = ($currentDate === $today);
            $hasRecord = isset($records[$d]);
            $classes = 'calendar-day';
            if ($isToday)
                $classes .= ' today';
            if ($hasRecord)
                $classes .= ' has-record';
            ?>
            <?php if ($hasRecord): ?>
                <a href="../pages/record_detail.php?id=<?php echo $records[$d]['id']; ?>" class="<?php echo $classes; ?>"
                    title="<?php echo $records[$d]['present_count']; ?> उपस्थित">
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

        <!-- Empty cells after last day -->
        <?php
        $totalCells = $startDayOfWeek + $daysInMonth;
        $remaining = (7 - ($totalCells % 7)) % 7;
        for ($i = 0; $i < $remaining; $i++): ?>
            <div class="calendar-day other-month"></div>
        <?php endfor; ?>
    </div>
</div>

<div class="card mt-3">
    <div class="card-header">ℹ️ कैलेंडर गाइड</div>
    <p style="color: var(--text-secondary); font-size: 0.9rem;">
        🟠 <strong>भगवा हाइलाइट</strong> = रिकॉर्ड मौजूद है (क्लिक करें विवरण के लिए)<br>
        🔶 <strong>बॉर्डर</strong> = आज की तारीख<br>
        📋 <strong>छोटा आइकन</strong> = समय-सारणी सेट है (क्लिक करें देखने के लिए)
    </p>
</div>

<?php require_once '../includes/footer.php'; ?>
