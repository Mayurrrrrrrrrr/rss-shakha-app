<?php
require_once '../includes/auth.php';
/**
 * Timetable Management - समय-सारणी प्रबंधन (Mukhyashikshak)
 */
require_once '../config/db.php';
requireLogin();

if (!isMukhyashikshak() && !isAdmin()) {
    header('Location: dashboard.php');
    exit;
}

// Auto-create tables
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS timetable_defaults (
        id INT AUTO_INCREMENT PRIMARY KEY,
        shakha_id INT NOT NULL,
        day_of_week TINYINT NOT NULL,
        slots JSON,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uk_shakha_day (shakha_id, day_of_week),
        FOREIGN KEY (shakha_id) REFERENCES shakhas(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS timetable_overrides (
        id INT AUTO_INCREMENT PRIMARY KEY,
        shakha_id INT NOT NULL,
        override_date DATE NOT NULL,
        slots JSON,
        UNIQUE KEY uk_shakha_date (shakha_id, override_date),
        FOREIGN KEY (shakha_id) REFERENCES shakhas(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
} catch (PDOException $e) {}

$shakhaId = getCurrentShakhaId();
$activeTab = $_GET['tab'] ?? 'default';
$selectedDay = intval($_GET['day'] ?? date('w'));

$hindiDayNames = ['रविवार', 'सोमवार', 'मंगलवार', 'बुधवार', 'गुरुवार', 'शुक्रवार', 'शनिवार'];

// Fetch all defaults for this shakha
$stmtDefaults = $pdo->prepare("SELECT day_of_week, slots FROM timetable_defaults WHERE shakha_id = ?");
$stmtDefaults->execute([$shakhaId]);
$defaults = [];
foreach ($stmtDefaults->fetchAll() as $row) {
    $defaults[$row['day_of_week']] = json_decode($row['slots'], true) ?: [];
}

// Current selected day's slots
$currentSlots = $defaults[$selectedDay] ?? [];

$pageTitle = 'समय-सारणी (Timetable)';
require_once '../includes/header.php';
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

<style>
.tt-tabs { display: flex; gap: 0; margin-bottom: 24px; border-radius: 12px; overflow: hidden; border: 1px solid var(--border-color); }
.tt-tab { flex: 1; padding: 14px; text-align: center; background: var(--bg-card); color: var(--text-secondary); font-weight: 600; cursor: pointer; transition: all 0.2s; text-decoration: none; font-size: 0.95rem; border: none; }
.tt-tab.active, .tt-tab:hover { background: var(--saffron); color: white; }

.day-tabs { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 20px; }
.day-tab { padding: 8px 14px; border-radius: 20px; background: var(--bg-input); color: var(--text-secondary); font-size: 0.85rem; font-weight: 600; cursor: pointer; text-decoration: none; transition: all 0.2s; border: 1px solid var(--border-color); }
.day-tab.active { background: var(--saffron); color: white; border-color: var(--saffron); }
.day-tab:hover { border-color: var(--saffron); color: var(--saffron-light); }
.day-tab.has-data::after { content: '✓'; margin-left: 4px; font-size: 0.75rem; }

.slot-row { display: grid; grid-template-columns: 80px 80px 1fr 44px; gap: 10px; align-items: center; margin-bottom: 10px; }
.slot-input { padding: 12px 14px; background: rgba(15,15,20,0.6); border: 1px solid var(--border-light); border-radius: 10px; color: var(--text-primary); font-size: 0.95rem; font-family: inherit; }
.slot-input:focus { outline: none; border-color: var(--saffron); box-shadow: 0 0 0 3px var(--saffron-glow); }
.slot-remove { background: rgba(239,83,80,0.15); border: 1px solid rgba(239,83,80,0.3); color: var(--danger); width: 44px; height: 44px; border-radius: 10px; cursor: pointer; font-size: 1.2rem; display: flex; align-items: center; justify-content: center; transition: all 0.2s; }
.slot-remove:hover { background: var(--danger); color: white; }

.add-slot-btn { background: rgba(255,107,0,0.1); border: 1px dashed var(--saffron); color: var(--saffron-light); padding: 12px; border-radius: 10px; cursor: pointer; text-align: center; font-size: 0.95rem; transition: all 0.2s; margin-top: 5px; }
.add-slot-btn:hover { background: rgba(255,107,0,0.2); }

.tt-premium-card { background: rgba(34,34,46,0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255,107,0,0.2); border-radius: 16px; padding: 28px; box-shadow: 0 8px 32px rgba(0,0,0,0.3); margin-bottom: 24px; }
.tt-premium-card .card-header { border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 1.2rem; padding-bottom: 15px; margin-bottom: 20px; color: var(--saffron-light); }

/* Weekly print table */
.weekly-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
.weekly-table th { background: var(--saffron); color: white; padding: 10px 8px; text-align: center; font-size: 0.8rem; }
.weekly-table td { padding: 8px; border: 1px solid var(--border-color); vertical-align: top; color: var(--text-primary); font-size: 0.8rem; }
.weekly-table tr:nth-child(even) td { background: rgba(255,255,255,0.02); }
.wt-time { color: var(--saffron-light); font-weight: 600; font-size: 0.75rem; white-space: nowrap; }
.wt-topic { color: var(--text-primary); }

@media (max-width: 600px) {
    .slot-row { grid-template-columns: 65px 65px 1fr 38px; gap: 6px; }
    .slot-input { padding: 10px; font-size: 0.85rem; }
}

@media print {
    body { background: white !important; color: #333 !important; }
    .navbar, .sidebar, .sidebar-overlay, .bottom-nav, .footer, .page-header, .tt-tabs, .day-tabs, .tt-premium-card, .share-actions, .no-print { display: none !important; }
    .main-content { padding: 0 !important; }
    .weekly-table th { background: #FF6B00 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .weekly-table td { border-color: #ccc !important; color: #333 !important; }
    .wt-time { color: #E65100 !important; }
    #print-section { display: block !important; }
}
</style>

<div class="page-header">
    <h1>📋 समय-सारणी (Timetable)</h1>
</div>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($_GET['msg']); ?></div>
<?php endif; ?>

<!-- Tabs -->
<div class="tt-tabs">
    <a href="timetable.php?tab=default&day=<?php echo $selectedDay; ?>" class="tt-tab <?php echo $activeTab === 'default' ? 'active' : ''; ?>">📅 साप्ताहिक डिफ़ॉल्ट</a>
    <a href="timetable.php?tab=override" class="tt-tab <?php echo $activeTab === 'override' ? 'active' : ''; ?>">📌 विशेष दिन</a>
    <a href="timetable.php?tab=weekly" class="tt-tab <?php echo $activeTab === 'weekly' ? 'active' : ''; ?>">🖨️ साप्ताहिक दृश्य</a>
</div>

<?php if ($activeTab === 'default'): ?>
<!-- ═══ DEFAULT WEEKLY TIMETABLE ═══ -->
<div class="day-tabs">
    <?php for ($dw = 0; $dw < 7; $dw++): ?>
        <a href="timetable.php?tab=default&day=<?php echo $dw; ?>" 
           class="day-tab <?php echo $selectedDay === $dw ? 'active' : ''; ?> <?php echo isset($defaults[$dw]) ? 'has-data' : ''; ?>">
            <?php echo $hindiDayNames[$dw]; ?>
        </a>
    <?php endfor; ?>
</div>

<div class="tt-premium-card">
    <div class="card-header">📋 <?php echo $hindiDayNames[$selectedDay]; ?> की समय-सारणी</div>
    <form method="POST" action="../actions/timetable_save.php" id="default-form">
        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
        <input type="hidden" name="save_type" value="default">
        <input type="hidden" name="day_of_week" value="<?php echo $selectedDay; ?>">
        
        <div style="display: grid; grid-template-columns: 80px 80px 1fr 44px; gap: 10px; margin-bottom: 8px; padding: 0 0 8px; border-bottom: 1px solid var(--border-color);">
            <span style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">शुरू (मि.)</span>
            <span style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">अंत (मि.)</span>
            <span style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">विषय / गतिविधि</span>
            <span></span>
        </div>
        
        <div id="slots-container">
            <?php if (!empty($currentSlots)): ?>
                <?php foreach ($currentSlots as $slot): ?>
                    <div class="slot-row">
                        <input type="number" step="any" name="slots[start_min][]" class="slot-input" value="<?php echo $slot['start_min']; ?>" min="0" placeholder="0">
                        <input type="number" step="any" name="slots[end_min][]" class="slot-input" value="<?php echo $slot['end_min']; ?>" min="1" placeholder="5">
                        <input type="text" name="slots[topic][]" class="slot-input" value="<?php echo htmlspecialchars($slot['topic']); ?>" placeholder="विषय / गतिविधि">
                        <button type="button" class="slot-remove" onclick="this.parentElement.remove()">✕</button>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="slot-row">
                    <input type="number" step="any" name="slots[start_min][]" class="slot-input" value="0" min="0">
                    <input type="number" step="any" name="slots[end_min][]" class="slot-input" value="5" min="1">
                    <input type="text" name="slots[topic][]" class="slot-input" placeholder="विषय / गतिविधि">
                    <button type="button" class="slot-remove" onclick="this.parentElement.remove()">✕</button>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="add-slot-btn" onclick="addSlot()">+ समय-खंड जोड़ें</div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 16px;">
            <button type="submit" name="save_type" value="default" class="btn btn-primary" style="border-radius: 12px; padding: 15px; font-size: 1rem;">
                💾 <?php echo $hindiDayNames[$selectedDay]; ?> सहेजें
            </button>
            <button type="submit" name="save_type" value="copy_all" class="btn btn-success" style="border-radius: 12px; padding: 15px; font-size: 1rem;" onclick="return confirm('क्या आप इस समय-सारणी को सभी 7 दिनों पर कॉपी करना चाहते हैं? पुराने डेटा नष्ट हो जाएंगे।');">
                📋 सभी 7 दिनों में कॉपी करें
            </button>
        </div>
    </form>
</div>

<?php elseif ($activeTab === 'override'): ?>
<!-- ═══ DATE OVERRIDE ═══ -->
<?php
    $overrideDate = $_GET['date'] ?? date('Y-m-d');
    $overrideDow = date('w', strtotime($overrideDate));
    
    // Check if override exists for this date
    $stmtOvr = $pdo->prepare("SELECT slots FROM timetable_overrides WHERE shakha_id = ? AND override_date = ?");
    $stmtOvr->execute([$shakhaId, $overrideDate]);
    $ovrRow = $stmtOvr->fetch();
    
    if ($ovrRow) {
        $overrideSlots = json_decode($ovrRow['slots'], true) ?: [];
    } else {
        // Pre-fill from default
        $overrideSlots = $defaults[$overrideDow] ?? [];
    }
?>

<div class="tt-premium-card">
    <div class="card-header">📌 विशेष दिन की समय-सारणी</div>
    
    <form method="GET" action="timetable.php" style="margin-bottom: 20px; display: flex; gap: 10px; align-items: end; flex-wrap: wrap;">
        <input type="hidden" name="tab" value="override">
        <div>
            <label style="display: block; font-size: 0.85rem; color: var(--text-muted); margin-bottom: 6px;">दिनांक चुनें</label>
            <input type="date" name="date" value="<?php echo $overrideDate; ?>" class="slot-input" style="width: 200px;">
        </div>
        <button type="submit" class="btn btn-outline btn-sm">📅 लोड करें</button>
    </form>
    
    <div style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 16px;">
        📅 <strong><?php echo $hindiDayNames[$overrideDow]; ?></strong> — <?php echo date('d M Y', strtotime($overrideDate)); ?>
        <?php if ($ovrRow): ?>
            <span class="badge badge-saffron" style="font-size: 0.75rem; margin-left: 8px;">कस्टम ओवरराइड सेट</span>
        <?php else: ?>
            <span style="font-size: 0.8rem; color: var(--text-muted);">(डिफ़ॉल्ट से लोड)</span>
        <?php endif; ?>
    </div>
    
    <form method="POST" action="../actions/timetable_save.php" id="override-form">
        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
        <input type="hidden" name="override_date" value="<?php echo $overrideDate; ?>">
        
        <div style="display: grid; grid-template-columns: 80px 80px 1fr 44px; gap: 10px; margin-bottom: 8px; padding: 0 0 8px; border-bottom: 1px solid var(--border-color);">
            <span style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">शुरू (मि.)</span>
            <span style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">अंत (मि.)</span>
            <span style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">विषय / गतिविधि</span>
            <span></span>
        </div>
        
        <div id="override-slots-container">
            <?php if (!empty($overrideSlots)): ?>
                <?php foreach ($overrideSlots as $slot): ?>
                    <div class="slot-row">
                        <input type="number" step="any" name="slots[start_min][]" class="slot-input" value="<?php echo $slot['start_min']; ?>" min="0">
                        <input type="number" step="any" name="slots[end_min][]" class="slot-input" value="<?php echo $slot['end_min']; ?>" min="1">
                        <input type="text" name="slots[topic][]" class="slot-input" value="<?php echo htmlspecialchars($slot['topic']); ?>" placeholder="विषय / गतिविधि">
                        <button type="button" class="slot-remove" onclick="this.parentElement.remove()">✕</button>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="slot-row">
                    <input type="number" step="any" name="slots[start_min][]" class="slot-input" value="0" min="0">
                    <input type="number" step="any" name="slots[end_min][]" class="slot-input" value="5" min="1">
                    <input type="text" name="slots[topic][]" class="slot-input" placeholder="विषय / गतिविधि">
                    <button type="button" class="slot-remove" onclick="this.parentElement.remove()">✕</button>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="add-slot-btn" onclick="addSlotOverride()">+ समय-खंड जोड़ें</div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 16px;">
            <button type="submit" name="save_type" value="override" class="btn btn-primary" style="border-radius: 12px; padding: 15px; font-size: 1rem;">
                💾 सिर्फ़ इस दिन के लिए
            </button>
            <button type="submit" name="save_type" value="override_to_default" class="btn btn-success" style="border-radius: 12px; padding: 15px; font-size: 1rem;">
                💾 सभी <?php echo $hindiDayNames[$overrideDow]; ?> के लिए
            </button>
        </div>
    </form>
</div>

<?php elseif ($activeTab === 'weekly'): ?>
<!-- ═══ WEEKLY VIEW (PRINTABLE) ═══ -->
<?php
    // Gather all unique time slots across all days
    $allSlots = [];
    for ($dw = 0; $dw < 7; $dw++) {
        $daySlots = $defaults[$dw] ?? [];
        foreach ($daySlots as $s) {
            $key = $s['start_min'] . '-' . $s['end_min'];
            if (!isset($allSlots[$key])) {
                $allSlots[$key] = ['start' => $s['start_min'], 'end' => $s['end_min']];
            }
        }
    }
    usort($allSlots, fn($a, $b) => $a['start'] - $b['start']);
    
    // Build lookup: day => start-end => topic
    $lookup = [];
    for ($dw = 0; $dw < 7; $dw++) {
        foreach (($defaults[$dw] ?? []) as $s) {
            $lookup[$dw][$s['start_min'] . '-' . $s['end_min']] = $s['topic'];
        }
    }
?>

<div class="no-print" style="text-align: center; margin-bottom: 16px;">
    <button onclick="window.print()" class="btn btn-primary" style="border-radius: 12px;">🖨️ प्रिंट करें</button>
</div>

<div id="print-section" class="tt-premium-card" style="overflow-x: auto;">
    <div class="card-header" style="text-align: center;">📋 साप्ताहिक समय-सारणी — <?php echo htmlspecialchars($shakhaName ?? ''); ?></div>
    
    <?php if (empty($allSlots)): ?>
        <div class="empty-state">
            <div class="icon">📋</div>
            <p>अभी तक कोई समय-सारणी नहीं बनाई गई है।</p>
            <a href="timetable.php?tab=default" class="btn btn-primary" style="margin-top: 10px;">📅 पहले डिफ़ॉल्ट बनाएं</a>
        </div>
    <?php else: ?>
        <table class="weekly-table">
            <thead>
                <tr>
                    <th style="min-width: 90px;">समय (मि.)</th>
                    <?php foreach ($hindiDayNames as $dn): ?>
                        <th><?php echo $dn; ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($allSlots as $ts): ?>
                    <tr>
                        <td><span class="wt-time"><?php echo $ts['start'] . '–' . $ts['end']; ?></span></td>
                        <?php for ($dw = 0; $dw < 7; $dw++): ?>
                            <td>
                                <span class="wt-topic"><?php echo htmlspecialchars($lookup[$dw][$ts['start'] . '-' . $ts['end']] ?? '—'); ?></span>
                            </td>
                        <?php endfor; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php endif; ?>

<?php
// Fetch shakha name for weekly view
$stmtSN = $pdo->prepare("SELECT name FROM shakhas WHERE id = ?");
$stmtSN->execute([$shakhaId]);
$shakhaName = $stmtSN->fetchColumn() ?: 'शाखा';
?>

<script>
function addSlot() {
    const container = document.getElementById('slots-container');
    const lastRow = container.querySelector('.slot-row:last-child');
    let nextStart = 0;
    if (lastRow) {
        const endInput = lastRow.querySelector('input[name="slots[end_min][]"]');
        nextStart = parseInt(endInput.value) || 0;
    }
    const row = document.createElement('div');
    row.className = 'slot-row';
    row.innerHTML = `
        <input type="number" step="any" name="slots[start_min][]" class="slot-input" value="${nextStart}" min="0">
        <input type="number" step="any" name="slots[end_min][]" class="slot-input" value="${nextStart + 5}" min="1">
        <input type="text" name="slots[topic][]" class="slot-input" placeholder="विषय / गतिविधि">
        <button type="button" class="slot-remove" onclick="this.parentElement.remove()">✕</button>
    `;
    container.appendChild(row);
}

function addSlotOverride() {
    const container = document.getElementById('override-slots-container');
    const lastRow = container.querySelector('.slot-row:last-child');
    let nextStart = 0;
    if (lastRow) {
        const endInput = lastRow.querySelector('input[name="slots[end_min][]"]');
        nextStart = parseInt(endInput.value) || 0;
    }
    const row = document.createElement('div');
    row.className = 'slot-row';
    row.innerHTML = `
        <input type="number" step="any" name="slots[start_min][]" class="slot-input" value="${nextStart}" min="0">
        <input type="number" step="any" name="slots[end_min][]" class="slot-input" value="${nextStart + 5}" min="1">
        <input type="text" name="slots[topic][]" class="slot-input" placeholder="विषय / गतिविधि">
        <button type="button" class="slot-remove" onclick="this.parentElement.remove()">✕</button>
    `;
    container.appendChild(row);
}
</script>

<?php require_once '../includes/footer.php'; ?>
