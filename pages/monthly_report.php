<?php
/**
 * Monthly Report - मासिक रिपोर्ट
 * Date range selection + downloadable report
 */
$pageTitle = 'मासिक रिपोर्ट';
require_once '../includes/header.php';
require_once '../config/db.php';
requireLogin();

if (isSwayamsevak()) {
    header('Location: swayamsevak_dashboard.php');
    exit;
}

$shakhaId = getCurrentShakhaId();

$hindiMonths = ['जनवरी', 'फ़रवरी', 'मार्च', 'अप्रैल', 'मई', 'जून', 'जुलाई', 'अगस्त', 'सितंबर', 'अक्टूबर', 'नवंबर', 'दिसंबर'];
$hindiDays = ['रविवार', 'सोमवार', 'मंगलवार', 'बुधवार', 'गुरुवार', 'शुक्रवार', 'शनिवार'];

function formatHindiDateReport($dateStr)
{
    global $hindiMonths, $hindiDays;
    $ts = strtotime($dateStr);
    $day = $hindiDays[date('w', $ts)];
    $d = date('j', $ts);
    $m = $hindiMonths[date('n', $ts) - 1];
    $y = date('Y', $ts);
    return "$day, $d $m $y";
}

// Default: current month
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-t');
$hasData = false;

$totalDays = 0;
$dailyAttendance = [];
$avgAttendance = 0;

if ($startDate && $endDate) {
    // Total shakha days
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM daily_records WHERE shakha_id = ? AND record_date BETWEEN ? AND ?");
    $stmt->execute([$shakhaId, $startDate, $endDate]);
    $totalDays = $stmt->fetchColumn();

    // Daily attendance
    $stmt = $pdo->prepare("SELECT dr.record_date, 
        (SELECT COUNT(*) FROM attendance a WHERE a.daily_record_id = dr.id AND a.is_present = 1) as present_count,
        (SELECT COUNT(*) FROM attendance a JOIN swayamsevaks s ON a.swayamsevak_id = s.id WHERE a.daily_record_id = dr.id AND a.is_present = 1 AND COALESCE(s.category, 'Tarun') = 'Baal') as baal_count,
        (SELECT COUNT(*) FROM attendance a JOIN swayamsevaks s ON a.swayamsevak_id = s.id WHERE a.daily_record_id = dr.id AND a.is_present = 1 AND COALESCE(s.category, 'Tarun') = 'Tarun') as tarun_count,
        (SELECT COUNT(*) FROM attendance a JOIN swayamsevaks s ON a.swayamsevak_id = s.id WHERE a.daily_record_id = dr.id AND a.is_present = 1 AND COALESCE(s.category, 'Tarun') = 'Praudh') as praudh_count,
        (SELECT COUNT(*) FROM attendance a JOIN swayamsevaks s ON a.swayamsevak_id = s.id WHERE a.daily_record_id = dr.id AND a.is_present = 1 AND COALESCE(s.category, 'Tarun') = 'Abhyagat') as abhyagat_count
        FROM daily_records dr
        WHERE dr.shakha_id = ? AND dr.record_date BETWEEN ? AND ?
        ORDER BY dr.record_date");
    $stmt->execute([$shakhaId, $startDate, $endDate]);
    $dailyAttendance = $stmt->fetchAll();

    if (count($dailyAttendance) > 0) {
        $hasData = true;
        $totalPresent = 0;
        $totalBaal = 0; $totalTarun = 0; $totalPraudh = 0; $totalAbhyagat = 0;
        foreach ($dailyAttendance as $row) {
            $totalPresent += $row['present_count'];
            $totalBaal += $row['baal_count'];
            $totalTarun += $row['tarun_count'];
            $totalPraudh += $row['praudh_count'];
            $totalAbhyagat += $row['abhyagat_count'];
        }
        $avgAttendance = round($totalPresent / count($dailyAttendance), 1);
        $avgBaal = round($totalBaal / count($dailyAttendance), 1);
        $avgTarun = round($totalTarun / count($dailyAttendance), 1);
        $avgPraudh = round($totalPraudh / count($dailyAttendance), 1);
        $avgAbhyagat = round($totalAbhyagat / count($dailyAttendance), 1);
    }
}

// Get shakha name
$shakhaName = 'शाखा';
$stmt = $pdo->prepare("SELECT name FROM shakhas WHERE id = ?");
$stmt->execute([$shakhaId]);
$shakhaRow = $stmt->fetch();
if ($shakhaRow) {
    $shakhaName = $shakhaRow['name'];
}

// Logo for capture
$logoPath = __DIR__ . '/assets/images/logo.svg';
$logoBase64 = '';
if (file_exists($logoPath)) {
    $logoBase64 = 'data:image/svg+xml;base64,' . base64_encode(file_get_contents($logoPath));
}
?>

<!-- html2canvas plugin -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

<div class="page-header">
    <h1>📊 मासिक रिपोर्ट</h1>
</div>

<!-- Date Range Selection -->
<div class="card">
    <div class="card-header">📅 तिथि चयन</div>
    <form method="GET" action="monthly_report.php"
        style="display: flex; flex-wrap: wrap; gap: 16px; align-items: flex-end;">
        <div class="form-group" style="flex: 1; min-width: 200px;">
            <label class="form-label">प्रारंभ तिथि</label>
            <input type="date" name="start_date" class="form-control"
                value="<?php echo htmlspecialchars($startDate); ?>" required>
        </div>
        <div class="form-group" style="flex: 1; min-width: 200px;">
            <label class="form-label">अंतिम तिथि</label>
            <input type="date" name="end_date" class="form-control" value="<?php echo htmlspecialchars($endDate); ?>"
                required>
        </div>
        <div class="form-group" style="min-width: 150px;">
            <button type="submit" class="btn btn-primary" style="width: 100%;">🔍 रिपोर्ट देखें</button>
        </div>
    </form>
</div>

<?php if ($hasData): ?>
    <!-- Download Buttons -->
    <div class="card" style="text-align: center;">
        <button id="btn-download-report" class="btn btn-success" style="font-size: 1.1rem; padding: 12px 24px;">
            ⬇️ रिपोर्ट डाउनलोड करें (JPG)
        </button>
        <button id="btn-share-report" class="btn btn-whatsapp"
            style="font-size: 1.1rem; padding: 12px 24px; margin-left: 12px;">
            📱 व्हाट्सएप पर भेजें
        </button>
    </div>

    <!-- Capturable Report Area -->
    <div style="overflow-x: auto; padding-bottom: 40px;">
        <div id="report-capture-area" style="
            width: 100%; max-width: 520px; margin: 0 auto;
            background: #FFF9F2; color: #4A1C00; 
            font-family: 'Noto Sans Devanagari', sans-serif;
            padding: 0; border: 4px solid #FF6B00; box-shadow: 0 4px 20px rgba(0,0,0,0.15);">

            <!-- Report Header -->
            <div style="background: #FF6B00; color: #FFF; padding: 20px; text-align: center;">
                <?php if ($logoBase64): ?>
                    <img src="<?php echo $logoBase64; ?>" alt="शाखा" loading="lazy"
                        style="width: 55px; height: 55px; border-radius: 50%; background: #FFF3E0; margin-bottom: 8px;">
                <?php endif; ?>
                <div style="font-size: 22px; font-weight: bold;">🚩
                    <?php echo htmlspecialchars($shakhaName); ?> 🚩
                </div>
                <div style="font-size: 14px; opacity: 0.9; margin-top: 4px;">मासिक रिपोर्ट</div>
            </div>

            <!-- Date Range -->
            <div
                style="background: #FFE0B2; padding: 12px; text-align: center; font-weight: bold; color: #D84315; font-size: 16px; border-bottom: 2px solid #FFB74D;">
                <?php echo formatHindiDateReport($startDate); ?> —
                <?php echo formatHindiDateReport($endDate); ?>
            </div>

            <div style="padding: 20px;">
                <!-- Summary Stats -->
                <div style="display: flex; gap: 12px; margin-bottom: 20px;">
                    <div
                        style="flex: 1; background: #F1F8E9; border: 1px solid #C5E1A5; border-radius: 10px; padding: 16px; text-align: center;">
                        <div style="font-size: 32px; font-weight: bold; color: #2E7D32;">
                            <?php echo $totalDays; ?>
                        </div>
                        <div style="font-size: 14px; color: #5D4037;">कुल शाखा दिवस</div>
                    </div>
                    <div
                        style="flex: 1; background: #FFF3E0; border: 1px solid #FFB74D; border-radius: 10px; padding: 16px; text-align: center;">
                        <div style="font-size: 32px; font-weight: bold; color: #E64A19;">
                            <?php echo $avgAttendance; ?>
                        </div>
                        <div style="font-size: 14px; color: #5D4037;">औसत उपस्थिति</div>
                        <div style="font-size: 12px; margin-top: 6px; color: #D84315; font-weight: 600; line-height: 1.4;">
                            बाल: <?php echo $avgBaal; ?>, तरुण: <?php echo $avgTarun; ?><br>
                            प्रौढ़: <?php echo $avgPraudh; ?>, अभ्यागत: <?php echo $avgAbhyagat; ?>
                        </div>
                    </div>
                </div>



                <!-- Footer -->
                <div style="margin-top: 20px; padding-top: 15px; border-top: 2px solid #FFCC80; text-align: center;">
                    <div style="font-size: 22px; font-weight: bold; color: #FF3D00;">जय श्री राम 🏹</div>
                </div>
            </div>
        </div>
    </div>

<?php elseif ($startDate && $endDate): ?>
    <div class="card">
        <div class="empty-state">
            <div class="icon">📭</div>
            <p>चयनित तिथि सीमा में कोई रिकॉर्ड नहीं मिला।</p>
        </div>
    </div>
<?php endif; ?>

<script>
    // Download Monthly Report
    async function generateReportImage() {
        const el = document.getElementById('report-capture-area');
        const canvas = await html2canvas(el, {
            scale: 2,
            backgroundColor: '#FFF9F2',
            useCORS: true,
            logging: false
        });
        return canvas;
    }

    const dlBtn = document.getElementById('btn-download-report');
    if (dlBtn) {
        dlBtn.addEventListener('click', async () => {
            const originalText = dlBtn.innerHTML;
            dlBtn.innerHTML = '⏳ कृपया प्रतीक्षा करें...';
            dlBtn.disabled = true;
            try {
                const canvas = await generateReportImage();

                if (window.FlutterShareChannel) {
                    const b64 = canvas.toDataURL('image/jpeg', 0.95);
                    window.FlutterShareChannel.postMessage(JSON.stringify({
                        image: b64,
                        text: 'शाखा मासिक रिपोर्ट',
                        filename: 'shakha_monthly_report_<?php echo $startDate; ?>_to_<?php echo $endDate; ?>.jpg'
                    }));
                    dlBtn.innerHTML = originalText;
                    dlBtn.disabled = false;
                    return;
                }

                const a = document.createElement('a');
                a.href = canvas.toDataURL('image/jpeg', 0.95);
                a.download = 'shakha_monthly_report_<?php echo $startDate; ?>_to_<?php echo $endDate; ?>.jpg';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
            } catch (e) {
                console.error(e);
                alert('रिपोर्ट बनाने में तकनीकी त्रुटि हुई।');
            }
            dlBtn.innerHTML = originalText;
            dlBtn.disabled = false;
        });
    }

    // WhatsApp Share
    const shareBtn = document.getElementById('btn-share-report');
    if (shareBtn) {
        shareBtn.addEventListener('click', async () => {
            const originalText = shareBtn.innerHTML;
            shareBtn.innerHTML = '⏳ रिपोर्ट तैयार हो रही है...';
            shareBtn.disabled = true;
            try {
                const canvas = await generateReportImage();
                const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/jpeg', 0.95));
                const file = new File([blob], 'shakha_monthly_report.jpg', { type: 'image/jpeg' });
                const textStr = 'शाखा मासिक रिपोर्ट — <?php echo date("d/m/Y", strtotime($startDate)); ?> से <?php echo date("d/m/Y", strtotime($endDate)); ?>';

                if (window.FlutterShareChannel) {
                    const b64 = canvas.toDataURL('image/jpeg', 0.95);
                    window.FlutterShareChannel.postMessage(JSON.stringify({
                        image: b64,
                        text: textStr,
                        filename: 'shakha_monthly_report_<?php echo $startDate; ?>_to_<?php echo $endDate; ?>.jpg'
                    }));
                    shareBtn.innerHTML = originalText;
                    shareBtn.disabled = false;
                    return;
                }

                if (navigator.share && navigator.canShare && navigator.canShare({ files: [file] })) {
                    await navigator.share({
                        title: 'शाखा मासिक रिपोर्ट',
                        text: textStr,
                        files: [file]
                    });
                } else {
                    const a = document.createElement('a');
                    a.href = URL.createObjectURL(blob);
                    a.download = 'shakha_monthly_report.jpg';
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
            shareBtn.innerHTML = originalText;
            shareBtn.disabled = false;
        });
    }
</script>

<?php require_once '../includes/footer.php'; ?>