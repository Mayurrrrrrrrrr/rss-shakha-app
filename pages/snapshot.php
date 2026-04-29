<?php
require_once '../includes/auth.php';
/**
 * Snapshot Generator - स्नैपशॉट जनरेटर
 * Generates report using HTML & html2canvas for accurate Hindi & Emoji rendering
 */
require_once '../config/db.php';
requireLogin();

$recordId = $_GET['id'] ?? null;
if (!$recordId) {
    header('Location: records_list.php');
    exit;
}

$shakhaId = getCurrentShakhaId();
if (isAdmin()) {
    $stmt = $pdo->prepare("SELECT * FROM daily_records WHERE id = ?");
    $stmt->execute([$recordId]);
} else {
    $stmt = $pdo->prepare("SELECT * FROM daily_records WHERE id = ? AND shakha_id = ?");
    $stmt->execute([$recordId, $shakhaId]);
}
$record = $stmt->fetch();

if (!$record) {
    header('Location: records_list.php');
    exit;
}

// Get Shakha details for logo and name
$stmt = $pdo->prepare("SELECT name, logo FROM shakhas WHERE id = ?");
$stmt->execute([$record['shakha_id'] ?? $shakhaId]);
$shakhaDetails = $stmt->fetch();
$shakhaName = $shakhaDetails ? $shakhaDetails['name'] : 'संघस्थान';
$shakhaLogo = $shakhaDetails ? $shakhaDetails['logo'] : null;

// Get attendance
$stmt = $pdo->prepare("SELECT a.*, s.name as swayamsevak_name, s.category as swayamsevak_category 
    FROM attendance a 
    JOIN swayamsevaks s ON a.swayamsevak_id = s.id 
    WHERE a.daily_record_id = ? 
    ORDER BY a.is_present DESC, s.name");
$stmt->execute([$recordId]);
$attendance = $stmt->fetchAll();

// Get activities
$stmt = $pdo->prepare("SELECT da.*, act.name as activity_name, s.name as conductor_name 
    FROM daily_activities da 
    JOIN activities act ON da.activity_id = act.id 
    LEFT JOIN swayamsevaks s ON da.conducted_by = s.id 
    WHERE da.daily_record_id = ? 
    ORDER BY act.sort_order, act.id");
$stmt->execute([$recordId]);
$dailyActivities = $stmt->fetchAll();

$hindiMonths = ['जनवरी', 'फ़रवरी', 'मार्च', 'अप्रैल', 'मई', 'जून', 'जुलाई', 'अगस्त', 'सितंबर', 'अक्टूबर', 'नवंबर', 'दिसंबर'];
$hindiDays = ['रविवार', 'सोमवार', 'मंगलवार', 'बुधवार', 'गुरुवार', 'शुक्रवार', 'शनिवार'];

$ts = strtotime($record['record_date']);
$formattedDate = $hindiDays[date('w', $ts)] . ', ' . date('j', $ts) . ' ' . $hindiMonths[date('n', $ts) - 1] . ' ' . date('Y', $ts);

$presentCount = 0;
$catCounts = ['Baal' => 0, 'Tarun' => 0, 'Praudh' => 0, 'Abhyagat' => 0];
foreach ($attendance as $a) {
    if ($a['is_present']) {
        $presentCount++;
        $cat = $a['swayamsevak_category'] ?? 'Tarun';
        if (isset($catCounts[$cat])) {
            $catCounts[$cat]++;
        } else {
            $catCounts['Tarun']++;
        }
    }
}
$absentCount = count($attendance) - $presentCount;
$doneCount = 0;
foreach ($dailyActivities as $a) {
    if ($a['is_done'])
        $doneCount++;
}

$pageTitle = 'स्नैपशॉट';
require_once '../includes/header.php';
?>

<!-- html2canvas plugin -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

<style>
    /* Scoped styles for the capture area to ensure it looks good when captured */
    .capture-container {
        width: 100%;
        max-width: 480px;
        min-height: 853px;
        /* Ensure 16:9 minimum size, but allow expansion */
        margin: 0 auto;
        background: #FFF9F2;
        /* Light cream background */
        color: #4A1C00;
        font-family: 'Noto Sans Devanagari', sans-serif;
        padding: 0;
        box-sizing: border-box;
        border-radius: 0;
        /* Sharp corners for border art */
        overflow: hidden;
        position: relative;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        border: 4px solid #FF6B00;
        /* Beautiful orange border */
    }

    /* Corner Arts using pseudo-elements */
    .capture-container::before,
    .capture-container::after {
        content: '❁';
        /* Beautiful corner flower/mandala */
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
    }

    .header-flag {
        font-size: 28px;
    }

    .capture-date {
        font-size: 18px;
        font-weight: 700;
        color: #D84315;
        text-align: center;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 2px dashed #FFB74D;
    }

    .capture-attendance-summary {
        display: flex;
        justify-content: space-between;
        background: #FFE0B2;
        border: 1px solid #FFB74D;
        border-radius: 8px;
        padding: 10px 15px;
        margin-bottom: 15px;
        font-size: 16px;
        font-weight: bold;
        color: #BF360C;
    }

    .capture-section-title {
        font-size: 18px;
        font-weight: 700;
        color: #E64A19;
        margin-bottom: 10px;
        margin-top: 15px;
        display: flex;
        align-items: center;
        gap: 8px;
        border-left: 4px solid #FF6B00;
        padding-left: 10px;
    }

    .capture-item {
        font-size: 15px;
        padding: 6px 10px;
        margin-bottom: 4px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: #FFFFFF;
        border: 1px solid #FFE0B2;
    }

    .capture-item.done {
        background: #F1F8E9;
        border-color: #C5E1A5;
    }

    .capture-item .item-main {
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 600;
    }

    .capture-item .conductor {
        color: #5D4037;
        font-size: 14px;
        font-weight: normal;
        background: #FFF3E0;
        padding: 2px 8px;
        border-radius: 12px;
    }

    .custom-msg {
        background: #FFF3E0;
        border: 1px solid #FFB74D;
        padding: 10px;
        border-radius: 8px;
        color: #4E342E;
        line-height: 1.4;
        font-size: 15px;
        font-style: italic;
    }

    .capture-footer {
        margin-top: 20px;
        padding-top: 15px;
        border-top: 2px solid #FFCC80;
        text-align: center;
    }

    .jai-shri-ram {
        font-size: 24px;
        font-weight: bold;
        color: #FF3D00;
        margin-bottom: 5px;
    }

    .footer-meta {
        font-size: 13px;
        color: #8D6E63;
    }
</style>

<div class="page-header">
    <h1>📸 स्नैपशॉट</h1>
    <a href="../pages/record_detail.php?id=<?php echo $recordId; ?>" class="btn btn-outline btn-sm">◀ वापस जाएँ</a>
</div>

<div class="card">
    <div class="card-header">रिपोर्ट स्नैपशॉट प्रीव्यू</div>

    <div class="alert alert-info"
        style="text-align: center; background: rgba(66, 165, 245, 0.1); border: 1px solid rgba(66, 165, 245, 0.3);">
        ℹ️ <strong>नोट:</strong> बेहतर क्वालिटी के लिए स्नैपशॉट अब सीधे आपके डिवाइस पर बनता है, जिससे हिंदी और एमुजी
        एकदम सही दिखते हैं।
    </div>

    <div class="share-actions" style="justify-content: center; margin-bottom: 30px; gap: 16px;">
        <button id="btn-download" class="btn btn-success" style="font-size: 1.1rem; padding: 12px 24px;">⬇️ डाउनलोड करें
            (JPG)</button>
        <button id="btn-share" class="btn btn-whatsapp" style="font-size: 1.1rem; padding: 12px 24px;">📱 व्हाट्सएप पर
            भेजें</button>
    </div>

    <div style="overflow-x: auto; padding-bottom: 40px;">
        <!-- Area to capture -->
        <div id="capture-area" class="capture-container"
            style="transform-origin: top left; transform: scale(min(1, calc((100vw - 60px) / 480))); width: 480px; margin: 0 auto;">
            <?php if (!empty($record['yugabdh']) || !empty($record['tithi'])): ?>
                <div
                    style="background: #FFE0B2; color: #D84315; text-align: center; padding: 12px 20px; font-weight: bold; font-size: 16px; border-bottom: 2px solid #FFB74D; line-height: 1.4;">
                    <?php if (!empty($record['yugabdh']))
                        echo "युगाब्द: " . htmlspecialchars($record['yugabdh']) . " | "; ?>
                    <?php if (!empty($record['vikram_samvat']))
                        echo "विक्रम संवत्: " . htmlspecialchars($record['vikram_samvat']) . "<br>"; ?>
                    <span style="font-size: 18px;">
                        <?php
                        $tithiStr = [];
                        if (!empty($record['hindi_month']))
                            $tithiStr[] = $record['hindi_month'];
                        if (!empty($record['paksh']))
                            $tithiStr[] = $record['paksh'];
                        if (!empty($record['tithi']))
                            $tithiStr[] = $record['tithi'];
                        echo htmlspecialchars(implode(' ', $tithiStr));
                        ?>
                    </span>
                    <div style="font-size: 14px; color: #8D6E63; margin-top: 5px; font-weight: normal;">
                        (<?php echo $formattedDate; ?>)</div>
                </div>
            <?php else: ?>
                <div
                    style="background: #FFE0B2; color: #D84315; text-align: center; padding: 10px; font-weight: bold; border-bottom: 2px solid #FFB74D;">
                    <?php echo $formattedDate; ?>
                </div>
            <?php endif; ?>

            <div class="capture-header" style="flex-direction: column; gap: 4px; padding: 15px 20px;">
                <?php
                if ($shakhaLogo && file_exists("../" . $shakhaLogo)) {
                    $logoPath = "../" . $shakhaLogo;
                } else {
                    $logoPath = dirname(__DIR__) . '/assets/images/logo.svg';
                }
                $logoBase64 = '';
                if (file_exists($logoPath)) {
                    $mime = mime_content_type($logoPath);
                    $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($logoPath));
                }
                ?>
                <?php
                // Pre-encode static icons to Base64 to avoid html2canvas loading/CORS issues
                $flagIconPath = dirname(__DIR__) . '/assets/images/flag_icon.png';
                $flagBase64 = '';
                if (file_exists($flagIconPath)) {
                    $flagBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($flagIconPath));
                }
                ?>
                <?php if ($logoBase64): ?>
                    <img src="<?php echo $logoBase64; ?>" alt="शाखा" 
                        style="width: 60px; height: 60px; border-radius: 50%; background: #FFF3E0; margin-bottom: 4px; object-fit: cover;">
                <?php endif; ?>
                <div style="display: flex; align-items: center; justify-content: center; gap: 10px; font-size: 20px;">
                    <?php if ($flagBase64): ?>
                        <img src="<?php echo $flagBase64; ?>" style="height: 1.2em; width: auto;" alt="🚩">
                    <?php else: ?>
                        🚩
                    <?php endif; ?>
                    <?php echo htmlspecialchars($shakhaName); ?>
                    <?php if ($flagBase64): ?>
                        <img src="<?php echo $flagBase64; ?>" style="height: 1.2em; width: auto;" alt="🚩">
                    <?php else: ?>
                        🚩
                    <?php endif; ?>
                </div>
                <div style="font-size: 16px; font-weight: normal; opacity: 0.9;">घाटकोपर पूर्व, मुंबई</div>
            </div>

            <div class="capture-body-inner">

                <div class="capture-attendance-summary" style="justify-content: center; flex-direction: column; text-align: center;">
                    <div style="font-size: 18px; margin-bottom: 5px;">✅ कुल उपस्थित: <?php echo $presentCount; ?></div>
                    <div style="font-size: 14px; color: #E65100;">
                        बाल-<?php echo $catCounts['Baal']; ?>, 
                        तरुण-<?php echo $catCounts['Tarun']; ?>, 
                        प्रौढ़-<?php echo $catCounts['Praudh']; ?>, 
                        अभ्यागत-<?php echo $catCounts['Abhyagat']; ?>
                    </div>
                </div>

                <div class="capture-section-title">📋 दैनिक गतिविधियाँ</div>
                <?php foreach ($dailyActivities as $da): ?>
                    <?php if (!$da['is_done'])
                        continue; ?>
                    <div class="capture-item <?php echo $da['is_done'] ? 'done' : ''; ?>">
                        <div class="item-main">
                            <span>✅</span> <span
                                style="color: #2E7D32;"><?php echo htmlspecialchars($da['activity_name']); ?></span>
                        </div>

                        <?php if ($da['conductor_name']): ?>
                            <span class="conductor">👤 <?php echo htmlspecialchars($da['conductor_name']); ?></span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

                <?php if (!empty($record['custom_message'])): ?>
                    <div class="capture-section-title">💬 विशेष संदेश</div>
                    <div class="custom-msg">
                        <?php echo nl2br(htmlspecialchars($record['custom_message'])); ?>
                    </div>
                <?php endif; ?>

                <div class="capture-footer">
                    <div class="jai-shri-ram">जय श्री राम 🏹</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Generate High-Res Image using html2canvas
    async function generateImage() {
        const el = document.getElementById('capture-area');
        
        // Use lower scale for mobile to prevent memory issues, 2x for desktop
        const isMobile = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent);
        const captureScale = isMobile ? 1.5 : 2;

        const canvas = await html2canvas(el, {
            scale: captureScale,
            backgroundColor: '#FFF9F2',
            useCORS: true,
            allowTaint: true,
            logging: false,
            imageTimeout: 10000
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

            if (window.FlutterShareChannel) {
                const b64 = canvas.toDataURL('image/jpeg', 0.9);
                window.FlutterShareChannel.postMessage(JSON.stringify({
                    image: b64,
                    text: 'शाखा रिपोर्ट',
                    filename: 'shakha_report_<?php echo $record['record_date']; ?>.jpg'
                }));
                btn.innerHTML = originalText;
                btn.disabled = false;
                return;
            }

            const a = document.createElement('a');
            a.href = canvas.toDataURL('image/jpeg', 0.9);
            a.download = 'shakha_report_<?php echo $record['record_date']; ?>.jpg';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        } catch (e) {
            console.error('Download Error:', e);
            alert('स्नैपशॉट बनाने में तकनीकी त्रुटि हुई। कृपया दोबारा प्रयास करें।');
        }

        btn.innerHTML = originalText;
        btn.disabled = false;
    });

    // WhatsApp Share Button
    document.getElementById('btn-share').addEventListener('click', async () => {
        const btn = document.getElementById('btn-share');
        const originalText = btn.innerHTML;
        btn.innerHTML = '⏳ स्नैपशॉट तैयार हो रहा है...';
        btn.disabled = true;

        try {
            const canvas = await generateImage();
            const textStr = 'शाखा दैनिक रिपोर्ट — <?php echo preg_replace('/\s+/', ' ', $formattedDate); ?>';

            // Flutter / Mobile App Bridge
            if (window.FlutterShareChannel) {
                try {
                    const b64 = canvas.toDataURL('image/jpeg', 0.85); // Lower quality for faster bridge transfer
                    window.FlutterShareChannel.postMessage(JSON.stringify({
                        image: b64,
                        text: textStr,
                        filename: 'shakha_report_<?php echo $record['record_date']; ?>.jpg'
                    }));
                } catch (b64err) {
                    console.error('Base64 Error:', b64err);
                    throw b64err;
                }
                btn.innerHTML = originalText;
                btn.disabled = false;
                return;
            }

            // Web Share API
            const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/jpeg', 0.9));
            const file = new File([blob], 'shakha_report.jpg', { type: 'image/jpeg' });

            const canShareFiles = navigator.canShare && typeof navigator.canShare === 'function' && navigator.canShare({ files: [file] });

            if (navigator.share && canShareFiles) {
                await navigator.share({
                    title: 'शाखा रिपोर्ट',
                    text: textStr,
                    files: [file]
                });
            } else {
                // Fallback for desktop/unsupported browsers
                alert('आपका ब्राउज़र सीधे इमेज शेयरिंग सपोर्ट नहीं करता। इमेज डाउनलोड हो रही है, उसे शेयर करें।');
                const a = document.createElement('a');
                a.href = URL.createObjectURL(blob);
                a.download = 'shakha_report_<?php echo $record['record_date']; ?>.jpg';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);

                // Open WhatsApp Web with pre-filled text
                window.open('https://wa.me/?text=' + encodeURIComponent(textStr), '_blank');
            }
        } catch (e) {
            if (e.name !== 'AbortError') {
                console.error('Share Error:', e);
                alert('शेयर करने में तकनीकी त्रुटि हुई। कृपया स्नैपशॉट डाउनलोड करके शेयर करें।');
            }
        }

        btn.innerHTML = originalText;
        btn.disabled = false;
    });
</script>

<?php require_once '../includes/footer.php'; ?>