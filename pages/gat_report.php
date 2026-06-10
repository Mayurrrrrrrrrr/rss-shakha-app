<?php
require_once '../includes/auth.php';
/**
 * Gat-wise Attendance Report - गटवार उपस्थिति रिपोर्ट
 */
$pageTitle = 'गटवार उपस्थिति रिपोर्ट';
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
$grouped = [];

if ($startDate && $endDate) {
    // Total shakha days
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM daily_records WHERE shakha_id = ? AND record_date BETWEEN ? AND ?");
    $stmt->execute([$shakhaId, $startDate, $endDate]);
    $totalDays = intval($stmt->fetchColumn());

    // Fetch all active swayamsevaks with their attendance count for this period
    $stmt = $pdo->prepare("
        SELECT s.*, 
        (
            SELECT COUNT(*) 
            FROM attendance a 
            JOIN daily_records dr ON a.daily_record_id = dr.id 
            WHERE a.swayamsevak_id = s.id 
              AND a.is_present = 1 
              AND dr.record_date BETWEEN ? AND ?
        ) as present_days
        FROM swayamsevaks s
        WHERE s.is_active = 1 AND s.shakha_id = ?
        ORDER BY COALESCE(NULLIF(s.gat, ''), 'zzzzzzzz') ASC, s.is_gat_nayak DESC, s.name ASC
    ");
    $stmt->execute([$startDate, $endDate, $shakhaId]);
    $swayamsevaks = $stmt->fetchAll();

    if (count($swayamsevaks) > 0) {
        $hasData = true;
        foreach ($swayamsevaks as $s) {
            $gatName = trim($s['gat'] ?? '');
            if ($gatName === '') {
                $gatKey = 'बिना गट के (Unassigned)';
            } else {
                $gatKey = $gatName;
            }

            if (!isset($grouped[$gatKey])) {
                $grouped[$gatKey] = [
                    'nayak' => null,
                    'members' => []
                ];
            }

            // Put Gat Nayak at the top of the group if they belong to a Gat
            if ($s['is_gat_nayak'] && $gatName !== '') {
                $grouped[$gatKey]['nayak'] = $s;
            } else {
                $grouped[$gatKey]['members'][] = $s;
            }
        }
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
$logoPath = __DIR__ . '/../assets/images/logo.png';
$logoBase64 = '';
if (file_exists($logoPath)) {
    $mime = mime_content_type($logoPath);
    $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($logoPath));
}
?>

<!-- html2canvas plugin -->
<script src="../assets/js/html2canvas.min.js"></script>

<div class="page-header">
    <h1>👥 गटवार उपस्थिति रिपोर्ट</h1>
</div>

<!-- Date Range Selection -->
<div class="card">
    <div class="card-header">📅 तिथि चयन</div>
    <form method="GET" action="gat_report.php"
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
                <div style="font-size: 14px; opacity: 0.9; margin-top: 4px;">गटवार उपस्थिति रिपोर्ट (Gat Attendance)</div>
            </div>

            <!-- Date Range -->
            <div
                style="background: #FFE0B2; padding: 12px; text-align: center; font-weight: bold; color: #D84315; font-size: 16px; border-bottom: 2px solid #FFB74D;">
                <?php echo formatHindiDateReport($startDate); ?> —
                <?php echo formatHindiDateReport($endDate); ?>
            </div>

            <div style="padding: 20px;">
                <!-- Total Days Info -->
                <div style="background: #F1F8E9; border: 1px solid #C5E1A5; border-radius: 10px; padding: 12px; text-align: center; margin-bottom: 20px; font-weight: 500;">
                    कुल शाखा दिवस (Total Shakha Days): <strong style="color: #2E7D32; font-size: 1.25rem;"><?php echo $totalDays; ?></strong>
                </div>

                <!-- Gat Sections -->
                <?php foreach ($grouped as $gatName => $group): ?>
                    <div style="background: #FFF; border: 1px solid #FFCC80; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); overflow: hidden;">
                        
                        <!-- Gat Header -->
                        <div style="background: #FFF3E0; border-bottom: 2px solid #FFB74D; padding: 10px 15px; display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-size: 17px; font-weight: bold; color: #E64A19;">👤 <?php echo htmlspecialchars($gatName); ?></span>
                            <span style="font-size: 13px; color: #D84315; font-weight: 500;">कुल सदस्य: <?php echo ( ($group['nayak'] ? 1 : 0) + count($group['members']) ); ?></span>
                        </div>

                        <div style="padding: 10px 15px;">
                            <!-- Leader (Gat Nayak) -->
                            <?php if ($group['nayak']): 
                                $n = $group['nayak'];
                                $pct = $totalDays > 0 ? round(($n['present_days'] / $totalDays) * 100) : 0;
                            ?>
                                <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px dashed #FFCC80; background: #FFFDE7;">
                                    <div>
                                        <strong style="font-size: 15px; color: #4A1C00;"><?php echo htmlspecialchars($n['name']); ?></strong>
                                        <span style="background: #E65100; color: #FFF; font-size: 11px; padding: 1px 5px; border-radius: 3px; margin-left: 6px; font-weight: bold;">गट नायक</span>
                                    </div>
                                    <div style="text-align: right;">
                                        <span style="font-weight: 600; color: #2E7D32;"><?php echo $n['present_days']; ?> / <?php echo $totalDays; ?> दिन</span>
                                        <span style="font-size: 12px; color: #757575; margin-left: 5px;">(<?php echo $pct; ?>%)</span>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Members -->
                            <?php if (!empty($group['members'])): ?>
                                <?php foreach ($group['members'] as $m): 
                                    $pct = $totalDays > 0 ? round(($m['present_days'] / $totalDays) * 100) : 0;
                                ?>
                                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #EEEEEE;">
                                        <div style="font-size: 14px; color: #5D4037;">
                                            <?php echo htmlspecialchars($m['name']); ?>
                                        </div>
                                        <div style="text-align: right; font-size: 14px;">
                                            <span style="font-weight: 500; color: #37474F;"><?php echo $m['present_days']; ?> / <?php echo $totalDays; ?> दिन</span>
                                            <span style="font-size: 12px; color: #9E9E9E; margin-left: 5px;">(<?php echo $pct; ?>%)</span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php elseif (!$group['nayak']): ?>
                                <div style="text-align: center; color: #9E9E9E; padding: 10px; font-size: 14px;">गट में कोई सदस्य नहीं है।</div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

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
            <p>चयनित तिथि सीमा में कोई सदस्य रिकॉर्ड नहीं मिला।</p>
        </div>
    </div>
<?php endif; ?>

<script>
    // Download Report Image
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
                        text: 'गटवार उपस्थिति रिपोर्ट',
                        filename: 'gat_attendance_report_<?php echo $startDate; ?>_to_<?php echo $endDate; ?>.jpg'
                    }));
                    dlBtn.innerHTML = originalText;
                    dlBtn.disabled = false;
                    return;
                }

                const a = document.createElement('a');
                a.href = canvas.toDataURL('image/jpeg', 0.95);
                a.download = 'gat_attendance_report_<?php echo $startDate; ?>_to_<?php echo $endDate; ?>.jpg';
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
                const file = new File([blob], 'gat_attendance_report.jpg', { type: 'image/jpeg' });
                const textStr = 'गटवार उपस्थिति रिपोर्ट — <?php echo date("d/m/Y", strtotime($startDate)); ?> से <?php echo date("d/m/Y", strtotime($endDate)); ?>';

                if (window.FlutterShareChannel) {
                    const b64 = canvas.toDataURL('image/jpeg', 0.95);
                    window.FlutterShareChannel.postMessage(JSON.stringify({
                        image: b64,
                        text: textStr,
                        filename: 'gat_attendance_report_<?php echo $startDate; ?>_to_<?php echo $endDate; ?>.jpg'
                    }));
                    shareBtn.innerHTML = originalText;
                    shareBtn.disabled = false;
                    return;
                }

                if (navigator.share && navigator.canShare && navigator.canShare({ files: [file] })) {
                    await navigator.share({
                        title: 'गटवार उपस्थिति रिपोर्ट',
                        text: textStr,
                        files: [file]
                    });
                } else {
                    const a = document.createElement('a');
                    a.href = URL.createObjectURL(blob);
                    a.download = 'gat_attendance_report.jpg';
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
