<?php
/**
 * Record Detail - रिकॉर्ड विवरण
 */
$pageTitle = 'रिकॉर्ड विवरण';
require_once '../includes/header.php';
require_once '../config/db.php';
requireLogin();

$recordId = $_GET['id'] ?? null;
if (!$recordId) {
    header('Location: records_list.php');
    exit;
}

$shakhaId = getCurrentShakhaId();
if (isAdmin()) {
    $stmt = $pdo->prepare("SELECT * FROM daily_records WHERE id = ?");
    $stmt->execute([$recordId]);
} else {
    $stmt = $pdo->prepare("SELECT * FROM daily_records WHERE id = ? AND shakha_id = ?");
    $stmt->execute([$recordId, $shakhaId]);
}
$record = $stmt->fetch();

if (!$record) {
    header('Location: records_list.php');
    exit;
}

// Get attendance
$stmt = $pdo->prepare("SELECT a.*, s.name as swayamsevak_name 
    FROM attendance a 
    JOIN swayamsevaks s ON a.swayamsevak_id = s.id 
    WHERE a.daily_record_id = ? 
    ORDER BY s.name");
$stmt->execute([$recordId]);
$attendance = $stmt->fetchAll();

// Get activities
$stmt = $pdo->prepare("SELECT da.*, act.name as activity_name, s.name as conductor_name 
    FROM daily_activities da 
    JOIN activities act ON da.activity_id = act.id 
    LEFT JOIN swayamsevaks s ON da.conducted_by = s.id 
    WHERE da.daily_record_id = ? 
    ORDER BY act.sort_order, act.id");
$stmt->execute([$recordId]);
$dailyActivities = $stmt->fetchAll();

$hindiMonths = ['जनवरी', 'फ़रवरी', 'मार्च', 'अप्रैल', 'मई', 'जून', 'जुलाई', 'अगस्त', 'सितंबर', 'अक्टूबर', 'नवंबर', 'दिसंबर'];
$hindiDays = ['रविवार', 'सोमवार', 'मंगलवार', 'बुधवार', 'गुरुवार', 'शुक्रवार', 'शनिवार'];

$ts = strtotime($record['record_date']);
$formattedDate = $hindiDays[date('w', $ts)] . ', ' . date('j', $ts) . ' ' . $hindiMonths[date('n', $ts) - 1] . ' ' . date('Y', $ts);

$presentCount = 0;
foreach ($attendance as $a) {
    if ($a['is_present'])
        $presentCount++;
}
$absentCount = count($attendance) - $presentCount;
$doneCount = 0;
foreach ($dailyActivities as $a) {
    if ($a['is_done'])
        $doneCount++;
}
?>

<?php if (isset($_GET['success'])): ?>
    <div id="success-overlay" class="success-overlay">
        <div class="success-content">
            <div class="checkmark-circle">
                <div class="checkmark draw"></div>
            </div>
            <h2>शाखा पूर्ण!</h2>
            <p>आज का रिकॉर्ड सहेज लिया गया है।</p>
        </div>
    </div>
    <script>
        setTimeout(() => {
            window.location.href = 'dashboard.php';
        }, 3000);
    </script>
<?php endif; ?>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'saved' && !isset($_GET['success'])): ?>
    <div class="alert alert-success">✅ रिकॉर्ड सफलतापूर्वक सहेजा गया!</div>
<?php endif; ?>

<div class="page-header">
    <h1>📄 रिकॉर्ड विवरण</h1>
    <div class="d-flex gap-1">
        <?php if (!isSwayamsevak()): ?>
            <a href="../pages/daily_record.php?id=<?php echo $record['id']; ?>" class="btn btn-outline btn-sm">✏️ संपादित करें</a>
        <?php endif; ?>
        <a href="../pages/snapshot.php?id=<?php echo $record['id']; ?>" class="btn btn-primary btn-sm">📸 स्नैपशॉट</a>
    </div>
</div>

<div class="record-detail">
    <div class="record-date">📅
        <?php echo $formattedDate; ?>
        <?php if (!empty($record['utsav'])): ?>
            <div style="font-size: 16px; color: #E65100; margin-top: 5px; font-weight: bold;">
                🌺 उत्सव: <?php echo htmlspecialchars($record['utsav']); ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Stats -->
    <div class="stats-grid" style="margin-bottom: 24px;">
        <div class="stat-card" style="padding: 16px;">
            <div class="stat-number" style="font-size: 1.5rem;">
                <?php echo $presentCount; ?>
            </div>
            <div class="stat-label">✅ उपस्थित</div>
        </div>
        <div class="stat-card" style="padding: 16px;">
            <div class="stat-number" style="font-size: 1.5rem; color: var(--danger);">
                <?php echo $absentCount; ?>
            </div>
            <div class="stat-label">❌ अनुपस्थित</div>
        </div>
        <div class="stat-card" style="padding: 16px;">
            <div class="stat-number" style="font-size: 1.5rem; color: var(--success);">
                <?php echo $doneCount; ?>
            </div>
            <div class="stat-label">📋 गतिविधि पूर्ण</div>
        </div>
    </div>

    <!-- Attendance -->
    <div class="record-section">
        <h3>👥 उपस्थिति</h3>
        <div class="attendance-grid">
            <?php foreach ($attendance as $att): ?>
                <div class="attendance-item <?php echo $att['is_present'] ? 'present' : 'absent'; ?>">
                    <?php echo $att['is_present'] ? '✅' : '❌'; ?>
                    <span>
                        <?php echo htmlspecialchars($att['swayamsevak_name']); ?>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
        <?php if (empty($attendance)): ?>
            <p style="color: var(--text-muted);">कोई उपस्थिति डेटा नहीं।</p>
        <?php endif; ?>
    </div>

    <!-- Activities -->
    <div class="record-section">
        <h3>📋 गतिविधियाँ</h3>
        <?php foreach ($dailyActivities as $da): ?>
            <div class="activity-detail-item">
                <div class="status">
                    <?php if ($da['is_done']): ?>
                        <span class="done">✅
                            <?php echo htmlspecialchars($da['activity_name']); ?>
                        </span>
                    <?php else: ?>
                        <span class="not-done">⬜
                            <?php echo htmlspecialchars($da['activity_name']); ?>
                        </span>
                    <?php endif; ?>
                </div>
                <?php if ($da['conductor_name']): ?>
                    <span class="conductor">👤
                        <?php echo htmlspecialchars($da['conductor_name']); ?>
                    </span>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <?php if (empty($dailyActivities)): ?>
            <p style="color: var(--text-muted);">कोई गतिविधि डेटा नहीं।</p>
        <?php endif; ?>
    </div>

    <!-- Custom Message -->
    <?php if (!empty($record['custom_message'])): ?>
        <div class="record-section">
            <h3>💬 विशेष संदेश</h3>
            <div class="custom-message-box">
                <?php echo nl2br(htmlspecialchars($record['custom_message'])); ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Share Actions -->
    <div class="share-actions">
        <a href="../pages/snapshot.php?id=<?php echo $record['id']; ?>" class="btn btn-primary">📸 स्नैपशॉट बनाएँ</a>
        <a href="../pages/snapshot.php?id=<?php echo $record['id']; ?>&download=1" class="btn btn-success">⬇️ डाउनलोड करें</a>
        <button
            onclick="shareOnWhatsApp('snapshot.php?id=<?php echo $record['id']; ?>&raw=1', 'शाखा दैनिक रिपोर्ट - <?php echo $formattedDate; ?>')"
            class="btn btn-whatsapp">📱 व्हाट्सएप पर भेजें</button>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
