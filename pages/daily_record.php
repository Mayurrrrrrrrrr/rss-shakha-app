<?php
/**
 * Daily Record Form - दैनिक रिकॉर्ड
 */
$pageTitle = 'दैनिक रिकॉर्ड';
require_once '../includes/header.php';
require_once '../config/db.php';
requireLogin();

if (isSwayamsevak()) {
    header('Location: swayamsevak_dashboard.php');
    exit;
}

$date = $_GET['date'] ?? date('Y-m-d');
$editId = $_GET['id'] ?? null;
$existingRecord = null;
$existingActivities = [];
$existingAttendance = [];

$shakhaId = getCurrentShakhaId();

// Check if record exists for this date or by ID
if ($editId) {
    $stmt = $pdo->prepare("SELECT * FROM daily_records WHERE id = ? AND shakha_id = ?");
    $stmt->execute([$editId, $shakhaId]);
    $existingRecord = $stmt->fetch();
    if ($existingRecord) $date = $existingRecord['record_date'];
} else {
    $stmt = $pdo->prepare("SELECT * FROM daily_records WHERE record_date = ? AND shakha_id = ?");
    $stmt->execute([$date, $shakhaId]);
    $existingRecord = $stmt->fetch();
}

// Load existing data if editing
if ($existingRecord) {
    $stmt = $pdo->prepare("SELECT * FROM daily_activities WHERE daily_record_id = ?");
    $stmt->execute([$existingRecord['id']]);
    foreach ($stmt->fetchAll() as $da) {
        $existingActivities[$da['activity_id']] = $da;
    }
    
    $stmt = $pdo->prepare("SELECT * FROM attendance WHERE daily_record_id = ?");
    $stmt->execute([$existingRecord['id']]);
    foreach ($stmt->fetchAll() as $att) {
        $existingAttendance[$att['swayamsevak_id']] = $att['is_present'];
    }
}

// Load all active swayamsevaks and activities for this shakha
$stmt = $pdo->prepare("SELECT * FROM swayamsevaks WHERE is_active = 1 AND shakha_id = ? ORDER BY name");
$stmt->execute([$shakhaId]);
$swayamsevaks = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM activities WHERE is_active = 1 AND (shakha_id IS NULL OR shakha_id = ?) ORDER BY sort_order, id");
$stmt->execute([$shakhaId]);
$activities = $stmt->fetchAll();

$hindiMonths = ['जनवरी','फ़रवरी','मार्च','अप्रैल','मई','जून','जुलाई','अगस्त','सितंबर','अक्टूबर','नवंबर','दिसंबर'];

// Auto-calculate approximate Samvats for the selected date
$dt = strtotime($date);
$yy = (int)date('Y', $dt);
$mm = (int)date('n', $dt);
// Starts in Chaitra (roughly mid-March/April)
$autoYg = ($mm >= 4) ? $yy + 3102 : $yy + 3101;
$autoVs = ($mm >= 4) ? $yy + 57 : $yy + 56;
$autoSs = ($mm >= 4) ? $yy - 78 : $yy - 79;
?>

<div class="page-header">
    <h1>📝 दैनिक रिकॉर्ड</h1>
</div>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'saved'): ?>
    <div class="alert alert-success">✅ रिकॉर्ड सफलतापूर्वक सहेजा गया!</div>
<?php endif; ?>

<form method="POST" action="../actions/daily_record_save.php">
    <?php if ($existingRecord): ?>
        <input type="hidden" name="record_id" value="<?php echo $existingRecord['id']; ?>">
    <?php endif; ?>

    <!-- Date Selection -->
    <div class="card">
        <div class="card-header">📅 कैलेंडर तारीख चुनें</div>
        <div class="form-group">
            <label for="record_date">कैलेंडर तारीख</label>
            <input type="date" id="record_date" name="record_date" class="form-control" 
                   value="<?php echo $date; ?>" required
                   onchange="window.location.href='../pages/daily_record.php?date='+this.value">
        </div>
        <?php if ($existingRecord): ?>
            <div class="alert alert-info">ℹ️ इस तारीख का रिकॉर्ड पहले से मौजूद है। संपादन किया जा सकता है।</div>
        <?php endif; ?>
    </div>

    <!-- Tithi Selection -->
    <div class="card" style="border-left: 4px solid #FF9800;">
        <div class="card-header" style="background: rgba(255, 152, 0, 0.1); color: #E65100; display: flex; justify-content: space-between; align-items: center;">
            <span>🕉️ पंचांग (तिथि)</span>
            <button type="button" id="btn-fetch-panchang" class="btn btn-sm" style="background: #FFF3E0; color: #E65100; border: 1px solid #FFCC80; font-weight: bold; padding: 4px 10px;">✨ ऑटो-फिल पंचांग</button>
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px;">
            
            <div class="form-group">
                <label>युगाब्द</label>
                <select name="yugabdh" class="form-control">
                    <option value="">-- चुनें --</option>
                    <?php 
                        $curYg = 5125; // Base Yg
                        for($i = $curYg; $i <= $curYg+5; $i++) {
                            $sel = '';
                            if ($existingRecord && isset($existingRecord['yugabdh']) && $existingRecord['yugabdh'] == $i) {
                                $sel = 'selected';
                            } elseif (!$existingRecord && $autoYg == $i) {
                                $sel = 'selected';
                            }
                            echo "<option value='$i' $sel>$i</option>";
                        }
                    ?>
                </select>
            </div>

            <div class="form-group">
                <label>विक्रम संवत्</label>
                <select name="vikram_samvat" class="form-control">
                    <option value="">-- चुनें --</option>
                    <?php 
                        $curVs = 2080; // Base Vs
                        for($i = $curVs - 1; $i <= $curVs+5; $i++) {
                            $sel = '';
                            if ($existingRecord && isset($existingRecord['vikram_samvat']) && $existingRecord['vikram_samvat'] == $i) {
                                $sel = 'selected';
                            } elseif (!$existingRecord && $autoVs == $i) {
                                $sel = 'selected';
                            }
                            echo "<option value='$i' $sel>$i</option>";
                        }
                    ?>
                </select>
            </div>

            <div class="form-group">
                <label>शक संवत्</label>
                <select name="shaka_samvat" class="form-control">
                    <option value="">-- चुनें --</option>
                    <?php 
                        $curSs = 1945; // Base Ss
                        for($i = $curSs - 1; $i <= $curSs+5; $i++) {
                            $sel = '';
                            if ($existingRecord && isset($existingRecord['shaka_samvat']) && $existingRecord['shaka_samvat'] == $i) {
                                $sel = 'selected';
                            } elseif (!$existingRecord && $autoSs == $i) {
                                $sel = 'selected';
                            }
                            echo "<option value='$i' $sel>$i</option>";
                        }
                    ?>
                </select>
            </div>

            <div class="form-group">
                <label>मास (महीना)</label>
                <select name="hindi_month" class="form-control">
                    <option value="">-- चुनें --</option>
                    <?php 
                        $hMonths = ['चैत्र', 'वैशाख', 'ज्येष्ठ', 'आषाढ़', 'श्रावण', 'भाद्रपद', 'आश्विन', 'कार्तिक', 'मार्गशीर्ष', 'पौष', 'माघ', 'फाल्गुन'];
                        foreach($hMonths as $hm) {
                            $sel = ($existingRecord && isset($existingRecord['hindi_month']) && $existingRecord['hindi_month'] == $hm) ? 'selected' : '';
                            echo "<option value='$hm' $sel>$hm</option>";
                        }
                    ?>
                </select>
            </div>

            <div class="form-group">
                <label>पक्ष</label>
                <select name="paksh" class="form-control">
                    <option value="">-- चुनें --</option>
                    <option value="शुक्ल पक्ष" <?php echo ($existingRecord && isset($existingRecord['paksh']) && $existingRecord['paksh'] == 'शुक्ल पक्ष') ? 'selected' : ''; ?>>शुक्ल पक्ष</option>
                    <option value="कृष्ण पक्ष" <?php echo ($existingRecord && isset($existingRecord['paksh']) && $existingRecord['paksh'] == 'कृष्ण पक्ष') ? 'selected' : ''; ?>>कृष्ण पक्ष</option>
                </select>
            </div>

            <div class="form-group">
                <label>तिथि</label>
                <select name="tithi" class="form-control">
                    <option value="">-- चुनें --</option>
                    <?php 
                        $tithis = ['प्रतिपदा', 'द्वितीया', 'तृतीया', 'चतुर्थी', 'पंचमी', 'षष्ठी', 'सप्तमी', 'अष्टमी', 'नवमी', 'दशमी', 'एकादशी', 'द्वादशी', 'त्रयोदशी', 'चतुर्दशी', 'पूर्णिमा', 'अमावस्या'];
                        foreach($tithis as $t) {
                            $sel = ($existingRecord && isset($existingRecord['tithi']) && $existingRecord['tithi'] == $t) ? 'selected' : '';
                            echo "<option value='$t' $sel>$t</option>";
                        }
                    ?>
                </select>
            </div>

            <div class="form-group" style="grid-column: 1 / -1;">
                <label>उत्सव (Festival) - वैकल्पिक</label>
                <input type="text" name="utsav" class="form-control" placeholder="उदा. रक्षाबंधन, विजयादशमी..." value="<?php echo htmlspecialchars($existingRecord['utsav'] ?? ''); ?>">
            </div>

        </div>
    </div>
        <div class="card-header">📅 तारीख चुनें</div>
        <div class="form-group">
            <label for="record_date">तारीख</label>
            <input type="date" id="record_date" name="record_date" class="form-control" 
                   value="<?php echo $date; ?>" required
                   onchange="window.location.href='../pages/daily_record.php?date='+this.value">
        </div>
        <?php if ($existingRecord): ?>
            <div class="alert alert-info">ℹ️ इस तारीख का रिकॉर्ड पहले से मौजूद है। संपादन किया जा सकता है।</div>
        <?php endif; ?>
    </div>

    <!-- Attendance -->
    <div class="card">
        <div class="card-header">👥 उपस्थिति (<?php echo count($swayamsevaks); ?> स्वयंसेवक)</div>
        <?php if (empty($swayamsevaks)): ?>
            <div class="alert alert-info">ℹ️ पहले <a href="../pages/swayamsevaks.php">स्वयंसेवक जोड़ें</a>।</div>
        <?php else: ?>
            <div class="checkbox-group">
                <?php foreach ($swayamsevaks as $s): ?>
                    <label class="checkbox-item">
                        <input type="checkbox" name="attendance[<?php echo $s['id']; ?>]" value="1"
                               <?php echo (isset($existingAttendance[$s['id']]) && $existingAttendance[$s['id']]) ? 'checked' : ''; ?>>
                        <span class="checkbox-label"><?php echo htmlspecialchars($s['name']); ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Activities -->
    <div class="card">
        <div class="card-header">📋 गतिविधियाँ</div>
        <?php if (empty($activities)): ?>
            <div class="alert alert-info">ℹ️ पहले <a href="../pages/activities.php">गतिविधियाँ जोड़ें</a>।</div>
        <?php else: ?>
            <?php foreach ($activities as $act): 
                $ea = $existingActivities[$act['id']] ?? null;
            ?>
                <div class="activity-row">
                    <label class="checkbox-item">
                        <input type="checkbox" name="activity_done[<?php echo $act['id']; ?>]" value="1"
                               <?php echo ($ea && $ea['is_done']) ? 'checked' : ''; ?>>
                        <span class="checkbox-label"><?php echo htmlspecialchars($act['name']); ?></span>
                    </label>
                    <div>
                        <select name="conducted_by[<?php echo $act['id']; ?>]" class="form-control">
                            <option value="">-- संचालक चुनें --</option>
                            <?php foreach ($swayamsevaks as $s): ?>
                                <option value="<?php echo $s['id']; ?>"
                                        <?php echo ($ea && $ea['conducted_by'] == $s['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($s['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Custom Message -->
    <div class="card">
        <div class="card-header">💬 विशेष संदेश / टिप्पणी</div>
        <div class="form-group">
            <label for="custom_message">आज का विशेष संदेश (वैकल्पिक)</label>
            <textarea id="custom_message" name="custom_message" class="form-control" rows="3"
                      placeholder="आज की कोई विशेष टिप्पणी या संदेश यहाँ लिखें..."><?php echo htmlspecialchars($existingRecord['custom_message'] ?? ''); ?></textarea>
        </div>
    </div>

    <div class="d-flex gap-1">
        <button type="submit" class="btn btn-primary btn-lg">💾 रिकॉर्ड सहेजें</button>
        <a href="../pages/dashboard.php" class="btn btn-outline">❌ रद्द करें</a>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Prokerala API Auto-fetch Logic
    const btnFetch = document.getElementById('btn-fetch-panchang');
    const dateInput = document.getElementById('record_date');

    const mappers = {
        month: {
            'Chaitra': 'चैत्र', 'Vaisakha': 'वैशाख', 'Vaishakha': 'वैशाख', 'Jyeshtha': 'ज्येष्ठ', 'Jyaistha': 'ज्येष्ठ', 
            'Ashadha': 'आषाढ़', 'Shravana': 'श्रावण', 'Sravana': 'श्रावण', 'Bhadrapada': 'भाद्रपद', 
            'Ashwin': 'आश्विन', 'Asvina': 'आश्विन', 'Kartika': 'कार्तिक', 'Kartik': 'कार्तिक', 'Margashirsha': 'मार्गशीर्ष', 
            'Margasira': 'मार्गशीर्ष', 'Pausha': 'पौष', 'Pausa': 'पौष', 'Magha': 'माघ', 'Phalguna': 'फाल्गुन'
        },
        tithi: {
            'Prathama': 'प्रतिपदा', 'Pratipada': 'प्रतिपदा', 'Dwitiya': 'द्वितीया', 'Tritiya': 'तृतीया', 
            'Chaturthi': 'चतुर्थी', 'Panchami': 'पंचमी', 'Shashthi': 'षष्ठी', 'Sashti': 'षष्ठी', 
            'Saptami': 'सप्तमी', 'Ashtami': 'अष्टमी', 'Navami': 'नवमी', 'Dashami': 'दशमी', 
            'Ekadashi': 'एकादशी', 'Dwadashi': 'द्वादशी', 'Trayodashi': 'त्रयोदशी', 'Chaturdashi': 'चतुर्दशी', 
            'Purnima': 'पूर्णिमा', 'Amavasya': 'अमावस्या'
        },
        paksha: {
            'Shukla': 'शुक्ल पक्ष', 'Krishna': 'कृष्ण पक्ष'
        }
    };

    btnFetch.addEventListener('click', function() {
        const selectedDate = dateInput.value;
        if (!selectedDate) return alert('कृपया पहले तारीख चुनें।');

        btnFetch.innerHTML = '⏳ लोडिंग...';
        btnFetch.disabled = true;

        fetch(`../api/fetch_panchang.php?date=${selectedDate}`)
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    const p = data.panchang;
                    
                    // Map & Auto-select values
                    const monthToSet = p.vikram_month || p.shaka_month;
                    setSelectedValue('hindi_month', mappers.month[monthToSet]);
                    setSelectedValue('paksh', mappers.paksha[p.paksha]);
                    setSelectedValue('tithi', mappers.tithi[p.tithi]);
                    
                    // Pre-fill Samvat if available
                    if (p.vikram_samvat) {
                        const vsField = document.querySelector('select[name="vikram_samvat"]');
                        if (vsField) vsField.value = p.vikram_samvat;
                    }
                    if (p.shaka_samvat) {
                        const ssField = document.querySelector('select[name="shaka_samvat"]');
                        if (ssField) ssField.value = p.shaka_samvat;
                    }
                    
                    alert('✨ पंचांग सफलतापूर्वक भर गया!');
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(err => {
                console.error(err);
                alert('तकनीकी त्रुटि हुई। कृपया दोबारा प्रयास करें।');
            })
            .finally(() => {
                btnFetch.innerHTML = '✨ ऑटो-फिल पंचांग';
                btnFetch.disabled = false;
            });
    });

    function setSelectedValue(fieldName, hindiValue) {
        if (!hindiValue) return;
        const select = document.querySelector(`select[name="${fieldName}"]`);
        if (select) {
            // Find option by text content since the value might be Hindi already
            const options = Array.from(select.options);
            const found = options.find(o => o.value === hindiValue || o.text.trim() === hindiValue);
            if (found) select.value = found.value;
        }
    }

    // 2. Existing auto-calculate logic for Yugabdh/Vikram Samvat on date change
    <?php if (!$existingRecord): ?>
    // ... logic already updated in previous turn ...
    <?php endif; ?>
});
</script>

<?php require_once '../includes/footer.php'; ?>
