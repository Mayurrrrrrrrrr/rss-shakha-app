<?php
require_once '../includes/auth.php';
require_once '../config/db.php';
requireLogin();

if (!isAdmin() && !isMukhyashikshak()) {
    header('Location: dashboard.php');
    exit;
}

$shakhaId = getCurrentShakhaId();
$success = '';
$error = '';

$noticeId = $_GET['id'] ?? null;
if ($noticeId) {
    // Verify it belongs to this shakha
    $stmt = $pdo->prepare("SELECT * FROM notices WHERE id = ? AND shakha_id = ?");
    $stmt->execute([$noticeId, $shakhaId]);
    $existing = $stmt->fetch();
    if ($existing) {
        $subject = $existing['subject'];
        $noticeDate = $existing['notice_date'];
        $tithi = $existing['tithi'];
        $location = $existing['location'];
        $message = $existing['message'];
    } else {
        $noticeId = null; // invalid ID
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $noticeIdToSave = $_POST['notice_id'] ?? null;
    $subject = trim($_POST['subject'] ?? '');
    $noticeDate = trim($_POST['notice_date'] ?? date('Y-m-d'));
    $tithi = trim($_POST['tithi'] ?? '');
    $location = trim($_POST['location'] ?? 'घाटकोपर पूर्व, मुंबई');
    $message = trim($_POST['message'] ?? '');
    $createdBy = $_SESSION['user_id'];

    if ($subject && $message) {
        try {
            if ($noticeIdToSave) {
                // Update
                $stmt = $pdo->prepare("UPDATE notices SET subject = ?, notice_date = ?, tithi = ?, location = ?, message = ? WHERE id = ? AND shakha_id = ?");
                $stmt->execute([$subject, $noticeDate, $tithi, $location, $message, $noticeIdToSave, $shakhaId]);
                $success = "सूचना सफलतापूर्वक अपडेट की गई!";
            } else {
                // Insert
                $stmt = $pdo->prepare("INSERT INTO notices (shakha_id, subject, notice_date, tithi, location, message, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$shakhaId, $subject, $noticeDate, $tithi, $location, $message, $createdBy]);
                $success = "सूचना सफलतापूर्वक सहेजी गई!";

                // clear fields after successful insert so it's ready for next
                $subject = '';
                $message = '';
                $noticeId = null;
            }
        } catch (Exception $e) {
            $error = "त्रुटि: सूचना सहेजने में विफल। " . $e->getMessage();
        }
    } else {
        $error = "विषय और संदेश आवश्यक हैं।";
    }
}

// Fetch recent notices
$stmt = $pdo->prepare("SELECT * FROM notices WHERE shakha_id = ? ORDER BY created_at DESC LIMIT 10");
$stmt->execute([$shakhaId]);
$recentNotices = $stmt->fetchAll();

$pageTitle = 'सूचना (Notice)';
require_once '../includes/header.php';
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

<style>
    /* Scoped styles exactly like snapshot.php */
    .capture-container {
        width: 100%;
        max-width: 480px;
        min-height: 853px;
        margin: 0 auto;
        background: #FFF9F2;
        color: #4A1C00;
        font-family: 'Noto Sans Devanagari', sans-serif;
        padding: 0;
        box-sizing: border-box;
        border-radius: 0;
        overflow: hidden;
        position: relative;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        border: 4px solid #FF6B00;
    }

    .capture-container::before,
    .capture-container::after {
        content: '❁';
        position: absolute;
        font-size: 36px;
        color: #FF6B00;
        line-height: 1;
        z-index: 10;
    }

    .capture-container::before {
        top: -2px;
        left: 4px;
    }

    .capture-container::after {
        top: -2px;
        right: 4px;
    }

    .capture-body-inner {
        position: relative;
        padding: 15px 20px;
        z-index: 5;
        background: #FFF9F2;
        min-height: 500px;
    }

    .capture-body-inner::before,
    .capture-body-inner::after {
        content: '❁';
        position: absolute;
        font-size: 36px;
        color: #FF6B00;
        line-height: 1;
        z-index: 10;
    }

    .capture-body-inner::before {
        bottom: -2px;
        left: 4px;
    }

    .capture-body-inner::after {
        bottom: -2px;
        right: 4px;
    }

    .capture-header {
        background: #FF6B00;
        color: #FFFFFF;
        padding: 15px 20px;
        font-size: 22px;
        font-weight: bold;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        text-align: center;
        position: relative;
        flex-direction: column;
    }

    .header-flag {
        font-size: 28px;
    }

    .tithi-bar {
        background: #FFE0B2;
        color: #D84315;
        text-align: center;
        padding: 12px 20px;
        font-weight: bold;
        font-size: 16px;
        border-bottom: 2px solid #FFB74D;
        line-height: 1.4;
    }

    .capture-subject {
        font-size: 24px;
        font-weight: 800;
        color: #D84315;
        text-align: center;
        margin: 20px 0 10px 0;
        text-decoration: underline;
        text-decoration-color: #FFB74D;
        text-underline-offset: 6px;
    }

    .capture-location {
        font-size: 15px;
        font-weight: bold;
        color: #8D6E63;
        text-align: center;
        margin-bottom: 25px;
    }

    .capture-message {
        font-size: 24px;
        line-height: 1.5;
        color: #3E2723;
        white-space: pre-wrap;
        margin-bottom: 50px;
        padding: 10px;
        background: transparent;
        border: none;
        text-align: center;
        height: 450px;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }

    .capture-message-inner {
        width: 100%;
        word-break: break-word;
    }

    .capture-footer {
        margin-top: auto;
        padding-top: 15px;
        border-top: 2px solid #FFCC80;
        text-align: center;
        position: absolute;
        bottom: 30px;
        width: calc(100% - 40px);
    }

    .jai-shri-ram {
        font-size: 24px;
        font-weight: bold;
        color: #FF3D00;
        margin-bottom: 5px;
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

<div class="page-header">
    <h1>📢 सूचना (Press Brief)</h1>
</div>

<?php if ($success): ?>
    <div class="alert alert-success">
        <?php echo $success; ?>
    </div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger">
        <?php echo $error; ?>
    </div>
<?php endif; ?>

<div class="layout-grid">
    <!-- Form Side -->
    <div>
        <div class="card">
            <div class="card-header">सूचना विवरण दर्ज करें</div>
            <form method="POST" action="notice.php">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <input type="hidden" name="notice_id" value="<?php echo htmlspecialchars($noticeId ?? ''); ?>">
                <div class="form-group">
                    <label>विषय (Subject) <span class="required">*</span></label>
                    <input type="text" name="subject" id="inp-subject" class="form-control"
                        placeholder="उदा. विशेष बौद्धिक वर्ग" required
                        value="<?php echo htmlspecialchars($subject ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label>दिनांक (Date)</label>
                    <input type="date" name="notice_date" id="inp-date" class="form-control"
                        value="<?php echo htmlspecialchars($noticeDate ?? date('Y-m-d')); ?>">
                </div>

                <div class="form-group">
                    <label>तिथि (तारीख के नीचे दिखाने के लिए)</label>
                    <input type="text" name="tithi" id="inp-tithi" class="form-control"
                        placeholder="उदा. मार्गशीर्ष शुक्ल पक्ष ..."
                        value="<?php echo htmlspecialchars($tithi ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label>स्थान (Location)</label>
                    <input type="text" name="location" id="inp-location" class="form-control"
                        value="<?php echo htmlspecialchars($location ?? 'घाटकोपर पूर्व, मुंबई'); ?>">
                </div>

                <div class="form-group">
                    <label>संदेश (Message) <span class="required">*</span></label>
                    <textarea name="message" id="inp-message" class="form-control" rows="6" required
                        placeholder="सूचना का विवरण यहाँ लिखें..."><?php echo htmlspecialchars($message ?? ''); ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; margin-bottom: 5px;">
                    <?php echo $noticeId ? '💾 अपडेट करें (Update)' : '💾 सहेजें (Save to Database)'; ?>
                </button>
                <?php if ($noticeId): ?>
                    <a href="../pages/notice.php" class="btn btn-outline"
                        style="width: 100%; text-align: center; display: block;">➕
                        नई सूचना बनाएं (Create New)</a>
                <?php endif; ?>
            </form>
        </div>

        <?php if (!empty($recentNotices)): ?>
            <div class="card" style="margin-top: 20px;">
                <div class="card-header">हाल की सूचनाएं</div>
                <div class="alert alert-info" style="font-size: 0.9em; padding: 10px; margin-bottom: 0;">
                    संपादित/डाउनलोड करने के लिए किसी सूचना पर क्लिक करें।
                </div>
                <div class="list-group" style="padding: 10px 0;">
                    <?php foreach ($recentNotices as $n): ?>
                        <a href="../pages/notice.php?id=<?php echo $n['id']; ?>"
                            class="list-group-item <?php echo ($noticeId == $n['id']) ? 'active' : ''; ?>"
                            style="display: block; padding: 10px 15px; border-bottom: 1px solid #eee; text-decoration: none; color: inherit; background: <?php echo ($noticeId == $n['id']) ? '#FFF3E0' : 'transparent'; ?>">
                            <strong>
                                <?php echo htmlspecialchars($n['subject']); ?>
                            </strong><br>
                            <small style="color: <?php echo ($noticeId == $n['id']) ? '#D84315' : '#666'; ?>;">
                                <?php echo date('d-m-Y', strtotime($n['notice_date'])); ?>
                                <?php if ($n['tithi'])
                                    echo '• ' . htmlspecialchars($n['tithi']); ?>
                            </small>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Preview Side -->
    <div>
        <div class="share-actions" style="justify-content: center; margin-bottom: 15px; gap: 16px;">
            <button id="btn-download" class="btn btn-success">⬇️ डाउनलोड (JPG)</button>
            <button id="btn-share" class="btn btn-whatsapp">📱 व्हाट्सएप शेयर</button>
        </div>

        <div style="overflow-x: auto; padding-bottom: 40px; text-align: center;">
            <div id="capture-area" class="capture-container" style="text-align: left;">

                <div class="tithi-bar">
                    <span id="prev-tithi" style="font-size: 18px;">तिथि यहाँ दिखेगी</span>
                    <div id="prev-date" style="font-size: 14px; color: #8D6E63; margin-top: 5px; font-weight: normal;">
                        (दिनांक)</div>
                </div>

                <div class="capture-header">
                    <?php
                    $logoPath = __DIR__ . '/assets/images/logo.svg';
                    $logoBase64 = '';
                    if (file_exists($logoPath)) {
                        $logoBase64 = 'data:image/svg+xml;base64,' . base64_encode(file_get_contents($logoPath));
                    }
                    ?>
                    <?php if ($logoBase64): ?>
                        <img src="<?php echo $logoBase64; ?>" alt="शाखा" loading="lazy"
                            style="width: 60px; height: 60px; border-radius: 50%; background: #FFF3E0; margin-bottom: 4px;">
                    <?php endif; ?>
                    <div
                        style="display: flex; align-items: center; justify-content: center; gap: 10px; font-size: 20px;">
                        <span class="header-flag">🚩</span>
                        वीरपाण्डिय कट्टभोम्मन शाखा
                        <span class="header-flag">🚩</span>
                    </div>
                </div>

                <div class="capture-body-inner">
                    <div id="prev-subject" class="capture-subject">विषय</div>
                    <div class="capture-location">📍 <span id="prev-location">घाटकोपर पूर्व, मुंबई</span></div>

                    <div class="capture-message">
                        <div id="prev-message" class="capture-message-inner">संदेश यहाँ दिखेगा...</div>
                    </div>

                    <div class="capture-footer">
                        <div class="jai-shri-ram">जय श्री राम 🏹</div>
                    </div>
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

    function updatePreview() {
        const subject = document.getElementById('inp-subject').value;
        const dateInput = document.getElementById('inp-date').value;
        const tithi = document.getElementById('inp-tithi').value;
        const location = document.getElementById('inp-location').value;
        let message = document.getElementById('inp-message').value;

        document.getElementById('prev-subject').innerText = subject || 'विषय';

        let tithiHtml = tithi || ' ';
        const dateHtml = formatHindiDate(dateInput) || '(दिनांक)';

        document.getElementById('prev-tithi').innerHTML = tithiHtml;
        document.getElementById('prev-date').innerText = `(${dateHtml})`;

        document.getElementById('prev-location').innerText = location || 'घाटकोपर पूर्व, मुंबई';

        // Convert newlines to breaks for preview
        message = message.replace(/\n/g, '<br>');

        const msgEl = document.getElementById('prev-message');
        msgEl.innerHTML = message || 'संदेश यहाँ दिखेगा...';

        // Add fluid text sizing
        let fontSize = 26;
        msgEl.style.fontSize = fontSize + 'px';
        const parentEl = msgEl.parentElement;

        while (msgEl.scrollHeight > parentEl.clientHeight && fontSize > 12) {
            fontSize--;
            msgEl.style.fontSize = fontSize + 'px';
        }
    }

    // Bind events
    document.getElementById('inp-subject').addEventListener('input', updatePreview);
    document.getElementById('inp-date').addEventListener('input', updatePreview);
    document.getElementById('inp-tithi').addEventListener('input', updatePreview);
    document.getElementById('inp-location').addEventListener('input', updatePreview);
    document.getElementById('inp-message').addEventListener('input', updatePreview);

    // Initial call
    updatePreview();

    // Generate High-Res Image using html2canvas
    async function generateImage() {
        const el = document.getElementById('capture-area');
        const canvas = await html2canvas(el, {
            scale: 2,
            backgroundColor: '#0F0F14',
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
                    text: 'शाखा सूचना',
                    filename: `shakha_notice_${dStr}.jpg`
                }));
                btn.innerHTML = originalText;
                btn.disabled = false;
                return;
            }

            const a = document.createElement('a');
            a.href = canvas.toDataURL('image/jpeg', 0.95);
            a.download = `shakha_notice_${dStr}.jpg`;
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

            const file = new File([blob], 'shakha_notice.jpg', { type: 'image/jpeg' });
            const subj = document.getElementById('inp-subject').value || 'सूचना';
            const textStr = `शाखा सूचना — ${subj}`;

            if (window.FlutterShareChannel) {
                const dStr = document.getElementById('inp-date').value || 'date';
                const b64 = canvas.toDataURL('image/jpeg', 0.95);
                window.FlutterShareChannel.postMessage(JSON.stringify({
                    image: b64,
                    text: textStr,
                    filename: `shakha_notice_${dStr}.jpg`
                }));
                btn.innerHTML = originalText;
                btn.disabled = false;
                return;
            }

            if (navigator.share && navigator.canShare && navigator.canShare({ files: [file] })) {
                await navigator.share({
                    title: 'शाखा सूचना',
                    text: textStr,
                    files: [file]
                });
            } else {
                alert('आपका ब्राउज़र सीधे इमेज शेयरिंग सपोर्ट नहीं करता। इमेज डाउनलोड हो रही है... उसके बाद व्हाट्सएप पर भेजें।');
                const a = document.createElement('a');
                a.href = URL.createObjectURL(blob);
                const dStr = document.getElementById('inp-date').value || 'date';
                a.download = `shakha_notice_${dStr}.jpg`;
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

<?php require_once '../includes/footer.php'; ?>