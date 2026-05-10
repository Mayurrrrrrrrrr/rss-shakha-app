<?php
require_once '../includes/auth.php';
/**
 * Subhashit View - Swayamsevak Read-Only View
 * Shows the latest subhashit posted by their Mukhyashikshak
 */
$pageTitle = 'सुभाषित (Subhashit)';
require_once '../includes/header.php';
require_once '../config/db.php';
requireLogin();

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
        FOREIGN KEY (shakha_id) REFERENCES shakhas(id) ON DELETE CASCADE,
        FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
} catch (PDOException $e) {}

$shakhaId = getCurrentShakhaId();

// Fetch shakha name
$stmtShakha = $pdo->prepare("SELECT name FROM shakhas WHERE id = ?");
$stmtShakha->execute([$shakhaId]);
$shakhaName = $stmtShakha->fetchColumn() ?: 'शाखा';

// Fetch latest subhashit for this shakha
$stmt = $pdo->prepare("SELECT * FROM subhashits WHERE shakha_id = ? ORDER BY subhashit_date DESC LIMIT 1");
$stmt->execute([$shakhaId]);
$latest = $stmt->fetch();

// Fetch recent list
$stmtAll = $pdo->prepare("SELECT id, sanskrit_text, subhashit_date FROM subhashits WHERE shakha_id = ? ORDER BY subhashit_date DESC LIMIT 10");
$stmtAll->execute([$shakhaId]);
$recentList = $stmtAll->fetchAll();

// If a specific ID is requested
if (isset($_GET['id'])) {
    $stmt2 = $pdo->prepare("SELECT * FROM subhashits WHERE id = ? AND shakha_id = ?");
    $stmt2->execute([$_GET['id'], $shakhaId]);
    $selected = $stmt2->fetch();
    if ($selected) $latest = $selected;
}

$shabdarth = [];
if ($latest) {
    $shabdarth = json_decode($latest['shabdarth'], true) ?: [];
}

$hindiMonths = ['जनवरी', 'फ़रवरी', 'मार्च', 'अप्रैल', 'मई', 'जून', 'जुलाई', 'अगस्त', 'सितंबर', 'अक्टूबर', 'नवंबर', 'दिसंबर'];
$hindiDays = ['रविवार', 'सोमवार', 'मंगलवार', 'बुधवार', 'गुरुवार', 'शुक्रवार', 'शनिवार'];

function formatHindiDateSub($dateStr) {
    global $hindiMonths, $hindiDays;
    $ts = strtotime($dateStr);
    $day = $hindiDays[date('w', $ts)];
    $d = date('j', $ts);
    $m = $hindiMonths[date('n', $ts) - 1];
    $y = date('Y', $ts);
    return "$day, $d $m $y";
}
?>

<!-- html2canvas plugin (Switched to jsDelivr for better reliability) -->
<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Noto+Serif+Devanagari:wght@400;700;800;900&display=swap" rel="stylesheet">

<style>
    /* ===== PREMIUM SUBHASHIT CAPTURE FRAME ===== */
    .sub-capture-container {
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
    .sub-capture-inner {
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

    .view-recent-list a {
        display: block;
        padding: 12px 15px;
        border-bottom: 1px solid var(--border-color);
        text-decoration: none;
        color: inherit;
        border-radius: 8px;
        margin-bottom: 4px;
        transition: all 0.2s;
    }
    .view-recent-list a:hover { background: rgba(233,30,99,0.08); }
    .view-recent-list a.active-item { background: rgba(233,30,99,0.12); }
</style>

<div class="page-header">
    <h1>📜 सुभाषित (Subhashit)</h1>
</div>

<?php if (!$latest): ?>
    <div class="card">
        <div class="empty-state">
            <div class="icon">📜</div>
            <p>अभी तक कोई सुभाषित पोस्ट नहीं किया गया है।</p>
        </div>
    </div>
<?php else: ?>

    <div class="share-actions" style="justify-content: center; margin-bottom: 15px; gap: 16px; display: flex;">
        <button id="btn-download" class="btn btn-success" style="background: linear-gradient(135deg, #558B2F, #33691E); box-shadow: 0 4px 15px rgba(85,139,47,0.3);">⬇️ डाउनलोड (JPG)</button>
        <button id="btn-share" class="btn btn-whatsapp">📱 व्हाट्सएप शेयर</button>
    </div>

    <div style="overflow-x: auto; padding-bottom: 20px; text-align: center; display: flex; justify-content: center;">
        <div id="preview-scaler" style="transform-origin: top center; transform: scale(min(1, calc((100vw - 40px) / 550))); width: 550px;">
            <div id="capture-area" class="sub-capture-container" style="transform: none !important; box-shadow: none;">
                <div class="sub-capture-inner">
                    <!-- SVG Floral Corners -->
                    <svg class="corner-svg tl" viewBox="0 0 100 100"><path d="M0,0 Q50,0 50,50 Q0,50 0,0 M10,10 Q40,10 40,40 Q10,40 10,10 M0,20 Q20,20 20,40 M20,0 Q20,20 40,20" /></svg>
                    <svg class="corner-svg tr" viewBox="0 0 100 100"><path d="M0,0 Q50,0 50,50 Q0,50 0,0 M10,10 Q40,10 40,40 Q10,40 10,10 M0,20 Q20,20 20,40 M20,0 Q20,20 40,20" /></svg>
                    <svg class="corner-svg bl" viewBox="0 0 100 100"><path d="M0,0 Q50,0 50,50 Q0,50 0,0 M10,10 Q40,10 40,40 Q10,40 10,10 M0,20 Q20,20 20,40 M20,0 Q20,20 40,20" /></svg>
                    <svg class="corner-svg br" viewBox="0 0 100 100"><path d="M0,0 Q50,0 50,50 Q0,50 0,0 M10,10 Q40,10 40,40 Q10,40 10,10 M0,20 Q20,20 20,40 M20,0 Q20,20 40,20" /></svg>

                    <!-- Bhagwa Dhwaj (Flags) -->
                    <div class="flag-wrap flag-l"><div class="pole"></div><svg class="dhwaj" viewBox="0 0 100 80"><path d="M0,0 L100,25 L20,40 L100,55 L0,80 Z" /></svg></div>
                    <div class="flag-wrap flag-r"><div class="pole"></div><svg class="dhwaj" viewBox="0 0 100 80"><path d="M0,0 L100,25 L20,40 L100,55 L0,80 Z" /></svg></div>

                    <!-- Om -->
                    <div class="om-text">॥ ॐ ॥</div>

                    <!-- Date / Panchang / Shakha -->
                    <div class="meta-info">
                        <div class="meta-date-panchang">
                            <?php echo formatHindiDateSub($latest['subhashit_date']); ?><br>
                            <?php if (!empty($latest['panchang_text'])): ?>
                                <?php echo nl2br(htmlspecialchars($latest['panchang_text'])); ?>
                            <?php endif; ?>
                        </div>
                        <div class="meta-shakha">🚩 <?php echo htmlspecialchars($shakhaName); ?> 🚩</div>
                    </div>

                    <svg class="divider-svg" viewBox="0 0 400 20"><path d="M50,10 L350,10" fill="none" stroke="#bf953f" stroke-width="1.5" /><circle cx="200" cy="10" r="4" fill="#cc0000" /></svg>

                    <!-- Title with Wings -->
                    <div class="title-container">
                        <svg class="wing" viewBox="0 0 100 100"><path d="M100,50 Q70,20 40,50 Q70,80 100,50 M80,50 Q60,35 40,50 Q60,65 80,50 M40,50 Q20,40 0,50 Q20,60 40,50" /></svg>
                        <h1 class="main-title">सुभाषित</h1>
                        <svg class="wing" style="transform: scaleX(-1)" viewBox="0 0 100 100"><path d="M100,50 Q70,20 40,50 Q70,80 100,50 M80,50 Q60,35 40,50 Q60,65 80,50 M40,50 Q20,40 0,50 Q20,60 40,50" /></svg>
                    </div>

                    <svg class="divider-svg" viewBox="0 0 400 20"><path d="M100,10 L300,10" fill="none" stroke="#cc0000" stroke-width="1" /><path d="M200,2 L200,18" stroke="#bf953f" stroke-width="2" /></svg>

                    <!-- Sanskrit Shlok -->
                    <div class="shlok-text"><?php echo nl2br(htmlspecialchars($latest['sanskrit_text'])); ?></div>

                    <svg class="divider-svg" viewBox="0 0 400 20"><path d="M50,10 L350,10" fill="none" stroke="#bf953f" stroke-width="1.5" stroke-dasharray="5 5" /><circle cx="200" cy="10" r="5" fill="#bf953f" /></svg>

                    <!-- Hindi Arth -->
                    <?php if (!empty($latest['hindi_meaning'])): ?>
                        <div class="arth-box">
                            <div class="arth-label">अर्थ :-</div>
                            <div class="arth-text"><?php echo nl2br(htmlspecialchars($latest['hindi_meaning'])); ?></div>
                        </div>
                    <?php endif; ?>

                    <!-- Shabdarth -->
                    <?php if (!empty($shabdarth)): ?>
                        <div class="shabdarth-box">
                            <div class="shabdarth-title">— शब्दार्थ —</div>
                            <div class="shabdarth-grid">
                                <?php foreach ($shabdarth as $pair): ?>
                                    <div class="shabd-row">
                                        <span class="shabd-word"><?php echo htmlspecialchars($pair['shabd']); ?></span>
                                        <span>—</span>
                                        <span style="color:#002266; font-weight:400;"><?php echo htmlspecialchars($pair['arth']); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Bottom Ornament -->
                    <svg class="bottom-ornament" viewBox="0 0 200 20"><path d="M0,10 Q100,0 200,10" fill="none" stroke="currentColor" stroke-width="2" /><circle cx="100" cy="10" r="4" fill="#cc0000" /></svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent list -->
    <?php if (count($recentList) > 1): ?>
        <div class="card" style="margin-top: 20px;">
            <div class="card-header">📋 हाल के सुभाषित</div>
            <div class="view-recent-list" style="max-height: 260px; overflow-y: auto;">
                <?php foreach ($recentList as $r): ?>
                    <a href="../pages/subhashit_view.php?id=<?php echo $r['id']; ?>"
                       class="<?php echo (isset($_GET['id']) && $_GET['id'] == $r['id']) || (!isset($_GET['id']) && $r['id'] == $latest['id']) ? 'active-item' : ''; ?>">
                        <strong style="color: var(--text-primary); font-size: 0.95rem;"><?php echo mb_substr(htmlspecialchars($r['sanskrit_text']), 0, 60) . (mb_strlen($r['sanskrit_text']) > 60 ? '...' : ''); ?></strong><br>
                        <small style="color: var(--text-muted);">
                            <?php echo date('d-m-Y', strtotime($r['subhashit_date'])); ?>
                        </small>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <script>
        // Helper to convert DataURL to Blob
        function dataURLtoBlob(dataurl) {
            try {
                var arr = dataurl.split(','), mime = arr[0].match(/:(.*?);/)[1],
                    bstr = atob(arr[1]), n = bstr.length, u8arr = new Uint8Array(n);
                while(n--){ u8arr[n] = bstr.charCodeAt(n); }
                return new Blob([u8arr], {type:mime});
            } catch (e) { console.error('Blob conversion failed:', e); return null; }
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
            const btn = document.getElementById('btn-download');
            const originalText = btn.innerHTML;
            btn.innerHTML = '⏳...';
            btn.disabled = true;
            try {
                const canvas = await generateImage();
                const b64 = canvas.toDataURL('image/jpeg', 0.85);
                if (window.FlutterShareChannel) {
                    window.FlutterShareChannel.postMessage(JSON.stringify({ image: b64, text: 'सुभाषित', filename: 'subhashit.jpg' }));
                } else {
                    const a = document.createElement('a');
                    a.href = b64;
                    a.download = 'subhashit.jpg';
                    document.body.appendChild(a); a.click(); document.body.removeChild(a);
                }
            } catch (e) { console.error(e); alert('स्नैपशॉट बनाने में त्रुटि हुई।'); }
            btn.innerHTML = originalText; btn.disabled = false;
        });

        document.getElementById('btn-share').addEventListener('click', async () => {
            const btn = document.getElementById('btn-share');
            const originalText = btn.innerHTML;
            btn.innerHTML = '⏳...';
            btn.disabled = true;
            try {
                const canvas = await generateImage();
                const textStr = '📜 आज का सुभाषित — अभ्यास हेतु';
                const b64 = canvas.toDataURL('image/jpeg', 0.85);

                if (window.FlutterShareChannel) {
                    window.FlutterShareChannel.postMessage(JSON.stringify({ image: b64, text: textStr, filename: 'subhashit.jpg' }));
                    btn.innerHTML = originalText; btn.disabled = false; return;
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
            } catch (e) {
                if (e.name !== 'AbortError') { console.error(e); alert('शेयर करने में त्रुटि हुई।'); }
            }
            btn.innerHTML = originalText; btn.disabled = false;
        });

        function runFallback(b64, textStr) {
            alert('आपका ब्राउज़र सीधे इमेज शेयरिंग सपोर्ट नहीं करता। इमेज डाउनलोड हो रही है... उसके बाद व्हाट्सएप पर भेजें।');
            const a = document.createElement('a');
            a.href = b64; a.download = 'subhashit.jpg';
            document.body.appendChild(a); a.click(); document.body.removeChild(a);
            setTimeout(() => { window.open('https://wa.me/?text=' + encodeURIComponent(textStr), '_blank'); }, 1000);
        }
    </script>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
