<?php
require_once '../includes/auth.php';
require_once '../config/db.php';
requireLogin();

if (!isMukhyashikshak() && !isAdmin()) {
    header('Location: dashboard.php');
    exit;
}

// Auto-create table
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS subhashits (
        id INT AUTO_INCREMENT PRIMARY KEY,
        shakha_id INT NOT NULL,
        sanskrit_text TEXT NOT NULL,
        hindi_meaning TEXT,
        shabdarth JSON,
        subhashit_date DATE NOT NULL,
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        panchang_text VARCHAR(255),
        FOREIGN KEY (shakha_id) REFERENCES shakhas(id) ON DELETE CASCADE,
        FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    // Ensure column exists for older installations
    $pdo->exec("ALTER TABLE subhashits ADD COLUMN panchang_text VARCHAR(255) AFTER subhashit_date");
} catch (PDOException $e) {
    // table already exists or other non-critical error
}

$shakhaId = getCurrentShakhaId();
$success = '';
$error = '';

// Fetch shakha name
$stmtShakha = $pdo->prepare("SELECT name FROM shakhas WHERE id = ?");
$stmtShakha->execute([$shakhaId]);
$shakhaName = $stmtShakha->fetchColumn() ?: 'शाखा';

$subhashitId = $_GET['id'] ?? null;
$sanskrit_text = '';
$hindi_meaning = '';
$shabdarth = [];
$subhashit_date = date('Y-m-d');

if ($subhashitId) {
    $stmt = $pdo->prepare("SELECT * FROM subhashits WHERE id = ? AND shakha_id = ?");
    $stmt->execute([$subhashitId, $shakhaId]);
    $existing = $stmt->fetch();
    if ($existing) {
        $sanskrit_text = $existing['sanskrit_text'];
        $hindi_meaning = $existing['hindi_meaning'];
        $shabdarth = json_decode($existing['shabdarth'], true) ?: [];
        $subhashit_date = $existing['subhashit_date'];
        $panchang_text = $existing['panchang_text'] ?? '';
    } else {
        $subhashitId = null;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $subhashitIdToSave = $_POST['subhashit_id'] ?? null;
    $sanskrit_text = trim($_POST['sanskrit_text'] ?? '');
    $hindi_meaning = trim($_POST['hindi_meaning'] ?? '');
    $subhashit_date = trim($_POST['subhashit_date'] ?? date('Y-m-d'));
    $panchang_text = trim($_POST['panchang_text'] ?? '');
    $createdBy = $_SESSION['user_id'];

    // Build shabdarth array from POST
    $shabdWords = $_POST['shabd'] ?? [];
    $shabdMeanings = $_POST['arth'] ?? [];
    $shabdarth = [];
    for ($i = 0; $i < count($shabdWords); $i++) {
        $word = trim($shabdWords[$i] ?? '');
        $meaning = trim($shabdMeanings[$i] ?? '');
        if ($word !== '' || $meaning !== '') {
            $shabdarth[] = ['shabd' => $word, 'arth' => $meaning];
        }
    }
    $shabdarthJson = json_encode($shabdarth, JSON_UNESCAPED_UNICODE);

    if ($sanskrit_text) {
        try {
            if ($subhashitIdToSave) {
                $stmt = $pdo->prepare("UPDATE subhashits SET sanskrit_text = ?, hindi_meaning = ?, shabdarth = ?, subhashit_date = ?, panchang_text = ? WHERE id = ? AND shakha_id = ?");
                $stmt->execute([$sanskrit_text, $hindi_meaning, $shabdarthJson, $subhashit_date, $panchang_text, $subhashitIdToSave, $shakhaId]);
                $success = "सुभाषित सफलतापूर्वक अपडेट किया गया!";
            } else {
                $stmt = $pdo->prepare("INSERT INTO subhashits (shakha_id, sanskrit_text, hindi_meaning, shabdarth, subhashit_date, panchang_text, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$shakhaId, $sanskrit_text, $hindi_meaning, $shabdarthJson, $subhashit_date, $panchang_text, $createdBy]);
                $success = "सुभाषित सफलतापूर्वक सहेजा गया!";
                $sanskrit_text = '';
                $hindi_meaning = '';
                $shabdarth = [];
                $panchang_text = '';
                $subhashitId = null;
            }
        } catch (Exception $e) {
            $error = "त्रुटि: सुभाषित सहेजने में विफल। " . $e->getMessage();
        }
    } else {
        $error = "संस्कृत सुभाषित आवश्यक है।";
    }
}

// Fetch recent subhashits
$stmt = $pdo->prepare("SELECT * FROM subhashits WHERE shakha_id = ? ORDER BY subhashit_date DESC LIMIT 15");
$stmt->execute([$shakhaId]);
$recentSubhashits = $stmt->fetchAll();

$pageTitle = 'सुभाषित (Subhashit)';
require_once '../includes/header.php';
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

<style>
    /* ===== SUBHASHIT CAPTURE FRAME ===== */
    .sub-capture-container {
        width: 100%;
        max-width: 480px;
        margin: 0 auto;
        background: linear-gradient(180deg, #FFF9E6 0%, #FFF5F5 50%, #F0FFF4 100%);
        color: #3E2723;
        font-family: 'Noto Sans Devanagari', serif;
        padding: 0;
        box-sizing: border-box;
        border-radius: 0;
        overflow: hidden;
        position: relative;
        box-shadow: 0 4px 25px rgba(0, 0, 0, 0.18);
        border: 3px solid #D4A574;
    }

    /* Floral corner decorations */
    .sub-capture-container::before { content: '🌸'; position: absolute; top: 2px; left: 8px; font-size: 24px; z-index: 10; }
    .sub-capture-container::after { content: '🌸'; position: absolute; top: 2px; right: 8px; font-size: 24px; z-index: 10; }

    .sub-floral-bottom::before { content: '🌺'; position: absolute; bottom: 2px; left: 8px; font-size: 24px; z-index: 10; }
    .sub-floral-bottom::after { content: '🌺'; position: absolute; bottom: 2px; right: 8px; font-size: 24px; z-index: 10; }

    /* Floral border band */
    .sub-floral-band {
        background: linear-gradient(90deg, #FCE4EC, #FFF9C4, #E8F5E9, #FCE4EC);
        text-align: center;
        padding: 5px 0;
        font-size: 16px;
        letter-spacing: 6px;
        color: #E91E63;
        border-bottom: 1px solid #F8BBD0;
    }

    /* Date strip */
    .sub-date-strip {
        background: linear-gradient(135deg, #558B2F, #33691E);
        color: #F1F8E9;
        text-align: center;
        padding: 10px 20px;
        font-size: 14px;
        font-weight: 500;
        letter-spacing: 0.5px;
    }

    /* Shakha header */
    .sub-shakha-header {
        background: linear-gradient(135deg, #C2185B 0%, #AD1457 100%);
        color: #FCE4EC;
        padding: 14px 20px;
        text-align: center;
        font-size: 18px;
        font-weight: 700;
        letter-spacing: 1px;
        border-bottom: 2px solid #E91E63;
    }

    /* Main title "सुभाषित" */
    .sub-main-title {
        text-align: center;
        padding: 18px 20px 4px;
        font-size: 30px;
        font-weight: 800;
        color: #880E4F;
        letter-spacing: 3px;
    }

    .sub-subtitle {
        text-align: center;
        font-size: 14px;
        color: #AD1457;
        margin-bottom: 12px;
        font-weight: 500;
    }

    /* Separator ornament */
    .sub-ornament {
        text-align: center;
        color: #E91E63;
        font-size: 16px;
        margin: 4px 0;
        letter-spacing: 10px;
    }

    /* Sanskrit shloka block */
    .sub-sanskrit-block {
        padding: 16px 24px;
        text-align: center;
        position: relative;
    }
    .sub-sanskrit-text {
        font-size: 22px;
        line-height: 1.8;
        color: #1B5E20;
        font-weight: 600;
        white-space: pre-wrap;
        word-break: break-word;
    }

    /* Hindi meaning block */
    .sub-hindi-section {
        margin: 0 20px;
        padding: 14px 18px;
        background: rgba(255, 249, 196, 0.5);
        border-radius: 10px;
        border: 1px dashed #F9A825;
    }
    .sub-section-label {
        font-size: 15px;
        font-weight: 700;
        color: #E65100;
        text-align: center;
        margin-bottom: 8px;
        letter-spacing: 1px;
    }
    .sub-hindi-text {
        font-size: 17px;
        line-height: 1.7;
        color: #3E2723;
        text-align: center;
        white-space: pre-wrap;
        word-break: break-word;
    }

    /* Shabdarth block */
    .sub-shabdarth-section {
        margin: 14px 20px;
        padding: 14px 18px;
        background: rgba(232, 245, 233, 0.5);
        border-radius: 10px;
        border: 1px dashed #81C784;
    }
    .sub-shabdarth-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 2px 16px;
    }
    .sub-shabdarth-row {
        display: flex;
        justify-content: center;
        align-items: baseline;
        gap: 6px;
        padding: 4px 0;
        font-size: 14px;
        color: #2E7D32;
    }
    .sub-shabdarth-word {
        font-weight: 700;
        color: #1B5E20;
    }
    .sub-shabdarth-dash {
        color: #81C784;
    }
    .sub-shabdarth-meaning {
        color: #33691E;
    }

    /* Footer */
    .sub-footer-band {
        margin-top: 16px;
        background: linear-gradient(90deg, #FCE4EC, #FFF9C4, #E8F5E9, #FCE4EC);
        text-align: center;
        padding: 5px 0;
        font-size: 16px;
        letter-spacing: 6px;
        color: #E91E63;
        border-top: 1px solid #F8BBD0;
    }

    /* ===== FORM STYLES ===== */
    .shabdarth-pair {
        display: grid;
        grid-template-columns: 1fr 1fr auto;
        gap: 10px;
        align-items: center;
        margin-bottom: 10px;
    }
    .shabdarth-pair input {
        width: 100%;
        padding: 12px 14px;
        background: rgba(15, 15, 20, 0.6);
        border: 1px solid var(--border-light);
        border-radius: 10px;
        color: var(--text-primary);
        font-size: 1rem;
        font-family: inherit;
        transition: all 0.3s ease;
    }
    .shabdarth-pair input:focus {
        outline: none;
        border-color: #E91E63;
        box-shadow: 0 0 0 3px rgba(233, 30, 99, 0.15);
    }
    .remove-pair-btn {
        background: rgba(239, 83, 80, 0.15);
        border: 1px solid rgba(239, 83, 80, 0.3);
        color: var(--danger);
        width: 38px;
        height: 38px;
        border-radius: 8px;
        cursor: pointer;
        font-size: 1.2rem;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
    }
    .remove-pair-btn:hover {
        background: var(--danger);
        color: white;
    }
    .add-pair-btn {
        background: rgba(233, 30, 99, 0.1);
        border: 1px dashed #E91E63;
        color: #F48FB1;
        padding: 10px;
        border-radius: 10px;
        cursor: pointer;
        text-align: center;
        font-size: 0.95rem;
        transition: all 0.2s;
        margin-top: 5px;
    }
    .add-pair-btn:hover {
        background: rgba(233, 30, 99, 0.2);
        color: #FCE4EC;
    }

    .sub-form-input {
        width: 100%;
        padding: 14px 18px;
        background: rgba(15, 15, 20, 0.6);
        border: 1px solid var(--border-light);
        border-radius: 10px;
        color: var(--text-primary);
        font-size: 1rem;
        font-family: inherit;
        transition: all 0.3s ease;
    }
    .sub-form-input:focus {
        outline: none;
        border-color: #E91E63;
        box-shadow: 0 0 0 3px rgba(233, 30, 99, 0.15);
        background: rgba(15, 15, 20, 0.9);
    }
    textarea.sub-form-input {
        resize: vertical;
        min-height: 80px;
    }

    .sub-form-label {
        display: block;
        margin-bottom: 6px;
        font-weight: 600;
        color: var(--text-secondary);
        font-size: 0.95rem;
    }

    .sub-premium-card {
        background: rgba(34, 34, 46, 0.7);
        backdrop-filter: blur(12px);
        border: 1px solid rgba(233, 30, 99, 0.2);
        border-radius: 16px;
        padding: 28px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
    }
    .sub-premium-card .card-header {
        border-bottom: 1px solid rgba(233, 30, 99, 0.15);
        font-size: 1.3rem;
        padding-bottom: 15px;
        margin-bottom: 25px;
        color: #F48FB1;
    }

    .layout-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }

    @media (max-width: 900px) {
        .layout-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="page-header" style="display: flex; justify-content: space-between; align-items: center;">
    <h1>📜 सुभाषित (Subhashit)</h1>
    <?php if (isAdmin() && !empty($subhashitId)): ?>
        <button class="btn btn-warning" onclick="openShareModal('subhashit', <?php echo $subhashitId; ?>)">🔗 Share across Shakhas</button>
    <?php endif; ?>
</div>

<?php if ($success): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<div class="layout-grid">
    <!-- Form Side -->
    <div>
        <div class="sub-premium-card">
            <div class="card-header">📜 सुभाषित विवरण दर्ज करें</div>
            <form method="POST" action="subhashit.php" id="subhashit-form">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <input type="hidden" name="subhashit_id" value="<?php echo htmlspecialchars($subhashitId ?? ''); ?>">

                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="sub-form-label">दिनांक (Date)</label>
                    <input type="date" name="subhashit_date" id="inp-date" class="sub-form-input"
                        value="<?php echo htmlspecialchars($subhashit_date); ?>">
                </div>

                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="sub-form-label">
                        पंचांग 
                        <button type="button" id="btn-fetch-panchang" class="btn btn-sm" style="background: #FFF3E0; color: #E65100; border: 1px solid #FFCC80; padding: 2px 8px; float: right; cursor: pointer;">✨ पंचांग प्राप्त करें</button>
                    </label>
                    <textarea name="panchang_text" id="inp-panchang" class="sub-form-input" rows="2"
                        placeholder="विक्रम संवत् 2083, चैत्र...&#10;शक संवत् 1948, चैत्र..."><?php echo htmlspecialchars($panchang_text ?? ''); ?></textarea>
                </div>

                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="sub-form-label">संस्कृत सुभाषित *</label>
                    <textarea name="sanskrit_text" id="inp-sanskrit" class="sub-form-input" rows="4" required
                        placeholder="यहाँ संस्कृत सुभाषित लिखें..."><?php echo htmlspecialchars($sanskrit_text ?? ''); ?></textarea>
                </div>

                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="sub-form-label" style="display: flex; justify-content: space-between; align-items: center;">
                        <span>हिंदी अर्थ</span>
                        <button type="button" class="btn btn-sm" onclick="generateAiSubhashit()" id="btn-ai-subhashit" style="background: rgba(233, 30, 99, 0.1); color: #F48FB1; border: 1px dashed #E91E63; padding: 4px 8px; cursor: pointer;">✨ AI से अर्थ व शब्दार्थ निकालें</button>
                    </label>
                    <textarea name="hindi_meaning" id="inp-hindi" class="sub-form-input" rows="3"
                        placeholder="हिंदी में अर्थ लिखें..."><?php echo htmlspecialchars($hindi_meaning ?? ''); ?></textarea>
                </div>

                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="sub-form-label">चुनिंदा शब्दार्थ</label>
                    <div id="shabdarth-container">
                        <?php if (!empty($shabdarth)): ?>
                            <?php foreach ($shabdarth as $idx => $pair): ?>
                                <div class="shabdarth-pair">
                                    <input type="text" name="shabd[]" placeholder="शब्द" value="<?php echo htmlspecialchars($pair['shabd']); ?>">
                                    <input type="text" name="arth[]" placeholder="अर्थ" value="<?php echo htmlspecialchars($pair['arth']); ?>">
                                    <button type="button" class="remove-pair-btn" onclick="this.parentElement.remove(); updatePreview();">✕</button>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="shabdarth-pair">
                                <input type="text" name="shabd[]" placeholder="शब्द">
                                <input type="text" name="arth[]" placeholder="अर्थ">
                                <button type="button" class="remove-pair-btn" onclick="this.parentElement.remove(); updatePreview();">✕</button>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="add-pair-btn" onclick="addShabdarthPair()">+ शब्दार्थ जोड़ें</div>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; margin-bottom: 5px; background: linear-gradient(135deg, #C2185B 0%, #AD1457 100%); box-shadow: 0 4px 15px rgba(194, 24, 91, 0.4); border-radius: 12px; padding: 15px; font-size: 1.1rem;">
                    <?php echo $subhashitId ? '💾 अपडेट करें' : '💾 सहेजें (Save)'; ?>
                </button>
                <?php if ($subhashitId): ?>
                    <a href="../pages/subhashit.php" class="btn btn-outline" style="width: 100%; text-align: center; display: block; margin-top: 8px;">➕ नया सुभाषित बनाएं</a>
                <?php endif; ?>
            </form>
        </div>

        <?php if (!empty($recentSubhashits)): ?>
            <div class="sub-premium-card" style="margin-top: 20px;">
                <div class="card-header">📋 हाल के सुभाषित</div>
                <div style="font-size: 0.85em; padding: 8px 0 12px; color: var(--text-muted);">
                    संपादित/डाउनलोड करने के लिए किसी सुभाषित पर क्लिक करें।
                </div>
                <div style="max-height: 300px; overflow-y: auto;">
                    <?php foreach ($recentSubhashits as $n): ?>
                        <a href="../pages/subhashit.php?id=<?php echo $n['id']; ?>"
                            style="display: block; padding: 12px 15px; border-bottom: 1px solid var(--border-color); text-decoration: none; color: inherit; border-radius: 8px; margin-bottom: 4px; background: <?php echo ($subhashitId == $n['id']) ? 'rgba(141,110,99,0.15)' : 'transparent'; ?>; transition: all 0.2s;"
                            onmouseover="this.style.background='rgba(141,110,99,0.1)'" onmouseout="this.style.background='<?php echo ($subhashitId == $n['id']) ? 'rgba(141,110,99,0.15)' : 'transparent'; ?>'">
                            <strong style="color: var(--text-primary); font-size: 0.95rem;"><?php echo mb_substr(htmlspecialchars($n['sanskrit_text']), 0, 60) . (mb_strlen($n['sanskrit_text']) > 60 ? '...' : ''); ?></strong><br>
                            <small style="color: <?php echo ($subhashitId == $n['id']) ? '#A1887F' : 'var(--text-muted)'; ?>;">
                                <?php echo date('d-m-Y', strtotime($n['subhashit_date'])); ?>
                            </small>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Preview Side -->
    <div>
        <div class="share-actions" style="justify-content: center; margin-bottom: 15px; gap: 16px; display: flex;">
            <button id="btn-download" class="btn btn-success" style="background: linear-gradient(135deg, #558B2F, #33691E); box-shadow: 0 4px 15px rgba(85,139,47,0.3);">⬇️ डाउनलोड (JPG)</button>
            <button id="btn-share" class="btn btn-whatsapp">📱 व्हाट्सएप शेयर</button>
        </div>

        <div style="overflow-x: auto; padding-bottom: 40px; text-align: center;">
            <div id="capture-area" class="sub-capture-container">

                <div class="sub-date-strip">
                    <div id="prev-date" style="font-size: 15px;"><?php echo date('d-m-Y'); ?></div>
                    <div id="prev-panchang" style="font-size: 12px; margin-top: 4px; font-weight: 400; color: #DCEDC8;"></div>
                </div>

                <div class="sub-shakha-header">
                    🚩 <?php echo htmlspecialchars($shakhaName); ?> 🚩
                </div>

                <div class="sub-floral-band">🌼 ❀ 🌸 ❀ 🌼 ❀ 🌸</div>

                <div class="sub-main-title">सुभाषित</div>
                <div class="sub-subtitle">( अभ्यास हेतु )</div>

                <div class="sub-ornament">─ ✦ ─</div>

                <div class="sub-sanskrit-block">
                    <div id="prev-sanskrit" class="sub-sanskrit-text">संस्कृत सुभाषित यहाँ दिखेगा...</div>
                </div>

                <div class="sub-ornament">─ ✦ ─</div>

                <div id="prev-hindi-section" class="sub-hindi-section" style="display: none;">
                    <div class="sub-section-label">— हिंदी अर्थ —</div>
                    <div id="prev-hindi" class="sub-hindi-text"></div>
                </div>

                <div id="prev-shabdarth-section" class="sub-shabdarth-section" style="display: none;">
                    <div class="sub-section-label">— चुनिंदा शब्दार्थ —</div>
                    <div id="prev-shabdarth" class="sub-shabdarth-grid"></div>
                </div>

                <div class="sub-floral-bottom" style="position: relative;">
                    <div class="sub-footer-band">🌼 ❀ 🌸 ❀ 🌼 ❀ 🌸</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const hindiMonths = ['जनवरी', 'फ़रवरी', 'मार्च', 'अप्रैल', 'मई', 'जून', 'जुलाई', 'अगस्त', 'सितंबर', 'अक्टूबर', 'नवंबर', 'दिसंबर'];
    const hindiDays = ['रविवार', 'सोमवार', 'मंगलवार', 'बुधवार', 'गुरुवार', 'शुक्रवार', 'शनिवार'];

    function formatHindiDate(dateString) {
        if (!dateString) return '';
        const d = new Date(dateString);
        if (isNaN(d)) return dateString;
        const dayName = hindiDays[d.getDay()];
        const day = d.getDate();
        const monthName = hindiMonths[d.getMonth()];
        const year = d.getFullYear();
        return `${dayName}, ${day} ${monthName} ${year}`;
    }

    function addShabdarthPair(shabd = '', arth = '') {
        const container = document.getElementById('shabdarth-container');
        
        // Remove empty placeholder if it exists and is empty
        const pairs = container.querySelectorAll('.shabdarth-pair');
        if (pairs.length === 1) {
            const inputs = pairs[0].querySelectorAll('input');
            if (!inputs[0].value && !inputs[1].value) {
                pairs[0].remove();
            }
        }

        const pair = document.createElement('div');
        pair.className = 'shabdarth-pair';
        pair.innerHTML = `
            <input type="text" name="shabd[]" placeholder="शब्द" oninput="updatePreview()" value="${shabd}">
            <input type="text" name="arth[]" placeholder="अर्थ" oninput="updatePreview()" value="${arth}">
            <button type="button" class="remove-pair-btn" onclick="this.parentElement.remove(); updatePreview();">✕</button>
        `;
        container.appendChild(pair);
        updatePreview();
    }

    function updatePreview() {
        const dateInput = document.getElementById('inp-date').value;
        const sanskrit = document.getElementById('inp-sanskrit').value;
        const hindi = document.getElementById('inp-hindi').value;
        const panchangText = document.getElementById('inp-panchang').value;

        document.getElementById('prev-date').innerText = formatHindiDate(dateInput) || '(दिनांक)';
        
        const panchangEl = document.getElementById('prev-panchang');
        if (panchangText.trim()) {
            panchangEl.innerHTML = panchangText.replace(/\n/g, '<br>');
            panchangEl.style.display = 'block';
        } else {
            panchangEl.style.display = 'none';
        }
        
        const sanskritEl = document.getElementById('prev-sanskrit');
        sanskritEl.innerHTML = (sanskrit || 'संस्कृत सुभाषित यहाँ दिखेगा...').replace(/\n/g, '<br>');

        // Auto-size sanskrit text
        let fontSize = 22;
        sanskritEl.style.fontSize = fontSize + 'px';
        // Rough check: if text is long, reduce font
        if (sanskrit.length > 200) fontSize = 17;
        else if (sanskrit.length > 120) fontSize = 19;
        sanskritEl.style.fontSize = fontSize + 'px';

        // Hindi meaning
        const hindiSection = document.getElementById('prev-hindi-section');
        const hindiEl = document.getElementById('prev-hindi');
        if (hindi.trim()) {
            hindiSection.style.display = 'block';
            hindiEl.innerHTML = hindi.replace(/\n/g, '<br>');
        } else {
            hindiSection.style.display = 'none';
        }

        // Shabdarth
        const shabdInputs = document.querySelectorAll('input[name="shabd[]"]');
        const arthInputs = document.querySelectorAll('input[name="arth[]"]');
        const shabdarthSection = document.getElementById('prev-shabdarth-section');
        const shabdarthEl = document.getElementById('prev-shabdarth');

        let hasAny = false;
        let html = '';
        for (let i = 0; i < shabdInputs.length; i++) {
            const w = shabdInputs[i].value.trim();
            const m = arthInputs[i] ? arthInputs[i].value.trim() : '';
            if (w || m) {
                hasAny = true;
                html += `<div class="sub-shabdarth-row">
                    <span class="sub-shabdarth-word">${w || ''}</span>
                    <span class="sub-shabdarth-dash">—</span>
                    <span class="sub-shabdarth-meaning">${m || ''}</span>
                </div>`;
            }
        }
        if (hasAny) {
            shabdarthSection.style.display = 'block';
            shabdarthEl.innerHTML = html;
        } else {
            shabdarthSection.style.display = 'none';
        }
    }

    // Bind events
    document.getElementById('inp-date').addEventListener('input', updatePreview);
    document.getElementById('inp-panchang').addEventListener('input', updatePreview);
    document.getElementById('inp-sanskrit').addEventListener('input', updatePreview);
    document.getElementById('inp-hindi').addEventListener('input', updatePreview);

    // Bind existing shabdarth inputs
    document.querySelectorAll('input[name="shabd[]"], input[name="arth[]"]').forEach(el => {
        el.addEventListener('input', updatePreview);
    });

    // Panchang API Fetch Logic
    document.getElementById('btn-fetch-panchang').addEventListener('click', function() {
        const selectedDate = document.getElementById('inp-date').value;
        if (!selectedDate) return alert('कृपया पहले तारीख चुनें।');

        const btnFetch = this;
        btnFetch.innerHTML = '⏳ लोडिंग...';
        btnFetch.disabled = true;

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

        fetch(`../api/fetch_panchang.php?date=${selectedDate}`)
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    const p = data.panchang;
                    
                    const pPaksha = mappers.paksha[p.paksha] || p.paksha;
                    const pTithi = mappers.tithi[p.tithi] || p.tithi;
                    
                    const pMonthVikram = mappers.month[p.vikram_month] || p.vikram_month || '';
                    const pMonthShaka = mappers.month[p.shaka_month] || p.shaka_month || '';
                    
                    let lines = [];

                    // Line 1: Vikram Samvat
                    if (p.vikram_samvat) {
                        let vikramLine = `विक्रम संवत् ${p.vikram_samvat}`;
                        let details = [];
                        if(pMonthVikram) details.push(pMonthVikram);
                        if(pPaksha) details.push(pPaksha);
                        if(pTithi) details.push(pTithi);
                        if (details.length > 0) vikramLine += ` (${details.join(' ')})`;
                        lines.push(vikramLine);
                    }

                    // Line 2: Shaka Samvat
                    if (p.shaka_samvat) {
                        let shakaLine = `शक संवत् ${p.shaka_samvat}`;
                        let details = [];
                        if(pMonthShaka) details.push(pMonthShaka);
                        if(pPaksha) details.push(pPaksha);
                        if(pTithi) details.push(pTithi);
                        if (details.length > 0) shakaLine += ` (${details.join(' ')})`;
                        lines.push(shakaLine);
                    }

                    // Fallback if neither exists
                    if (lines.length === 0) {
                        lines.push(`${pMonthVikram} ${pPaksha} ${pTithi}`.trim());
                    }
                    
                    document.getElementById('inp-panchang').value = lines.join('\n');
                    updatePreview();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(err => {
                console.error(err);
                alert('तकनीकी त्रुटि हुई।');
            })
            .finally(() => {
                btnFetch.innerHTML = '✨ पंचांग प्राप्त करें';
                btnFetch.disabled = false;
            });
    });

    // Initial call
    updatePreview();

    async function generateAiSubhashit() {
        const sanskrit = document.getElementById('inp-sanskrit').value.trim();
        if (!sanskrit) {
            alert("कृपया पहले संस्कृत सुभाषित दर्ज करें।");
            return;
        }

        const btn = document.getElementById('btn-ai-subhashit');
        const originalText = btn.innerHTML;
        btn.innerHTML = '⏳ AI सोच रहा है...';
        btn.disabled = true;

        try {
            const response = await fetch('../api/ai_content.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'subhashit_meaning', sanskrit: sanskrit })
            });
            
            const data = await response.json();
            if (data.success) {
                document.getElementById('inp-hindi').value = data.result.hindi_meaning || '';
                
                // Clear existing shabdarth
                document.getElementById('shabdarth-container').innerHTML = '';
                
                // Add new ones
                if (data.result.shabdarth && data.result.shabdarth.length > 0) {
                    data.result.shabdarth.forEach(item => {
                        addShabdarthPair(item.shabd, item.arth);
                    });
                } else {
                    addShabdarthPair(); // add empty
                }
                
                updatePreview();
            } else {
                alert("त्रुटि: " + (data.message || "Unknown error"));
            }
        } catch (e) {
            console.error(e);
            alert("सर्वर से जुड़ने में त्रुटि।");
        }

        btn.innerHTML = originalText;
        btn.disabled = false;
    }

    // Generate High-Res Image
    async function generateImage() {
        const el = document.getElementById('capture-area');
        const canvas = await html2canvas(el, {
            scale: 2,
            backgroundColor: '#FFF9E6',
            useCORS: true,
            logging: false
        });
        return canvas;
    }

    // Download Button
    document.getElementById('btn-download').addEventListener('click', async () => {
        const btn = document.getElementById('btn-download');
        const originalText = btn.innerHTML;
        btn.innerHTML = '⏳ कृपया प्रतीक्षा करें...';
        btn.disabled = true;

        try {
            const canvas = await generateImage();
            const dStr = document.getElementById('inp-date').value || 'date';

            if (window.FlutterShareChannel) {
                const b64 = canvas.toDataURL('image/jpeg', 0.95);
                window.FlutterShareChannel.postMessage(JSON.stringify({
                    image: b64,
                    text: 'सुभाषित',
                    filename: `subhashit_${dStr}.jpg`
                }));
                btn.innerHTML = originalText;
                btn.disabled = false;
                return;
            }

            const a = document.createElement('a');
            a.href = canvas.toDataURL('image/jpeg', 0.95);
            a.download = `subhashit_${dStr}.jpg`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        } catch (e) {
            console.error(e);
            alert('स्नैपशॉट बनाने में तकनीकी त्रुटि हुई।');
        }

        btn.innerHTML = originalText;
        btn.disabled = false;
    });

    // WhatsApp Share Button
    document.getElementById('btn-share').addEventListener('click', async () => {
        const btn = document.getElementById('btn-share');
        const originalText = btn.innerHTML;
        btn.innerHTML = '⏳ तैयार हो रहा है...';
        btn.disabled = true;

        try {
            const canvas = await generateImage();
            const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/jpeg', 0.95));

            const file = new File([blob], 'subhashit.jpg', { type: 'image/jpeg' });
            const textStr = `📜 आज का सुभाषित — अभ्यास हेतु`;

            if (window.FlutterShareChannel) {
                const dStr = document.getElementById('inp-date').value || 'date';
                const b64 = canvas.toDataURL('image/jpeg', 0.95);
                window.FlutterShareChannel.postMessage(JSON.stringify({
                    image: b64,
                    text: textStr,
                    filename: `subhashit_${dStr}.jpg`
                }));
                btn.innerHTML = originalText;
                btn.disabled = false;
                return;
            }

            if (navigator.share && navigator.canShare && navigator.canShare({ files: [file] })) {
                await navigator.share({
                    title: 'सुभाषित',
                    text: textStr,
                    files: [file]
                });
            } else {
                alert('आपका ब्राउज़र सीधे इमेज शेयरिंग सपोर्ट नहीं करता। इमेज डाउनलोड हो रही है...');
                const a = document.createElement('a');
                a.href = URL.createObjectURL(blob);
                const dStr = document.getElementById('inp-date').value || 'date';
                a.download = `subhashit_${dStr}.jpg`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.open('https://wa.me/?text=' + encodeURIComponent(textStr), '_blank');
            }
        } catch (e) {
            if (e.name !== 'AbortError') {
                console.error(e);
                alert('शेयर करने में तकनीकी त्रुटि हुई।');
            }
        }

        btn.innerHTML = originalText;
        btn.disabled = false;
    });
</script>

<?php require_once '../includes/share_modal.php'; ?>
<?php require_once '../includes/footer.php'; ?>
