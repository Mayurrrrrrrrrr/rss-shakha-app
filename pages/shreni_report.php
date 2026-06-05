<?php
require_once '../includes/auth.php';
/**
 * Shreni-wise Attendance Report - श्रेणीवार उपस्थिति रिपोर्ट
 */
$pageTitle = 'श्रेणीवार उपस्थिति रिपोर्ट';
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

// Default values
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-t');
$category = $_GET['category'] ?? ''; // Empty means 'All'

$hasData = false;
$totalDays = 0;
$swayamsevaks = [];

if ($startDate && $endDate) {
    // Total shakha days
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM daily_records WHERE shakha_id = ? AND record_date BETWEEN ? AND ?");
    $stmt->execute([$shakhaId, $startDate, $endDate]);
    $totalDays = intval($stmt->fetchColumn());

    // Build query to fetch attendance with category filter
    $query = "
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
    ";
    
    $params = [$startDate, $endDate, $shakhaId];
    
    if ($category !== '') {
        $query .= " AND s.category = ?";
        $params[] = $category;
    }
    
    $query .= " ORDER BY present_days DESC, s.name ASC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $swayamsevaks = $stmt->fetchAll();
    
    if (count($swayamsevaks) > 0) {
        $hasData = true;
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
$logoPath = __DIR__ . '/../assets/images/logo.svg';
$logoBase64 = '';
if (file_exists($logoPath)) {
    $logoBase64 = 'data:image/svg+xml;base64,' . base64_encode(file_get_contents($logoPath));
}

$catMap = ['Baal' => 'बाल', 'Tarun' => 'तरुण', 'Praudh' => 'प्रौढ़', 'Abhyagat' => 'अभ्यागत'];
?>

<!-- html2canvas plugin -->
<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>

<div class="page-header">
    <h1>📊 श्रेणीवार उपस्थिति रिपोर्ट</h1>
</div>

<!-- Filters -->
<div class="card">
    <div class="card-header">📅 तिथि एवं श्रेणी चयन</div>
    <form method="GET" action="shreni_report.php"
        style="display: flex; flex-wrap: wrap; gap: 16px; align-items: flex-end;">
        <div class="form-group" style="flex: 1; min-width: 150px;">
            <label class="form-label">प्रारंभ तिथि</label>
            <input type="date" name="start_date" class="form-control"
                value="<?php echo htmlspecialchars($startDate); ?>" required>
        </div>
        <div class="form-group" style="flex: 1; min-width: 150px;">
            <label class="form-label">अंतिम तिथि</label>
            <input type="date" name="end_date" class="form-control" value="<?php echo htmlspecialchars($endDate); ?>"
                required>
        </div>
        <div class="form-group" style="flex: 1; min-width: 150px;">
            <label class="form-label">श्रेणी (Shreni)</label>
            <select name="category" class="form-control">
                <option value="">-- सभी श्रेणियाँ --</option>
                <option value="Baal" <?php echo ($category === 'Baal') ? 'selected' : ''; ?>>बाल (Baal)</option>
                <option value="Tarun" <?php echo ($category === 'Tarun') ? 'selected' : ''; ?>>तरुण (Tarun)</option>
                <option value="Praudh" <?php echo ($category === 'Praudh') ? 'selected' : ''; ?>>प्रौढ़ (Praudh)</option>
                <option value="Abhyagat" <?php echo ($category === 'Abhyagat') ? 'selected' : ''; ?>>अभ्यागत (Abhyagat)</option>
            </select>
        </div>
        <div class="form-group" style="min-width: 120px;">
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
                <div style="font-size: 14px; opacity: 0.9; margin-top: 4px;">
                    <?php echo ($category !== '') ? $catMap[$category] . ' श्रेणी' : 'सभी श्रेणियाँ'; ?> - उपस्थिति रिपोर्ट
                </div>
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
                    कुल शाखा दिवस (Total Days): <strong style="color: #2E7D32; font-size: 1.25rem;"><?php echo $totalDays; ?></strong>
                </div>

                <!-- Members List (Ordered by present_days DESC) -->
                <div style="background: #FFF; border: 1px solid #FFCC80; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); overflow: hidden;">
                    <table style="width: 100%; border-collapse: collapse; text-align: left;">
                        <thead>
                            <tr style="background: #FFF3E0; border-bottom: 2px solid #FFB74D;">
                                <th style="padding: 12px 10px; font-weight: bold; color: #E64A19; font-size: 14px; text-align: center; width: 45px;">रैंक</th>
                                <th style="padding: 12px 10px; font-weight: bold; color: #E64A19; font-size: 14px;">स्वयंसेवक नाम</th>
                                <th style="padding: 12px 10px; font-weight: bold; color: #E64A19; font-size: 14px;">श्रेणी / गट</th>
                                <th style="padding: 12px 10px; font-weight: bold; color: #E64A19; font-size: 14px; text-align: right; width: 120px;">उपस्थिति</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($swayamsevaks as $i => $s): 
                                $pct = $totalDays > 0 ? round(($s['present_days'] / $totalDays) * 100) : 0;
                                // Highlight top attenders
                                $bgColor = ($i < 3 && $s['present_days'] > 0) ? '#FFFDE7' : '#FFF';
                                $rankBadgeColor = '#757575';
                                if ($i === 0 && $s['present_days'] > 0) $rankBadgeColor = '#FFD700'; // Gold
                                elseif ($i === 1 && $s['present_days'] > 0) $rankBadgeColor = '#C0C0C0'; // Silver
                                elseif ($i === 2 && $s['present_days'] > 0) $rankBadgeColor = '#CD7F32'; // Bronze
                            ?>
                                <tr style="background: <?php echo $bgColor; ?>; border-bottom: 1px solid #EEEEEE;">
                                    <td style="padding: 12px 10px; text-align: center;">
                                        <span style="display: inline-block; width: 24px; height: 24px; line-height: 24px; border-radius: 50%; background: <?php echo $rankBadgeColor; ?>; color: #FFF; font-weight: bold; font-size: 12px;">
                                            <?php echo $i + 1; ?>
                                        </span>
                                    </td>
                                    <td style="padding: 12px 10px; font-weight: 500; color: #4A1C00;">
                                        <?php echo htmlspecialchars($s['name']); ?>
                                        <?php if ($s['is_gat_nayak']): ?>
                                            <span style="background: #E65100; color: #FFF; font-size: 10px; padding: 1px 4px; border-radius: 3px; font-weight: bold; margin-left: 4px;">गट नायक</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 12px 10px; font-size: 13px; color: #5D4037;">
                                        <span style="font-weight: 600;"><?php echo $catMap[$s['category']] ?? $s['category']; ?></span>
                                        <?php if (!empty($s['gat'])): ?>
                                            <br><span style="color: #757575; font-size: 11px;"><?php echo htmlspecialchars($s['gat']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 12px 10px; text-align: right;">
                                        <strong style="color: #2E7D32;"><?php echo $s['present_days']; ?> / <?php echo $totalDays; ?> दिन</strong>
                                        <span style="display: block; font-size: 11px; color: #757575; margin-top: 2px;">(<?php echo $pct; ?>%)</span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
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
                        text: 'श्रेणीवार उपस्थिति रिपोर्ट',
                        filename: 'shreni_attendance_report_<?php echo $startDate; ?>_to_<?php echo $endDate; ?>.jpg'
                    }));
                    dlBtn.innerHTML = originalText;
                    dlBtn.disabled = false;
                    return;
                }

                const a = document.createElement('a');
                a.href = canvas.toDataURL('image/jpeg', 0.95);
                a.download = 'shreni_attendance_report_<?php echo $startDate; ?>_to_<?php echo $endDate; ?>.jpg';
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
                const file = new File([blob], 'shreni_attendance_report.jpg', { type: 'image/jpeg' });
                const textStr = 'श्रेणीवार उपस्थिति रिपोर्ट — <?php echo date("d/m/Y", strtotime($startDate)); ?> से <?php echo date("d/m/Y", strtotime($endDate)); ?>';

                if (window.FlutterShareChannel) {
                    const b64 = canvas.toDataURL('image/jpeg', 0.95);
                    window.FlutterShareChannel.postMessage(JSON.stringify({
                        image: b64,
                        text: textStr,
                        filename: 'shreni_attendance_report_<?php echo $startDate; ?>_to_<?php echo $endDate; ?>.jpg'
                    }));
                    shareBtn.innerHTML = originalText;
                    shareBtn.disabled = false;
                    return;
                }

                if (navigator.share && navigator.canShare && navigator.canShare({ files: [file] })) {
                    await navigator.share({
                        title: 'श्रेणीवार उपस्थिति रिपोर्ट',
                        text: textStr,
                        files: [file]
                    });
                } else {
                    const a = document.createElement('a');
                    a.href = URL.createObjectURL(blob);
                    a.download = 'shreni_attendance_report.jpg';
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
