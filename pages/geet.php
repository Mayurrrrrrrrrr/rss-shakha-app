<?php
require_once '../includes/auth.php';
require_once '../config/db.php';
requireLogin();

if (!isAdmin() && !isMukhyashikshak()) {
    header('Location: dashboard.php');
    exit;
}

// Auto-create table
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS geet (
        id INT AUTO_INCREMENT PRIMARY KEY,
        shakha_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        geet_type ENUM('Ekal', 'Sanghik') NOT NULL DEFAULT 'Sanghik',
        lyrics TEXT NOT NULL,
        meaning_or_context TEXT,
        geet_date DATE NOT NULL,
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (shakha_id) REFERENCES shakhas(id) ON DELETE CASCADE,
        FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
} catch (PDOException $e) {}

$shakhaId = getCurrentShakhaId();
$success = '';
$error = '';

$geetId = $_GET['id'] ?? null;
$title = '';
$geet_type = 'Sanghik';
$lyrics = '';
$meaning = '';
$geetDate = date('Y-m-d');
$isReadOnly = false;

// Fetch shakha name
$stmtShakha = $pdo->prepare("SELECT name FROM shakhas WHERE id = ?");
$stmtShakha->execute([$shakhaId]);
$shakhaName = $stmtShakha->fetchColumn() ?: 'शाखा';

if ($geetId) {
    // Fetch the geet (allow viewing from any shakha)
    $stmt = $pdo->prepare("SELECT * FROM geet WHERE id = ?");
    $stmt->execute([$geetId]);
    $existing = $stmt->fetch();
    if ($existing) {
        $title = $existing['title'];
        $geet_type = $existing['geet_type'];
        $lyrics = $existing['lyrics'];
        $meaning = $existing['meaning_or_context'];
        $geetDate = $existing['geet_date'];
        $isReadOnly = ($existing['shakha_id'] != $shakhaId);
    } else {
        $geetId = null; 
        $isReadOnly = false;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $geetIdToSave = $_POST['geet_id'] ?? null;
    $title = trim($_POST['title'] ?? '');
    $geet_type = $_POST['geet_type'] ?? 'Sanghik';
    $lyrics = trim($_POST['lyrics'] ?? '');
    $meaning = trim($_POST['meaning'] ?? '');
    $geetDate = trim($_POST['geet_date'] ?? date('Y-m-d'));
    $createdBy = $_SESSION['user_id'];

    if ($title && $lyrics) {
        try {
            if ($geetIdToSave) {
                // Security check
                $stmtCheck = $pdo->prepare("SELECT shakha_id FROM geet WHERE id = ?");
                $stmtCheck->execute([$geetIdToSave]);
                if ($stmtCheck->fetchColumn() != $shakhaId) {
                    $error = "त्रुटि: आप केवल अपनी शाखा के गीत अपडेट कर सकते हैं।";
                } else {
                    // Update
                    $stmt = $pdo->prepare("UPDATE geet SET title = ?, geet_type = ?, lyrics = ?, meaning_or_context = ?, geet_date = ? WHERE id = ? AND shakha_id = ?");
                    $stmt->execute([$title, $geet_type, $lyrics, $meaning, $geetDate, $geetIdToSave, $shakhaId]);
                    $success = "गीत सफलतापूर्वक अपडेट किया गया!";
                }
            } else {
                // Insert
                $stmt = $pdo->prepare("INSERT INTO geet (shakha_id, title, geet_type, lyrics, meaning_or_context, geet_date, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$shakhaId, $title, $geet_type, $lyrics, $meaning, $geetDate, $createdBy]);
                $success = "गीत सफलतापूर्वक सहेजा गया!";
                
                $title = '';
                $lyrics = '';
                $meaning = '';
                $geetId = null;
            }
        } catch (Exception $e) {
            $error = "त्रुटि: गीत सहेजने में विफल। " . $e->getMessage();
        }
    } else {
        $error = "शीर्षक और गीत के बोल आवश्यक हैं।";
    }
}

// Fetch recent geet from all shakhas
$stmt = $pdo->prepare("SELECT g.id, g.title, g.geet_type, g.geet_date, g.shakha_id, sh.name as origin_shakha_name FROM geet g JOIN shakhas sh ON g.shakha_id = sh.id ORDER BY g.geet_date DESC LIMIT 30");
$stmt->execute();
$recentGeet = $stmt->fetchAll();

// Pre-encode static flag icon to Base64 to avoid html2canvas loading/CORS issues
$flagIconPath = dirname(__DIR__) . '/assets/images/flag_icon.png';
$flagBase64 = '';
if (file_exists($flagIconPath)) {
    $flagBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($flagIconPath));
}

$pageTitle = 'गीत (Geet)';
require_once '../includes/header.php';
?>

<script src="../assets/js/html2canvas.min.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Noto+Serif+Devanagari:wght@400;700;800;900&display=swap" rel="stylesheet">

<style>
    /* ===== PREMIUM GEET CAPTURE FRAME ===== */
    .geet-capture-container {
        background: linear-gradient(135deg, #aa771c, #fcf6ba, #aa771c);
        padding: 12px;
        border-radius: 4px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.2);
        width: 100%;
        max-width: 550px;
        margin: 0 auto;
        position: relative;
        font-family: 'Noto Serif Devanagari', serif;
    }
    .geet-capture-inner {
        background: #fff9e3;
        background-image: radial-gradient(circle at 50% 10%, #fffdf5 0%, #fff9e3 70%);
        border: 2px solid #5a4408;
        padding: 30px 20px;
        position: relative;
        overflow: hidden;
        box-sizing: border-box;
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    .corner-svg { position: absolute; width: 80px; height: 80px; fill: #8b6b0d; z-index: 5; pointer-events: none; }
    .tl { top: 0; left: 0; }
    .tr { top: 0; right: 0; transform: scaleX(-1); }
    .bl { bottom: 0; left: 0; transform: scaleY(-1); }
    .br { bottom: 0; right: 0; transform: scale(-1); }
    
    .flag-wrap { position: absolute; top: 15px; width: 40px; height: 60px; z-index: 10; }
    .flag-l { left: 15px; }
    .flag-r { right: 15px; transform: scaleX(-1); }
    .pole { width: 3px; height: 100%; background: linear-gradient(to right, #444, #888, #333); position: absolute; left: 0; }
    .dhwaj { position: absolute; left: 3px; width: 35px; height: 25px; fill: #ff8c00; transform-origin: left center; }

    .om-text { font-size: 50px; color: #cc0000; font-weight: 900; letter-spacing: 2px; margin-bottom: 5px; z-index: 2; line-height: 1; }

    .meta-info { display: flex; flex-direction: column; align-items: center; text-align: center; margin: 5px 0 10px; z-index: 2; }
    .meta-date { font-size: 14px; font-weight: 700; color: #5a4408; }
    .meta-shakha { font-size: 15px; font-weight: 900; color: #880E4F; margin-top: 4px; }

    .title-container { display: flex; align-items: center; justify-content: center; gap: 15px; margin: 10px 0; z-index: 2; }
    .wing { width: 35px; height: 35px; fill: #cc0000; }
    .main-title { font-size: clamp(24px, 5.5vw, 36px); font-weight: 900; color: #002266; margin: 0; text-align: center; text-shadow: 2px 2px 0 #fff, -1px -1px 0 #fff, 1px -1px 0 #fff, -1px 1px 0 #fff; line-height: 1.3; }
    .divider-svg { width: 80%; height: 20px; margin: 5px 0; z-index: 2; }
    
    .geet-lyrics-text { text-align: center; font-size: clamp(16px, 4.2vw, 20px); line-height: 1.6; font-weight: 700; color: #000000; margin: 15px 0; z-index: 2; white-space: pre-wrap; word-break: break-word; }
    .meaning-box { display: flex; flex-direction: column; align-items: center; width: 95%; margin-top: 20px; z-index: 2; background: rgba(230,81,0,0.04); border: 1px dashed #5a4408; padding: 12px; border-radius: 8px; }
    .meaning-label { font-weight: 900; font-size: 16px; color: #610000; margin-bottom: 6px; }
    .meaning-text { font-size: clamp(14px, 3.8vw, 17px); color: #002266; font-weight: 600; line-height: 1.5; text-align: center; white-space: pre-wrap; word-break: break-word; }

    .geet-type-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; margin-top: 5px; }
    .geet-type-badge.sanghik { background: #e8f5e9; color: #2e7d32; }
    .geet-type-badge.ekal { background: #e3f2fd; color: #1565c0; }

    .bottom-ornament { margin-top: 20px; width: 150px; height: 15px; fill: #bf953f; }

    /* LAYOUT STYLES */
    .layout-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    @media (max-width: 900px) { .layout-grid { grid-template-columns: 1fr; } }

    .geet-form-card { background: rgba(34, 34, 46, 0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255, 107, 0, 0.2); border-radius: 16px; padding: 28px; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3); }
    .geet-form-card .card-header { border-bottom: 1px solid rgba(255, 107, 0, 0.15); font-size: 1.3rem; padding-bottom: 15px; margin-bottom: 25px; color: #ffb74d; }
    .geet-form-input { width: 100%; padding: 12px 14px; background: rgba(15, 15, 20, 0.6); border: 1px solid var(--border-light); border-radius: 10px; color: var(--text-primary); font-size: 1rem; transition: all 0.3s ease; }
    .geet-form-input:focus { outline: none; border-color: #ff6b00; box-shadow: 0 0 0 3px rgba(255, 107, 0, 0.15); background: rgba(15, 15, 20, 0.9); }
    .geet-form-label { display: block; margin-bottom: 6px; font-weight: 600; color: var(--text-secondary); font-size: 0.95rem; }
</style>

<div class="page-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
    <h1>🎵 गीत (Geet)</h1>
    <?php if (isAdmin() && !empty($geetId)): ?>
        <button class="btn btn-warning" onclick="openShareModal('geet', <?php echo $geetId; ?>)">🔗 Share across Shakhas</button>
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
        <div class="geet-form-card">
            <div class="card-header">📋 गीत विवरण दर्ज करें</div>
            <form method="POST" action="geet.php" id="geet-form">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <input type="hidden" name="geet_id" value="<?php echo htmlspecialchars($geetId ?? ''); ?>">
                
                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="geet-form-label">गीत का प्रकार (Type)</label>
                    <select name="geet_type" id="inp-type" class="geet-form-input" required>
                        <option value="Sanghik" <?php echo ($geet_type == 'Sanghik') ? 'selected' : ''; ?>>सांघिक गीत (Chorus)</option>
                        <option value="Ekal" <?php echo ($geet_type == 'Ekal') ? 'selected' : ''; ?>>एकल गीत (Solo)</option>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="geet-form-label">शीर्षक (Title) *</label>
                    <input type="text" name="title" id="inp-title" class="geet-form-input" placeholder="उदा. ध्येय पथ पर बढ़ चले..." required value="<?php echo htmlspecialchars($title); ?>">
                </div>

                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="geet-form-label">दिनांक (Date)</label>
                    <input type="date" name="geet_date" id="inp-date" class="geet-form-input" value="<?php echo htmlspecialchars($geetDate); ?>">
                </div>

                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="geet-form-label">बोल (Lyrics) *</label>
                    <textarea name="lyrics" id="inp-lyrics" class="geet-form-input" rows="8" required placeholder="गीत के बोल यहाँ लिखें..."><?php echo htmlspecialchars($lyrics); ?></textarea>
                </div>

                <div class="form-group" style="margin-bottom: 24px;">
                    <label class="geet-form-label" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
                        <span>अर्थ / संदर्भ / अमृत वचन</span>
                        <button type="button" class="btn btn-sm" onclick="generateAiContext()" id="btn-ai-geet" style="background: rgba(255, 107, 0, 0.1); color: #ffb74d; border: 1px dashed #ff6b00; padding: 4px 8px; cursor: pointer;">✨ AI से अर्थ निकालें</button>
                    </label>
                    <textarea name="meaning" id="inp-meaning" class="geet-form-input" rows="5" placeholder="गीत का भावार्थ या इससे जुड़ा कोई अमृत वचन यहाँ लिखें..."><?php echo htmlspecialchars($meaning); ?></textarea>
                </div>

                <?php if (!$isReadOnly): ?>
                <button type="submit" class="btn btn-primary" style="width: 100%; border-radius: 12px; padding: 15px; margin-bottom: 10px;">
                    <?php echo $geetId ? '💾 अपडेट करें (Update)' : '💾 सहेजें (Save)'; ?>
                </button>
                <?php endif; ?>
                <?php if ($geetId): ?>
                    <a href="geet.php" class="btn btn-outline" style="width: 100%; text-align: center; display: block;">➕ नया गीत बनाएं (Create New)</a>
                <?php endif; ?>
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
                <div id="capture-area" class="geet-capture-container" style="transform: none !important; box-shadow: none;">
                    <div class="geet-capture-inner">
                        <!-- SVG Floral Corners -->
                        <svg class="corner-svg tl" viewBox="0 0 100 100"><path d="M0,0 Q50,0 50,50 Q0,50 0,0 M10,10 Q40,10 40,40 Q10,40 10,10 M0,20 Q20,20 20,40 M20,0 Q20,20 40,20" /></svg>
                        <svg class="corner-svg tr" viewBox="0 0 100 100"><path d="M0,0 Q50,0 50,50 Q0,50 0,0 M10,10 Q40,10 40,40 Q10,40 10,10 M0,20 Q20,20 20,40 M20,0 Q20,20 40,20" /></svg>
                        <svg class="corner-svg bl" viewBox="0 0 100 100"><path d="M0,0 Q50,0 50,50 Q0,50 0,0 M10,10 Q40,10 40,40 Q10,40 10,10 M0,20 Q20,20 20,40 M20,0 Q20,20 40,20" /></svg>
                        <svg class="corner-svg br" viewBox="0 0 100 100"><path d="M0,0 Q50,0 50,50 Q0,50 0,0 M10,10 Q40,10 40,40 Q10,40 10,10 M0,20 Q20,20 20,40 M20,0 Q20,20 40,20" /></svg>

                        <!-- Dhwaj -->
                        <div class="flag-wrap flag-l"><div class="pole"></div>
                            <?php if ($flagBase64): ?>
                                <img src="<?php echo $flagBase64; ?>" style="height: 25px; width: auto; position: absolute; left: 3px; transform-origin: left center;" alt="🚩">
                            <?php else: ?>
                                <svg class="dhwaj" viewBox="0 0 100 80"><path d="M0,0 L100,25 L20,40 L100,55 L0,80 Z" /></svg>
                            <?php endif; ?>
                        </div>
                        <div class="flag-wrap flag-r"><div class="pole"></div>
                            <?php if ($flagBase64): ?>
                                <img src="<?php echo $flagBase64; ?>" style="height: 25px; width: auto; position: absolute; left: 3px; transform-origin: left center;" alt="🚩">
                            <?php else: ?>
                                <svg class="dhwaj" viewBox="0 0 100 80"><path d="M0,0 L100,25 L20,40 L100,55 L0,80 Z" /></svg>
                            <?php endif; ?>
                        </div>

                        <!-- Om -->
                        <div class="om-text">॥ ॐ ॥</div>

                        <!-- Meta Info -->
                        <div class="meta-info">
                            <div class="meta-date" id="prev-date"></div>
                            <div class="meta-shakha">🚩 <?php echo htmlspecialchars($shakhaName); ?> 🚩</div>
                            <div><span id="prev-type" class="geet-type-badge"></span></div>
                        </div>

                        <svg class="divider-svg" viewBox="0 0 400 20"><path d="M50,10 L350,10" fill="none" stroke="#bf953f" stroke-width="1.5" /><circle cx="200" cy="10" r="4" fill="#cc0000" /></svg>

                        <!-- Title -->
                        <div class="title-container">
                            <svg class="wing" viewBox="0 0 100 100"><path d="M100,50 Q70,20 40,50 Q70,80 100,50 M80,50 Q60,35 40,50 Q60,65 80,50 M40,50 Q20,40 0,50 Q20,60 40,50" /></svg>
                            <h1 class="main-title" id="prev-title"></h1>
                            <svg class="wing" style="transform: scaleX(-1)" viewBox="0 0 100 100"><path d="M100,50 Q70,20 40,50 Q70,80 100,50 M80,50 Q60,35 40,50 Q60,65 80,50 M40,50 Q20,40 0,50 Q20,60 40,50" /></svg>
                        </div>

                        <svg class="divider-svg" viewBox="0 0 400 20"><path d="M100,10 L300,10" fill="none" stroke="#cc0000" stroke-width="1" /><path d="M200,2 L200,18" stroke="#bf953f" stroke-width="2" /></svg>

                        <!-- Lyrics -->
                        <div class="geet-lyrics-text" id="prev-lyrics"></div>

                        <!-- Meaning -->
                        <div id="prev-meaning-section" class="meaning-box" style="display: none;">
                            <div class="meaning-label">— भावार्थ / संदर्भ —</div>
                            <div class="meaning-text" id="prev-meaning"></div>
                        </div>

                        <!-- Bottom Ornament -->
                        <svg class="bottom-ornament" viewBox="0 0 200 20"><path d="M0,10 Q100,0 200,10" fill="none" stroke="currentColor" stroke-width="2" /><circle cx="100" cy="10" r="4" fill="#cc0000" /></svg>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<hr style="margin: 40px 0; border: 0; border-top: 1px solid rgba(255, 107, 0, 0.1);">

<!-- Recent List Section -->
<div class="card">
    <div class="card-header">📋 हाल के गीत (Recent Geets)</div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>दिनांक</th>
                    <th>गीत शीर्षक</th>
                    <th>प्रकार</th>
                    <th>क्रियाएं (Actions)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentGeet as $g): ?>
                    <tr>
                        <td><?php echo date('d-m-Y', strtotime($g['geet_date'])); ?></td>
                        <td>
                            <strong style="color: var(--text-primary);"><?php echo htmlspecialchars($g['title']); ?></strong>
                            <?php if ($g['shakha_id'] != $shakhaId): ?>
                                <small style="color: #666;">(🚩 <?php echo htmlspecialchars($g['origin_shakha_name']); ?>)</small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="geet-type-badge <?php echo $g['geet_type'] == 'Ekal' ? 'ekal' : 'sanghik'; ?>">
                                <?php echo $g['geet_type'] == 'Ekal' ? 'एकल' : 'सांघिक'; ?>
                            </span>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <?php if ($g['shakha_id'] == $shakhaId || isAdmin()): ?>
                                    <a href="geet.php?id=<?php echo $g['id']; ?>" class="btn btn-sm btn-outline">✏️ Edit</a>
                                <?php else: ?>
                                    <a href="geet.php?id=<?php echo $g['id']; ?>" class="btn btn-sm btn-outline">👁️ View/Share</a>
                                <?php endif; ?>
                                <button class="btn btn-sm btn-success" onclick="quickAction('download', <?php echo $g['id']; ?>)">⬇️ JPG</button>
                                <button class="btn btn-sm btn-whatsapp" onclick="quickAction('share', <?php echo $g['id']; ?>)">📱 Share</button>
                                <?php if (isAdmin()): ?>
                                    <button class="btn btn-sm btn-warning" onclick="openShareModal('geet', <?php echo $g['id']; ?>)">🔗 Global</button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($recentGeet)): ?>
                    <tr><td colspan="4" style="text-align: center; color: var(--text-muted);">कोई गीत नहीं मिला।</td></tr>
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

function updatePreview() {
    const titleVal = document.getElementById('inp-title').value;
    const lyricsVal = document.getElementById('inp-lyrics').value;
    const meaningVal = document.getElementById('inp-meaning').value;
    const typeVal = document.getElementById('inp-type').value;
    const dateVal = document.getElementById('inp-date').value;

    document.getElementById('prev-title').innerText = titleVal.trim() || 'गीत शीर्षक';
    document.getElementById('prev-lyrics').innerHTML = (lyricsVal.trim() || 'गीत के बोल यहाँ दिखेंगे...').replace(/\n/g, '<br>');
    
    const mSect = document.getElementById('prev-meaning-section');
    const mEl = document.getElementById('prev-meaning');
    if (meaningVal.trim()) {
        mSect.style.display = 'flex';
        mEl.innerHTML = meaningVal.replace(/\n/g, '<br>');
    } else {
        mSect.style.display = 'none';
    }

    const typeLabel = document.getElementById('prev-type');
    typeLabel.innerText = typeVal === 'Ekal' ? 'एकल गीत' : 'सांघिक गीत';
    typeLabel.className = 'geet-type-badge ' + (typeVal === 'Ekal' ? 'ekal' : 'sanghik');

    document.getElementById('prev-date').innerText = formatHindiDate(dateVal);
}

document.getElementById('inp-title').addEventListener('input', updatePreview);
document.getElementById('inp-lyrics').addEventListener('input', updatePreview);
document.getElementById('inp-meaning').addEventListener('input', updatePreview);
document.getElementById('inp-type').addEventListener('change', updatePreview);
document.getElementById('inp-date').addEventListener('input', updatePreview);

async function generateImage() {
    const el = document.getElementById('capture-area');
    const isMobile = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent);
    const captureScale = isMobile ? 1.5 : 2;

    return await html2canvas(el, {
        scale: captureScale,
        backgroundColor: '#FFF9E3',
        useCORS: true,
        allowTaint: false,
        logging: false,
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
            window.FlutterShareChannel.postMessage(JSON.stringify({ image: b64, text: 'गीत', filename: 'geet.jpg' }));
        } else {
            const a = document.createElement('a'); a.href = b64; a.download = `geet.jpg`; document.body.appendChild(a); a.click(); document.body.removeChild(a);
        }
    } catch(e) { console.error(e); alert('स्नैपशॉट बनाने में त्रुटि हुई।'); }
    b.innerText = '⬇️ डाउनलोड (JPG)'; b.disabled = false;
});

document.getElementById('btn-share').addEventListener('click', async () => {
    const b = document.getElementById('btn-share'); b.innerText = '⏳...'; b.disabled = true;
    try {
        const c = await generateImage();
        const textStr = '🎵 गीत';
        const b64 = c.toDataURL('image/jpeg', 0.85);

        if (window.FlutterShareChannel) {
            window.FlutterShareChannel.postMessage(JSON.stringify({ image: b64, text: textStr, filename: 'geet.jpg' }));
            b.innerText = '📱 व्हाट्सएप शेयर'; b.disabled = false; return;
        }

        if (navigator.share) {
            try {
                const blob = dataURLtoBlob(b64);
                const file = new File([blob], 'geet.jpg', { type: 'image/jpeg' });
                if (navigator.canShare && navigator.canShare({ files: [file] })) {
                    await navigator.share({ title: 'गीत', text: textStr, files: [file] });
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
    a.href = b64; a.download = 'geet.jpg';
    document.body.appendChild(a); a.click(); document.body.removeChild(a);
    setTimeout(() => { window.open('https://wa.me/?text=' + encodeURIComponent(textStr), '_blank'); }, 1000);
}

async function quickAction(type, id) {
    window.location.href = 'geet.php?id=' + id + '&action=' + type;
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

async function generateAiContext() {
    const lyrics = document.getElementById('inp-lyrics').value.trim();
    if (!lyrics) {
        alert("कृपया पहले गीत के बोल (Lyrics) दर्ज करें।");
        return;
    }

    const btn = document.getElementById('btn-ai-geet');
    const originalText = btn.innerHTML;
    btn.innerHTML = '⏳ AI सोच रहा है...';
    btn.disabled = true;

    try {
        const response = await fetch('../api/ai_content.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'geet_meaning', lyrics: lyrics })
        });
        
        const data = await response.json();
        if (data.success) {
            document.getElementById('inp-meaning').value = data.result;
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
</script>

<?php require_once '../includes/share_modal.php'; ?>
<?php require_once '../includes/footer.php'; ?>
