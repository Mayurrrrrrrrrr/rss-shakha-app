<?php
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

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

<style>
    .sub-capture-container {
        width: 100%;
        max-width: 480px;
        margin: 0 auto;
        background: linear-gradient(180deg, #FFF9E6 0%, #FFF5F5 50%, #F0FFF4 100%);
        color: #3E2723;
        font-family: 'Noto Sans Devanagari', serif;
        padding: 0;
        box-sizing: border-box;
        overflow: hidden;
        position: relative;
        box-shadow: 0 4px 25px rgba(0, 0, 0, 0.18);
        border: 3px solid #D4A574;
    }
    .sub-capture-container::before { content: '🌸'; position: absolute; top: 2px; left: 8px; font-size: 24px; z-index: 10; }
    .sub-capture-container::after { content: '🌸'; position: absolute; top: 2px; right: 8px; font-size: 24px; z-index: 10; }
    .sub-floral-bottom::before { content: '🌺'; position: absolute; bottom: 2px; left: 8px; font-size: 24px; z-index: 10; }
    .sub-floral-bottom::after { content: '🌺'; position: absolute; bottom: 2px; right: 8px; font-size: 24px; z-index: 10; }
    .sub-floral-band { background: linear-gradient(90deg, #FCE4EC, #FFF9C4, #E8F5E9, #FCE4EC); text-align: center; padding: 5px 0; font-size: 16px; letter-spacing: 6px; color: #E91E63; border-bottom: 1px solid #F8BBD0; }
    .sub-date-strip { background: linear-gradient(135deg, #558B2F, #33691E); color: #F1F8E9; text-align: center; padding: 10px 20px; font-size: 14px; font-weight: 500; }
    .sub-shakha-header { background: linear-gradient(135deg, #C2185B 0%, #AD1457 100%); color: #FCE4EC; padding: 14px 20px; text-align: center; font-size: 18px; font-weight: 700; letter-spacing: 1px; border-bottom: 2px solid #E91E63; }
    .sub-main-title { text-align: center; padding: 18px 20px 4px; font-size: 30px; font-weight: 800; color: #880E4F; letter-spacing: 3px; }
    .sub-subtitle { text-align: center; font-size: 14px; color: #AD1457; margin-bottom: 12px; font-weight: 500; }
    .sub-ornament { text-align: center; color: #E91E63; font-size: 16px; margin: 4px 0; letter-spacing: 10px; }
    .sub-sanskrit-block { padding: 16px 24px; text-align: center; }
    .sub-sanskrit-text { font-size: 22px; line-height: 1.8; color: #1B5E20; font-weight: 600; white-space: pre-wrap; word-break: break-word; }
    .sub-hindi-section { margin: 0 20px; padding: 14px 18px; background: rgba(255, 249, 196, 0.5); border-radius: 10px; border: 1px dashed #F9A825; }
    .sub-section-label { font-size: 15px; font-weight: 700; color: #E65100; text-align: center; margin-bottom: 8px; letter-spacing: 1px; }
    .sub-hindi-text { font-size: 17px; line-height: 1.7; color: #3E2723; text-align: center; white-space: pre-wrap; word-break: break-word; }
    .sub-shabdarth-section { margin: 14px 20px; padding: 14px 18px; background: rgba(232, 245, 233, 0.5); border-radius: 10px; border: 1px dashed #81C784; }
    .sub-shabdarth-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 2px 16px; }
    .sub-shabdarth-row { display: flex; justify-content: center; align-items: baseline; gap: 6px; padding: 4px 0; font-size: 14px; color: #2E7D32; }
    .sub-shabdarth-word { font-weight: 700; color: #1B5E20; }
    .sub-shabdarth-dash { color: #81C784; }
    .sub-shabdarth-meaning { color: #33691E; }
    .sub-footer-band { margin-top: 16px; background: linear-gradient(90deg, #FCE4EC, #FFF9C4, #E8F5E9, #FCE4EC); text-align: center; padding: 5px 0; font-size: 16px; letter-spacing: 6px; color: #E91E63; border-top: 1px solid #F8BBD0; }

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

    <div style="overflow-x: auto; padding-bottom: 20px; text-align: center;">
        <div id="capture-area" class="sub-capture-container">
            <div class="sub-date-strip"><?php echo formatHindiDateSub($latest['subhashit_date']); ?></div>
            <div class="sub-shakha-header">🚩 <?php echo htmlspecialchars($shakhaName); ?> 🚩</div>
            <div class="sub-floral-band">🌼 ❀ 🌸 ❀ 🌼 ❀ 🌸</div>
            <div class="sub-main-title">सुभाषित</div>
            <div class="sub-subtitle">( अभ्यास हेतु )</div>
            <div class="sub-ornament">─ ✦ ─</div>

            <div class="sub-sanskrit-block">
                <div class="sub-sanskrit-text"><?php echo nl2br(htmlspecialchars($latest['sanskrit_text'])); ?></div>
            </div>

            <div class="sub-ornament">─ ✦ ─</div>

            <?php if (!empty($latest['hindi_meaning'])): ?>
                <div class="sub-hindi-section">
                    <div class="sub-section-label">— हिंदी अर्थ —</div>
                    <div class="sub-hindi-text"><?php echo nl2br(htmlspecialchars($latest['hindi_meaning'])); ?></div>
                </div>
            <?php endif; ?>

            <?php if (!empty($shabdarth)): ?>
                <div class="sub-shabdarth-section">
                    <div class="sub-section-label">— चुनिंदा शब्दार्थ —</div>
                    <div class="sub-shabdarth-grid">
                        <?php foreach ($shabdarth as $pair): ?>
                            <div class="sub-shabdarth-row">
                                <span class="sub-shabdarth-word"><?php echo htmlspecialchars($pair['shabd']); ?></span>
                                <span class="sub-shabdarth-dash">—</span>
                                <span class="sub-shabdarth-meaning"><?php echo htmlspecialchars($pair['arth']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="sub-floral-bottom" style="position: relative;">
                <div class="sub-footer-band">🌼 ❀ 🌸 ❀ 🌼 ❀ 🌸</div>
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

        document.getElementById('btn-download').addEventListener('click', async () => {
            const btn = document.getElementById('btn-download');
            const originalText = btn.innerHTML;
            btn.innerHTML = '⏳ कृपया प्रतीक्षा करें...';
            btn.disabled = true;
            try {
                const canvas = await generateImage();
                if (window.FlutterShareChannel) {
                    const b64 = canvas.toDataURL('image/jpeg', 0.95);
                    window.FlutterShareChannel.postMessage(JSON.stringify({ image: b64, text: 'सुभाषित', filename: 'subhashit.jpg' }));
                    btn.innerHTML = originalText; btn.disabled = false; return;
                }
                const a = document.createElement('a');
                a.href = canvas.toDataURL('image/jpeg', 0.95);
                a.download = 'subhashit.jpg';
                document.body.appendChild(a); a.click(); document.body.removeChild(a);
            } catch (e) { console.error(e); alert('स्नैपशॉट बनाने में त्रुटि हुई।'); }
            btn.innerHTML = originalText; btn.disabled = false;
        });

        document.getElementById('btn-share').addEventListener('click', async () => {
            const btn = document.getElementById('btn-share');
            const originalText = btn.innerHTML;
            btn.innerHTML = '⏳ तैयार हो रहा है...';
            btn.disabled = true;
            try {
                const canvas = await generateImage();
                const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/jpeg', 0.95));
                const file = new File([blob], 'subhashit.jpg', { type: 'image/jpeg' });
                const textStr = '📜 आज का सुभाषित — अभ्यास हेतु';

                if (window.FlutterShareChannel) {
                    const b64 = canvas.toDataURL('image/jpeg', 0.95);
                    window.FlutterShareChannel.postMessage(JSON.stringify({ image: b64, text: textStr, filename: 'subhashit.jpg' }));
                    btn.innerHTML = originalText; btn.disabled = false; return;
                }

                if (navigator.share && navigator.canShare && navigator.canShare({ files: [file] })) {
                    await navigator.share({ title: 'सुभाषित', text: textStr, files: [file] });
                } else {
                    alert('इमेज डाउनलोड हो रही है... उसके बाद व्हाट्सएप पर भेजें।');
                    const a = document.createElement('a');
                    a.href = URL.createObjectURL(blob);
                    a.download = 'subhashit.jpg';
                    document.body.appendChild(a); a.click(); document.body.removeChild(a);
                    window.open('https://wa.me/?text=' + encodeURIComponent(textStr), '_blank');
                }
            } catch (e) {
                if (e.name !== 'AbortError') { console.error(e); alert('शेयर करने में त्रुटि हुई।'); }
            }
            btn.innerHTML = originalText; btn.disabled = false;
        });
    </script>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
