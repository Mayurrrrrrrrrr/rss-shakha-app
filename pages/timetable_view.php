<?php
require_once '../includes/auth.php';
/**
 * Timetable View - समय-सारणी दृश्य (Read-only, for a specific date)
 * Accessible by Mukhyashikshak AND Swayamsevak
 */
require_once '../config/db.php';
requireLogin();

// Auto-create tables
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS timetable_defaults (
        id INT AUTO_INCREMENT PRIMARY KEY, shakha_id INT NOT NULL, day_of_week TINYINT NOT NULL,
        slots JSON, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uk_shakha_day (shakha_id, day_of_week),
        FOREIGN KEY (shakha_id) REFERENCES shakhas(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    $pdo->exec("CREATE TABLE IF NOT EXISTS timetable_overrides (
        id INT AUTO_INCREMENT PRIMARY KEY, shakha_id INT NOT NULL, override_date DATE NOT NULL,
        slots JSON, UNIQUE KEY uk_shakha_date (shakha_id, override_date),
        FOREIGN KEY (shakha_id) REFERENCES shakhas(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
} catch (PDOException $e) {}

$shakhaId = getCurrentShakhaId();
$viewDate = $_GET['date'] ?? date('Y-m-d');
$dayOfWeek = date('w', strtotime($viewDate));

$hindiMonths = ['जनवरी', 'फ़रवरी', 'मार्च', 'अप्रैल', 'मई', 'जून', 'जुलाई', 'अगस्त', 'सितंबर', 'अक्टूबर', 'नवंबर', 'दिसंबर'];
$hindiDaysFull = ['रविवार', 'सोमवार', 'मंगलवार', 'बुधवार', 'गुरुवार', 'शुक्रवार', 'शनिवार'];

$ts = strtotime($viewDate);
$formattedDate = $hindiDaysFull[date('w', $ts)] . ', ' . date('j', $ts) . ' ' . $hindiMonths[date('n', $ts) - 1] . ' ' . date('Y', $ts);

// Shakha name
$stmtSN = $pdo->prepare("SELECT name FROM shakhas WHERE id = ?");
$stmtSN->execute([$shakhaId]);
$shakhaName = $stmtSN->fetchColumn() ?: 'शाखा';

// Resolve timetable: override first, then default
$isOverride = false;
$stmtOvr = $pdo->prepare("SELECT slots FROM timetable_overrides WHERE shakha_id = ? AND override_date = ?");
$stmtOvr->execute([$shakhaId, $viewDate]);
$ovrRow = $stmtOvr->fetch();

if ($ovrRow) {
    $slots = json_decode($ovrRow['slots'], true) ?: [];
    $isOverride = true;
} else {
    $stmtDef = $pdo->prepare("SELECT slots FROM timetable_defaults WHERE shakha_id = ? AND day_of_week = ?");
    $stmtDef->execute([$shakhaId, $dayOfWeek]);
    $defRow = $stmtDef->fetch();
    $slots = $defRow ? (json_decode($defRow['slots'], true) ?: []) : [];
}

$pageTitle = 'समय-सारणी — ' . date('d M Y', $ts);
require_once '../includes/header.php';
?>

<style>
.tv-card { background: rgba(34,34,46,0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255,107,0,0.15); border-radius: 16px; padding: 28px; box-shadow: 0 8px 32px rgba(0,0,0,0.3); max-width: 600px; margin: 0 auto; }
.tv-header { text-align: center; margin-bottom: 20px; padding-bottom: 16px; border-bottom: 1px solid rgba(255,255,255,0.06); }
.tv-date-big { font-size: 1.3rem; font-weight: 700; color: var(--saffron-light); }
.tv-shakha { font-size: 0.9rem; color: var(--text-muted); margin-top: 4px; }
.tv-tag { display: inline-block; font-size: 0.75rem; padding: 3px 10px; border-radius: 12px; margin-top: 8px; }
.tv-tag-default { background: rgba(76,175,80,0.15); color: #66BB6A; border: 1px solid rgba(76,175,80,0.3); }
.tv-tag-override { background: rgba(255,107,0,0.15); color: var(--saffron-light); border: 1px solid rgba(255,107,0,0.3); }

.tv-slot { display: flex; align-items: stretch; margin-bottom: 8px; border-radius: 10px; overflow: hidden; transition: transform 0.15s; }
.tv-slot:hover { transform: translateX(4px); }
.tv-time { min-width: 100px; padding: 14px 12px; background: var(--saffron); color: white; font-weight: 700; font-size: 0.9rem; display: flex; align-items: center; justify-content: center; text-align: center; }
.tv-topic { flex: 1; padding: 14px 16px; background: rgba(255,255,255,0.04); border: 1px solid var(--border-color); border-left: 0; font-size: 1rem; color: var(--text-primary); display: flex; align-items: center; }
.tv-slot:nth-child(even) .tv-time { background: #E65100; }

.tv-actions { display: flex; gap: 10px; margin-top: 20px; justify-content: center; flex-wrap: wrap; }

@media print {
    body { background: white !important; color: #333 !important; }
    .navbar, .sidebar, .sidebar-overlay, .bottom-nav, .footer, .no-print { display: none !important; }
    .main-content { padding: 0 !important; }
    .tv-card { box-shadow: none !important; border: 2px solid #FF6B00 !important; background: white !important; }
    .tv-time { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .tv-topic { color: #333 !important; border-color: #ddd !important; }
    .tv-date-big { color: #E65100 !important; }
}
</style>

<div class="page-header">
    <h1>📋 समय-सारणी</h1>
    <a href="javascript:history.back()" class="btn btn-outline btn-sm no-print">← वापस</a>
</div>

<div class="tv-card">
    <div class="tv-header">
        <div class="tv-date-big">📅 <?php echo $formattedDate; ?></div>
        <div class="tv-shakha">🚩 <?php echo htmlspecialchars($shakhaName); ?></div>
        <?php if ($isOverride): ?>
            <span class="tv-tag tv-tag-override">📌 विशेष दिन</span>
        <?php else: ?>
            <span class="tv-tag tv-tag-default">📅 साप्ताहिक डिफ़ॉल्ट</span>
        <?php endif; ?>
    </div>

    <?php if (empty($slots)): ?>
        <div class="empty-state" style="padding: 30px 0;">
            <div class="icon">📋</div>
            <p>इस दिन की समय-सारणी अभी सेट नहीं है।</p>
            <?php if (isMukhyashikshak() || isAdmin()): ?>
                <a href="timetable.php?tab=override&date=<?php echo $viewDate; ?>" class="btn btn-primary" style="margin-top: 10px;">📝 समय-सारणी बनाएं</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <?php foreach ($slots as $idx => $slot): ?>
            <div class="tv-slot">
                <div class="tv-time"><?php echo $slot['start_min']; ?> – <?php echo $slot['end_min']; ?> मि.</div>
                <div class="tv-topic"><?php echo htmlspecialchars($slot['topic']); ?></div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <div class="tv-actions no-print">
        <button onclick="window.print()" class="btn btn-outline btn-sm">🖨️ प्रिंट</button>
        <?php if (isMukhyashikshak() || isAdmin()): ?>
            <a href="timetable.php?tab=override&date=<?php echo $viewDate; ?>" class="btn btn-primary btn-sm">✏️ संपादित करें</a>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
