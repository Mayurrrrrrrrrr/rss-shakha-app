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
} catch (PDOException $e) {}

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
$panchang_text = '';

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
                $sanskrit_text = ''; $hindi_meaning = ''; $shabdarth = []; $panchang_text = ''; $subhashitId = null;
            }
        } catch (Exception $e) {
            $error = "त्रुटि: सुभाषित सहेजने में विफल। " . $e->getMessage();
        }
    } else {
        $error = "संस्कृत सुभाषित आवश्यक है।";
    }
}

$stmt = $pdo->prepare("SELECT * FROM subhashits WHERE shakha_id = ? ORDER BY subhashit_date DESC LIMIT 15");
$stmt->execute([$shakhaId]);
$recentSubhashits = $stmt->fetchAll();

$pageTitle = 'सुभाषित (Subhashit)';
require_once '../includes/header.php';
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Noto+Serif+Devanagari:wght@400;700;800;900&display=swap" rel="stylesheet">

<style>
    /* PREMIUM DESIGN STYLES */
    .sub-capture-container {
        background: linear-gradient(135deg, #aa771c, #fcf6ba, #aa771c);
        padding: 12px; border-radius: 4px; box-shadow: 0 20px 60px rgba(0,0,0,0.2);
        width: 100%; max-width: 550px; margin: 0 auto; position: relative; font-family: 'Noto Serif Devanagari', serif;
    }
    .sub-capture-inner {
        background: #fff9e3; background-image: radial-gradient(circle at 50% 10%, #fffdf5 0%, #fff9e3 70%);
        border: 2px solid #5a4408; padding: 30px 20px; position: relative; overflow: hidden;
        box-sizing: border-box; display: flex; flex-direction: column; align-items: center;
    }
    .corner-svg { position: absolute; width: 80px; height: 80px; fill: #8b6b0d; z-index: 5; pointer-events: none; }
    .tl { top: 0; left: 0; }
    .tr { top: 0; right: 0; transform: scaleX(-1); }
    .bl { bottom: 0; left: 0; transform: scaleY(-1); }
    .br { bottom: 0; right: 0; transform: scale(-1); }
    .flag-wrap { position: absolute; top: 15px; width: 40px; height: 60px; z-index: 10; }
    .flag-l { left: 15px; } .flag-r { right: 15px; transform: scaleX(-1); }
    .pole { width: 3px; height: 100%; background: linear-gradient(to right, #444, #888, #333); position: absolute; left: 0; }
    .dhwaj { position: absolute; left: 3px; width: 35px; height: 25px; fill: #ff8c00; transform-origin: left center; }
    .om-text { font-size: 55px; color: #cc0000; font-weight: 900; letter-spacing: 2px; margin-bottom: 5px; z-index: 2; line-height: 1; }
    .meta-info { display: flex; flex-direction: column; align-items: center; text-align: center; margin: 5px 0 10px; z-index: 2; }
    .meta-date-panchang { font-size: 13px; font-weight: 700; color: #5a4408; line-height: 1.4; }
    .meta-shakha { font-size: 15px; font-weight: 900; color: #880E4F; margin-top: 4px; }
    .title-container { display: flex; align-items: center; justify-content: center; gap: 15px; margin: 10px 0; z-index: 2; }
    .wing { width: 35px; height: 35px; fill: #cc0000; }
    .main-title { font-size: clamp(35px, 8vw, 55px); font-weight: 900; color: #002266; margin: 0; text-shadow: 2px 2px 0 #fff, -1px -1px 0 #fff, 1px -1px 0 #fff, -1px 1px 0 #fff, 2px 4px 5px rgba(0,0,0,0.1); }
    .divider-svg { width: 80%; height: 20px; margin: 5px 0; z-index: 2; }
    .shlok-text { text-align: center; font-size: clamp(20px, 5.5vw, 30px); line-height: 1.4; font-weight: 800; color: #000000; margin: 15px 0; z-index: 2; white-space: pre-wrap; word-break: break-word; }
    .arth-box { display: flex; align-items: center; justify-content: center; gap: 15px; width: 95%; margin-top: 20px; z-index: 2; flex-wrap: wrap; }
    .arth-label { background: #610000; color: #ffea00; padding: 6px 20px; border-radius: 20px 4px 20px 4px; font-weight: 900; font-size: 20px; border: 1px solid #910000; }
    .arth-text { font-size: clamp(18px, 4.5vw, 22px); color: #002266; font-weight: 700; line-height: 1.5; text-align: center; flex: 1; min-width: 200px; white-space: pre-wrap; word-break: break-word; }
    .shabdarth-box { width: 90%; margin-top: 20px; z-index: 2; display: flex; flex-direction: column; align-items: center; }
    .shabdarth-title { font-size: 16px; font-weight: 800; color: #8b6b0d; margin-bottom: 8px; border-bottom: 1px dashed #8b6b0d; padding-bottom: 2px; }
    .shabdarth-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 4px 20px; width: 100%; text-align: center; }
    .shabd-row { font-size: 15px; color: #002266; display: flex; justify-content: center; gap: 5px; }
    .shabd-word { font-weight: 900; color: #610000; }
    .bottom-ornament { margin-top: 20px; width: 150px; height: 15px; fill: #bf953f; }

    /* FORM STYLES */
    .shabdarth-pair { display: grid; grid-template-columns: 1fr 1fr auto; gap: 10px; align-items: center; margin-bottom: 10px; }
    .shabdarth-pair input, .sub-form-input { width: 100%; padding: 12px 14px; background: rgba(15, 15, 20, 0.6); border: 1px solid var(--border-light); border-radius: 10px; color: var(--text-primary); font-size: 1rem; transition: all 0.3s ease; }
    .shabdarth-pair input:focus, .sub-form-input:focus { outline: none; border-color: #E91E63; box-shadow: 0 0 0 3px rgba(233, 30, 99, 0.15); background: rgba(15, 15, 20, 0.9); }
    .remove-pair-btn { background: rgba(239, 83, 80, 0.15); border: 1px solid rgba(239, 83, 80, 0.3); color: var(--danger); width: 38px; height: 38px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; justify-content: center; }
    .add-pair-btn { background: rgba(233, 30, 99, 0.1); border: 1px dashed #E91E63; color: #F48FB1; padding: 10px; border-radius: 10px; cursor: pointer; text-align: center; margin-top: 5px; }
    .sub-form-label { display: block; margin-bottom: 6px; font-weight: 600; color: var(--text-secondary); font-size: 0.95rem; }
    .sub-premium-card { background: rgba(34, 34, 46, 0.7); backdrop-filter: blur(12px); border: 1px solid rgba(233, 30, 99, 0.2); border-radius: 16px; padding: 28px; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3); }
    .sub-premium-card .card-header { border-bottom: 1px solid rgba(233, 30, 99, 0.15); font-size: 1.3rem; padding-bottom: 15px; margin-bottom: 25px; color: #F48FB1; }
    .layout-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    @media (max-width: 900px) { .layout-grid { grid-template-columns: 1fr; } }
</style>

<div class="page-header" style="display: flex; justify-content: space-between; align-items: center;">
    <h1>📜 सुभाषित (Subhashit)</h1>
    <?php if (isAdmin() && !empty($subhashitId)): ?>
        <button class="btn btn-warning" onclick="openShareModal('subhashit', <?php echo $subhashitId; ?>)">🔗 Share across Shakhas</button>
    <?php endif; ?>
</div>

<?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>

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
                    <input type="date" name="subhashit_date" id="inp-date" class="sub-form-input" value="<?php echo htmlspecialchars($subhashit_date); ?>">
                </div>

                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="sub-form-label">पंचांग <button type="button" id="btn-fetch-panchang" class="btn btn-sm" style="background: #FFF3E0; color: #E65100; border: 1px solid #FFCC80; padding: 2px 8px; float: right; cursor: pointer;">✨ पंचांग प्राप्त करें</button></label>
                    <textarea name="panchang_text" id="inp-panchang" class="sub-form-input" rows="2"><?php echo htmlspecialchars($panchang_text ?? ''); ?></textarea>
                </div>

                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="sub-form-label">संस्कृत सुभाषित *</label>
                    <textarea name="sanskrit_text" id="inp-sanskrit" class="sub-form-input" rows="4" required><?php echo htmlspecialchars($sanskrit_text ?? ''); ?></textarea>
                </div>

                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="sub-form-label" style="display: flex; justify-content: space-between; align-items: center;">
                        <span>हिंदी अर्थ</span>
                        <button type="button" class="btn btn-sm" onclick="generateAiSubhashit()" id="btn-ai-subhashit" style="background: rgba(233, 30, 99, 0.1); color: #F48FB1; border: 1px dashed #E91E63; padding: 4px 8px; cursor: pointer;">✨ AI से अर्थ व शब्दार्थ निकालें</button>
                    </label>
                    <textarea name="hindi_meaning" id="inp-hindi" class="sub-form-input" rows="3"><?php echo htmlspecialchars($hindi_meaning ?? ''); ?></textarea>
                </div>

                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="sub-form-label">चुनिंदा शब्दार्थ</label>
                    <div id="shabdarth-container">
                        <?php if (!empty($shabdarth)): foreach ($shabdarth as $pair): ?>
                            <div class="shabdarth-pair">
                                <input type="text" name="shabd[]" placeholder="शब्द" value="<?php echo htmlspecialchars($pair['shabd']); ?>">
                                <input type="text" name="arth[]" placeholder="अर्थ" value="<?php echo htmlspecialchars($pair['arth']); ?>">
                                <button type="button" class="remove-pair-btn" onclick="this.parentElement.remove(); updatePreview();">✕</button>
                            </div>
                        <?php endforeach; else: ?>
                            <div class="shabdarth-pair">
                                <input type="text" name="shabd[]" placeholder="शब्द"><input type="text" name="arth[]" placeholder="अर्थ">
                                <button type="button" class="remove-pair-btn" onclick="this.parentElement.remove(); updatePreview();">✕</button>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="add-pair-btn" onclick="addShabdarthPair()">+ शब्दार्थ जोड़ें</div>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; border-radius: 12px; padding: 15px;"><?php echo $subhashitId ? '💾 अपडेट करें' : '💾 सहेजें (Save)'; ?></button>
                <?php if ($subhashitId): ?><a href="../pages/subhashit.php" class="btn btn-outline" style="width: 100%; text-align: center; display: block; margin-top: 8px;">➕ नया सुभाषित बनाएं</a><?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Preview Side -->
    <div>
        <div class="share-actions" style="justify-content: center; margin-bottom: 15px; gap: 16px; display: flex;">
            <button id="btn-download" class="btn btn-success">⬇️ डाउनलोड (JPG)</button>
            <button id="btn-share" class="btn btn-whatsapp">📱 व्हाट्सएप शेयर</button>
        </div>

        <div style="overflow-x: auto; padding-bottom: 40px; text-align: center; display: flex; justify-content: center;">
            <div id="preview-scaler" style="transform-origin: top center; transform: scale(min(1, calc((100vw - 40px) / 550))); width: 550px;">
                <div id="capture-area" class="sub-capture-container" style="transform: none !important; box-shadow: none;">
                    <div class="sub-capture-inner">
                        <svg class="corner-svg tl" viewBox="0 0 100 100"><path d="M0,0 Q50,0 50,50 Q0,50 0,0 M10,10 Q40,10 40,40 Q10,40 10,10 M0,20 Q20,20 20,40 M20,0 Q20,20 40,20" /></svg>
                        <svg class="corner-svg tr" viewBox="0 0 100 100"><path d="M0,0 Q50,0 50,50 Q0,50 0,0 M10,10 Q40,10 40,40 Q10,40 10,10 M0,20 Q20,20 20,40 M20,0 Q20,20 40,20" /></svg>
                        <svg class="corner-svg bl" viewBox="0 0 100 100"><path d="M0,0 Q50,0 50,50 Q0,50 0,0 M10,10 Q40,10 40,40 Q10,40 10,10 M0,20 Q20,20 20,40 M20,0 Q20,20 40,20" /></svg>
                        <svg class="corner-svg br" viewBox="0 0 100 100"><path d="M0,0 Q50,0 50,50 Q0,50 0,0 M10,10 Q40,10 40,40 Q10,40 10,10 M0,20 Q20,20 20,40 M20,0 Q20,20 40,20" /></svg>

                        <div class="flag-wrap flag-l"><div class="pole"></div><svg class="dhwaj" viewBox="0 0 100 80"><path d="M0,0 L100,25 L20,40 L100,55 L0,80 Z" /></svg></div>
                        <div class="flag-wrap flag-r"><div class="pole"></div><svg class="dhwaj" viewBox="0 0 100 80"><path d="M0,0 L100,25 L20,40 L100,55 L0,80 Z" /></svg></div>

                        <div class="om-text">॥ ॐ ॥</div>

                        <div class="meta-info">
                            <div class="meta-date-panchang"><span id="prev-date"></span><br><span id="prev-panchang"></span></div>
                            <div class="meta-shakha">🚩 <span id="prev-shakha-name"><?php echo htmlspecialchars($shakhaName); ?></span> 🚩</div>
                        </div>

                        <svg class="divider-svg" viewBox="0 0 400 20"><path d="M50,10 L350,10" fill="none" stroke="#bf953f" stroke-width="1.5" /><circle cx="200" cy="10" r="4" fill="#cc0000" /></svg>

                        <div class="title-container">
                            <svg class="wing" viewBox="0 0 100 100"><path d="M100,50 Q70,20 40,50 Q70,80 100,50 M80,50 Q60,35 40,50 Q60,65 80,50 M40,50 Q20,40 0,50 Q20,60 40,50" /></svg>
                            <h1 class="main-title">सुभाषित</h1>
                            <svg class="wing" style="transform: scaleX(-1)" viewBox="0 0 100 100"><path d="M100,50 Q70,20 40,50 Q70,80 100,50 M80,50 Q60,35 40,50 Q60,65 80,50 M40,50 Q20,40 0,50 Q20,60 40,50" /></svg>
                        </div>

                        <svg class="divider-svg" viewBox="0 0 400 20"><path d="M100,10 L300,10" fill="none" stroke="#cc0000" stroke-width="1" /><path d="M200,2 L200,18" stroke="#bf953f" stroke-width="2" /></svg>

                        <div id="prev-sanskrit" class="shlok-text">संस्कृत सुभाषित यहाँ दिखेगा...</div>

                        <svg class="divider-svg" viewBox="0 0 400 20"><path d="M50,10 L350,10" fill="none" stroke="#bf953f" stroke-width="1.5" stroke-dasharray="5 5" /><circle cx="200" cy="10" r="5" fill="#bf953f" /></svg>

                        <div id="prev-hindi-section" class="arth-box" style="display: none;">
                            <div class="arth-label">अर्थ :-</div>
                            <div id="prev-hindi" class="arth-text"></div>
                        </div>

                        <div id="prev-shabdarth-section" class="shabdarth-box" style="display: none;">
                            <div class="shabdarth-title">— शब्दार्थ —</div>
                            <div id="prev-shabdarth" class="shabdarth-grid"></div>
                        </div>

                        <svg class="bottom-ornament" viewBox="0 0 200 20"><path d="M0,10 Q100,0 200,10" fill="none" stroke="currentColor" stroke-width="2" /><circle cx="100" cy="10" r="4" fill="#cc0000" /></svg>
                    </div>
                </div>
            </div>
        </div>
    </div>
<hr style="margin: 40px 0; border: 0; border-top: 1px solid rgba(233, 30, 99, 0.1);">

<!-- Recent List Section -->
<div class="card">
    <div class="card-header">📋 हाल के सुभाषित (Recent Subhashits)</div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>दिनांक</th>
                    <th>सुभाषित</th>
                    <th>क्रियाएं (Actions)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentSubhashits as $r): ?>
                    <tr>
                        <td><?php echo date('d-m-Y', strtotime($r['subhashit_date'])); ?></td>
                        <td>
                            <div style="max-width: 300px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-weight: 600;">
                                <?php echo htmlspecialchars($r['sanskrit_text']); ?>
                            </div>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="subhashit.php?id=<?php echo $r['id']; ?>" class="btn btn-sm btn-outline">✏️ Edit</a>
                                <button class="btn btn-sm btn-success" onclick="quickAction('download', <?php echo $r['id']; ?>)">⬇️ JPG</button>
                                <button class="btn btn-sm btn-whatsapp" onclick="quickAction('share', <?php echo $r['id']; ?>)">📱 Share</button>
                                <?php if (isAdmin()): ?>
                                    <button class="btn btn-sm btn-warning" onclick="openShareModal('subhashit', <?php echo $r['id']; ?>)">🔗 Global</button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($recentSubhashits)): ?>
                    <tr><td colspan="3" style="text-align: center; color: var(--text-muted);">कोई सुभाषित नहीं मिला।</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    const hindiMonths = ['जनवरी', 'फ़रवरी', 'मार्च', 'अप्रैल', 'मई', 'जून', 'जुलाई', 'अगस्त', 'सितंबर', 'अक्टूबर', 'नवंबर', 'दिसंबर'];
    const hindiDays = ['रविवार', 'सोमवार', 'मंगलवार', 'बुधवार', 'गुरुवार', 'शुक्रवार', 'शनिवार'];

    function formatHindiDate(dateString) {
        if (!dateString) return '';
        const d = new Date(dateString); if (isNaN(d)) return dateString;
        return `${hindiDays[d.getDay()]}, ${d.getDate()} ${hindiMonths[d.getMonth()]} ${d.getFullYear()}`;
    }

    function addShabdarthPair(shabd = '', arth = '') {
        const container = document.getElementById('shabdarth-container');
        const pair = document.createElement('div');
        pair.className = 'shabdarth-pair';
        pair.innerHTML = `<input type="text" name="shabd[]" placeholder="शब्द" oninput="updatePreview()" value="${shabd}">
            <input type="text" name="arth[]" placeholder="अर्थ" oninput="updatePreview()" value="${arth}">
            <button type="button" class="remove-pair-btn" onclick="this.parentElement.remove(); updatePreview();">✕</button>`;
        container.appendChild(pair); updatePreview();
    }

    function updatePreview() {
        const dateVal = document.getElementById('inp-date').value;
        const sanskrit = document.getElementById('inp-sanskrit').value;
        const hindi = document.getElementById('inp-hindi').value;
        const panchang = document.getElementById('inp-panchang').value;

        document.getElementById('prev-date').innerText = formatHindiDate(dateVal) || '(दिनांक)';
        const pEl = document.getElementById('prev-panchang');
        pEl.innerHTML = panchang.trim() ? panchang.replace(/\n/g, '<br>') : '';
        pEl.style.display = panchang.trim() ? 'block' : 'none';
        
        const sEl = document.getElementById('prev-sanskrit');
        sEl.innerHTML = (sanskrit || 'संस्कृत सुभाषित यहाँ दिखेगा...').replace(/\n/g, '<br>');

        const hSect = document.getElementById('prev-hindi-section');
        const hEl = document.getElementById('prev-hindi');
        if (hindi.trim()) { hSect.style.display = 'flex'; hEl.innerHTML = hindi.replace(/\n/g, '<br>'); }
        else { hSect.style.display = 'none'; }

        const sSect = document.getElementById('prev-shabdarth-section');
        const sGrid = document.getElementById('prev-shabdarth');
        const sIns = document.querySelectorAll('input[name="shabd[]"]');
        const aIns = document.querySelectorAll('input[name="arth[]"]');
        let sHtml = '';
        for (let i = 0; i < sIns.length; i++) {
            const w = sIns[i].value.trim(); const m = aIns[i].value.trim();
            if (w || m) sHtml += `<div class="shabd-row"><span class="shabd-word">${w}</span><span>—</span><span style="color:#002266; font-weight:400;">${m}</span></div>`;
        }
        if (sHtml) { sSect.style.display = 'flex'; sGrid.innerHTML = sHtml; }
        else { sSect.style.display = 'none'; }
    }

    document.getElementById('inp-date').addEventListener('input', updatePreview);
    document.getElementById('inp-panchang').addEventListener('input', updatePreview);
    document.getElementById('inp-sanskrit').addEventListener('input', updatePreview);
    document.getElementById('inp-hindi').addEventListener('input', updatePreview);
    document.getElementById('btn-fetch-panchang').addEventListener('click', function() {
        const d = document.getElementById('inp-date').value; if (!d) return alert('तारीख चुनें');
        this.innerText = '⏳...';
        fetch(`../api/fetch_panchang.php?date=${d}`).then(r => r.json()).then(data => {
            if (data.status === 'success') {
                const p = data.panchang;
                let l = [];
                if (p.vikram_samvat) l.push(`विक्रम संवत् ${p.vikram_samvat} (${p.vikram_month} ${p.paksha} ${p.tithi})`);
                if (p.shaka_samvat) l.push(`शक संवत् ${p.shaka_samvat} (${p.shaka_month} ${p.paksha} ${p.tithi})`);
                document.getElementById('inp-panchang').value = l.join('\n'); updatePreview();
            }
        }).finally(() => this.innerText = '✨ पंचांग प्राप्त करें');
    });

    async function generateAiSubhashit() {
        const s = document.getElementById('inp-sanskrit').value.trim(); if (!s) return alert("श्लोक लिखें");
        const b = document.getElementById('btn-ai-subhashit'); b.innerText = '⏳...'; b.disabled = true;
        try {
            const res = await fetch('../api/ai_content.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'subhashit_meaning', sanskrit: s }) });
            const data = await res.json();
            if (data.success) {
                document.getElementById('inp-hindi').value = data.result.hindi_meaning || '';
                document.getElementById('shabdarth-container').innerHTML = '';
                if (data.result.shabdarth) data.result.shabdarth.forEach(i => addShabdarthPair(i.shabd, i.arth));
                updatePreview();
            }
        } catch(e) {}
        b.innerText = '✨ AI से अर्थ व शब्दार्थ निकालें'; b.disabled = false;
    }

    async function generateImage() {
        if (document.fonts) { await document.fonts.ready; }
        const el = document.getElementById('capture-area');
        const isMobile = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent);
        const captureScale = isMobile ? 1.5 : 2;

        return await html2canvas(el, {
            scale: captureScale,
            backgroundColor: '#1a1100',
            useCORS: true,
            allowTaint: false,
            logging: false,
            imageTimeout: 15000,
            onclone: (clonedDoc) => {
                const clonedEl = clonedDoc.getElementById('capture-area');
                clonedEl.style.transform = 'none';
                clonedEl.style.display = 'block';
            }
        });
    }

    document.getElementById('btn-download').addEventListener('click', async () => {
        const b = document.getElementById('btn-download'); b.innerText = '⏳...'; b.disabled = true;
        try {
            const c = await generateImage();
            const b64 = c.toDataURL('image/jpeg', 0.85);
            if (window.FlutterShareChannel) {
                window.FlutterShareChannel.postMessage(JSON.stringify({ image: b64, text: 'सुभाषित', filename: 'subhashit.jpg' }));
            } else {
                const a = document.createElement('a'); a.href = b64; a.download = `subhashit.jpg`; a.click();
            }
        } catch(e) { console.error(e); alert('स्नैपशॉट बनाने में त्रुटि हुई।'); }
        b.innerText = '⬇️ डाउनलोड (JPG)'; b.disabled = false;
    });

    document.getElementById('btn-share').addEventListener('click', async () => {
        const b = document.getElementById('btn-share'); b.innerText = '⏳...'; b.disabled = true;
        try {
            const c = await generateImage();
            const textStr = '📜 सुभाषित';
            const b64 = c.toDataURL('image/jpeg', 0.85);

            if (window.FlutterShareChannel) {
                window.FlutterShareChannel.postMessage(JSON.stringify({ image: b64, text: textStr, filename: 'subhashit.jpg' }));
                b.innerText = '📱 व्हाट्सएप शेयर'; b.disabled = false; return;
            }

            if (navigator.share) {
                try {
                    const blob = dataURLtoBlob(b64);
                    const file = new File([blob], 'subhashit.jpg', { type: 'image/jpeg' });
                    if (navigator.canShare && navigator.canShare({ files: [file] })) {
                        await navigator.share({ title: 'सुभाषित', text: textStr, files: [file] });
                    } else { runFallback(b64, textStr); }
                } catch (shareErr) {
                    if (shareErr.name !== 'AbortError') { runFallback(b64, textStr); }
                }
            } else { runFallback(b64, textStr); }
        } catch(e) { if (e.name !== 'AbortError') { console.error(e); alert('शेयर करने में त्रुटि हुई।'); } }
        b.innerText = '📱 व्हाट्सएप शेयर'; b.disabled = false;
    });

    function dataURLtoBlob(dataurl) {
        try {
            var arr = dataurl.split(','), mime = arr[0].match(/:(.*?);/)[1],
                bstr = atob(arr[1]), n = bstr.length, u8arr = new Uint8Array(n);
            while(n--){ u8arr[n] = bstr.charCodeAt(n); }
            return new Blob([u8arr], {type:mime});
        } catch (e) { console.error('Blob conversion failed:', e); return null; }
    }

    function runFallback(b64, textStr) {
        alert('आपका ब्राउज़र सीधे इमेज शेयरिंग सपोर्ट नहीं करता। इमेज डाउनलोड हो रही है... उसके बाद व्हाट्सएप पर भेजें।');
        const a = document.createElement('a');
        a.href = b64; a.download = 'subhashit.jpg';
        document.body.appendChild(a); a.click(); document.body.removeChild(a);
        setTimeout(() => { window.open('https://wa.me/?text=' + encodeURIComponent(textStr), '_blank'); }, 1000);
    }

    async function quickAction(type, id) {
        window.location.href = 'subhashit.php?id=' + id + '&action=' + type;
    }

    window.addEventListener('DOMContentLoaded', () => {
        const urlParams = new URLSearchParams(window.location.search);
        const action = urlParams.get('action');
        if (action === 'download') {
            document.getElementById('btn-download').click();
        } else if (action === 'share') {
            document.getElementById('btn-share').click();
        }
    });

    updatePreview();
</script>

<?php require_once '../includes/share_modal.php'; ?>
<?php require_once '../includes/footer.php'; ?>
