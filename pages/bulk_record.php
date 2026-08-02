<?php
require_once '../includes/auth.php';
/**
 * Bulk Daily Record Form - थोक दैनिक रिकॉर्ड
 */
$pageTitle = 'थोक दैनिक रिकॉर्ड';
require_once '../includes/header.php';
require_once '../config/db.php';
requireLogin();

if (isSwayamsevak()) {
    header('Location: swayamsevak_dashboard.php');
    exit;
}

$shakhaId = getCurrentShakhaId();
$startDate = $_GET['start_date'] ?? date('Y-m-d');
$numDays = intval($_GET['num_days'] ?? 7);
if ($numDays < 1) $numDays = 1;
if ($numDays > 14) $numDays = 14; // Limit to 2 weeks for performance

// Fetch all active swayamsevaks and activities for this shakha
$stmt = $pdo->prepare("SELECT id, name FROM swayamsevaks WHERE is_active = 1 AND shakha_id = ? ORDER BY name");
$stmt->execute([$shakhaId]);
$swayamsevaks = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT id, name FROM activities WHERE is_active = 1 AND (shakha_id IS NULL OR shakha_id = ?) ORDER BY sort_order, id");
$stmt->execute([$shakhaId]);
$activities = $stmt->fetchAll();

// Generate date range
$dates = [];
$startTs = strtotime($startDate);
for ($i = 0; $i < $numDays; $i++) {
    $dates[] = date('Y-m-d', strtotime("+$i day", $startTs));
}

// Fetch existing records for this range
$existingRecords = [];
$existingActivities = []; // [date][activity_id] = ['is_done' => ..., 'conducted_by' => ...]

if (!empty($dates)) {
    $placeholders = implode(',', array_fill(0, count($dates), '?'));
    
    // Fetch daily records
    $stmt = $pdo->prepare("SELECT * FROM daily_records WHERE record_date IN ($placeholders) AND shakha_id = ?");
    $stmt->execute(array_merge($dates, [$shakhaId]));
    $records = $stmt->fetchAll();
    
    $recordIds = [];
    foreach ($records as $r) {
        $existingRecords[$r['record_date']] = $r;
        $recordIds[$r['id']] = $r['record_date'];
    }
    
    if (!empty($recordIds)) {
        $idPlaceholders = implode(',', array_fill(0, count($recordIds), '?'));
        $stmt = $pdo->prepare("SELECT * FROM daily_activities WHERE daily_record_id IN ($idPlaceholders)");
        $stmt->execute(array_keys($recordIds));
        $daList = $stmt->fetchAll();
        
        foreach ($daList as $da) {
            $dateKey = $recordIds[$da['daily_record_id']];
            if (!isset($existingActivities[$dateKey])) {
                $existingActivities[$dateKey] = [];
            }
            $existingActivities[$dateKey][$da['activity_id']] = $da;
        }
    }
}

$hindiMonths = ['जनवरी','फ़रवरी','मार्च','अप्रैल','मई','जून','जुलाई','अगस्त','सितंबर','अक्टूबर','नवंबर','दिसंबर'];
$hindiDays = ['रविवार','सोमवार','मंगलवार','बुधवार','गुरुवार','शुक्रवार','शनिवार'];

function formatHindiDateShort($dateStr) {
    global $hindiMonths, $hindiDays;
    $ts = strtotime($dateStr);
    $day = $hindiDays[date('w', $ts)];
    $d = date('j', $ts);
    $m = $hindiMonths[date('n', $ts) - 1];
    return "$day, $d $m";
}
?>

<style>
    .bulk-container {
        display: flex;
        gap: 16px;
        overflow-x: auto;
        padding-bottom: 20px;
        scroll-snap-type: x mandatory;
    }
    .bulk-card {
        flex: 0 0 320px;
        scroll-snap-align: start;
        background: #fff;
        border-radius: 12px;
        border: 1px solid #e0e0e0;
        box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        display: flex;
        flex-direction: column;
        padding: 16px;
    }
    .bulk-card-header {
        border-bottom: 2px solid #ff9800;
        padding-bottom: 8px;
        margin-bottom: 12px;
    }
    .bulk-card-title {
        font-size: 1.15rem;
        font-weight: 700;
        color: #e65100;
    }
    .activity-item {
        background: #fff9f2;
        border: 1px solid #ffe0b2;
        border-radius: 8px;
        padding: 10px;
        margin-bottom: 10px;
    }
    .activity-title {
        font-weight: 600;
        color: #5d4037;
        margin-bottom: 6px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .copy-btn {
        background: #efebe9;
        border: 1px solid #d7ccc8;
        border-radius: 4px;
        color: #4e342e;
        font-size: 0.75rem;
        padding: 2px 6px;
        cursor: pointer;
        font-weight: bold;
        transition: all 0.2s;
    }
    .copy-btn:hover {
        background: #d7ccc8;
    }
</style>

<div class="page-header">
    <h1>📝 थोक दैनिक रिकॉर्ड (Bulk Update)</h1>
</div>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'saved'): ?>
    <div class="alert alert-success">✅ सभी रिकॉर्ड सफलतापूर्वक सहेजे गए!</div>
<?php endif; ?>

<div class="card" style="margin-bottom: 20px;">
    <div class="card-header">📅 थोक प्रविष्टि तिथि एवं अवधि चुनें</div>
    <form method="GET" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end;">
        <div class="form-group" style="flex: 1; min-width: 200px; margin-bottom: 0;">
            <label for="start_date">प्रारंभ तिथि (Start Date)</label>
            <input type="date" id="start_date" name="start_date" class="form-control" value="<?php echo $startDate; ?>" required>
        </div>
        <div class="form-group" style="flex: 1; min-width: 150px; margin-bottom: 0;">
            <label for="num_days">दिनों की संख्या (Days count)</label>
            <select id="num_days" name="num_days" class="form-control">
                <?php for($d = 1; $d <= 14; $d++): ?>
                    <option value="<?php echo $d; ?>" <?php echo $numDays == $d ? 'selected' : ''; ?>><?php echo $d; ?> दिन</option>
                <?php endfor; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary" style="height: 46px;">🔍 लोड करें</button>
    </form>
</div>

<form method="POST" action="../api/actions/bulk_record_save.php">
    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
    <input type="hidden" name="start_date" value="<?php echo $startDate; ?>">
    <input type="hidden" name="num_days" value="<?php echo $numDays; ?>">

    <div class="bulk-container">
        <?php foreach ($dates as $index => $date): 
            $er = $existingRecords[$date] ?? null;
            $notes = $er ? $er['custom_message'] : '';
        ?>
            <div class="bulk-card">
                <div class="bulk-card-header">
                    <div class="bulk-card-title"><?php echo formatHindiDateShort($date); ?></div>
                    <span style="font-size: 0.8rem; color: #757575;"><?php echo $date; ?></span>
                </div>
                
                <div style="flex-grow: 1;">
                    <h4 style="font-size: 0.95rem; margin-bottom: 8px; color: #e65100;">📋 गतिविधियाँ</h4>
                    <?php if (empty($activities)): ?>
                        <div class="alert alert-info" style="font-size: 0.85rem; padding: 8px;">ℹ️ गतिविधियाँ उपलब्ध नहीं हैं।</div>
                    <?php else: ?>
                        <?php foreach ($activities as $act): 
                            $ea = $existingActivities[$date][$act['id']] ?? null;
                            $isDone = ($ea && $ea['is_done']) ? true : false;
                            $conductedBy = $ea ? $ea['conducted_by'] : '';
                        ?>
                            <div class="activity-item" data-activity-id="<?php echo $act['id']; ?>">
                                <div class="activity-title">
                                    <input type="checkbox" 
                                           name="record[<?php echo $date; ?>][activity_done][<?php echo $act['id']; ?>]" 
                                           value="1" 
                                           class="act-checkbox"
                                           style="width: 16px; height: 16px; cursor: pointer;"
                                           <?php echo $isDone ? 'checked' : ''; ?>>
                                    <span><?php echo htmlspecialchars($act['name']); ?></span>
                                </div>
                                <div style="display: flex; gap: 8px; align-items: center;">
                                    <select name="record[<?php echo $date; ?>][conducted_by][<?php echo $act['id']; ?>]" 
                                            class="form-control act-select" 
                                            style="height: 32px; padding: 2px 8px; font-size: 0.85rem;">
                                        <option value="">-- संचालक --</option>
                                        <?php foreach ($swayamsevaks as $s): ?>
                                            <option value="<?php echo $s['id']; ?>" <?php echo $conductedBy == $s['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($s['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="button" class="copy-btn" title="यह संचालक सभी दिनों में कॉपी करें" onclick="copyActivityToAll('<?php echo $act['id']; ?>', <?php echo $index; ?>)">🔗 कॉपी</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div style="margin-top: 15px;">
                    <label style="font-size: 0.85rem; font-weight: bold; color: #5d4037; display: block; margin-bottom: 4px;">💬 विशेष टिप्पणी / नोट्स</label>
                    <textarea name="record[<?php echo $date; ?>][notes]" class="form-control" rows="2" style="font-size: 0.85rem;" placeholder="आज के नोट्स यहाँ लिखें..."><?php echo htmlspecialchars($notes); ?></textarea>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="d-flex gap-1" style="margin-top: 20px;">
        <button type="submit" class="btn btn-primary btn-lg">💾 सभी रिकॉर्ड सहेजें (Save All)</button>
        <a href="../pages/dashboard.php" class="btn btn-outline">❌ रद्द करें</a>
    </div>
</form>

<script>
function copyActivityToAll(activityId, fromIndex) {
    // Find the source select and checkbox elements
    const cards = document.querySelectorAll('.bulk-card');
    if (fromIndex >= cards.length) return;
    
    const sourceCard = cards[fromIndex];
    const sourceItem = sourceCard.querySelector(`.activity-item[data-activity-id="${activityId}"]`);
    if (!sourceItem) return;
    
    const sourceSelect = sourceItem.querySelector('.act-select');
    const sourceCheckbox = sourceItem.querySelector('.act-checkbox');
    
    const selectedVal = sourceSelect.value;
    const isChecked = sourceCheckbox.checked;
    
    // Copy to all other cards
    cards.forEach((card, idx) => {
        if (idx === fromIndex) return; // skip self
        
        const targetItem = card.querySelector(`.activity-item[data-activity-id="${activityId}"]`);
        if (targetItem) {
            const targetSelect = targetItem.querySelector('.act-select');
            const targetCheckbox = targetItem.querySelector('.act-checkbox');
            
            if (targetSelect) targetSelect.value = selectedVal;
            if (targetCheckbox) targetCheckbox.checked = isChecked;
        }
    });
    
    // Toast notification or highlight
    alert('यह गतिविधि की जानकारी सभी दिनों में कॉपी कर दी गई है!');
}
</script>

<?php require_once '../includes/footer.php'; ?>
