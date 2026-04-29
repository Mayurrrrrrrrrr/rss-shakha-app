<?php
require_once '../includes/auth.php';
/**
 * Paper Shakha (Sanghasthan Printout)
 * 8-Panel Zine Fold Layout (A4 Landscape)
 */
require_once '../config/db.php';
require_once '../includes/PanchangCalculator.php';

requireLogin();

$shakha_id = getCurrentShakhaId();
if (isAdmin() && isset($_GET['shakha_id'])) {
    $shakha_id = intval($_GET['shakha_id']);
}

if (!$shakha_id) {
    die("शखा आईडी प्राप्त नहीं हुई (Shakha ID not found).");
}

$date = $_GET['date'] ?? date('Y-m-d');
$ts = strtotime($date);

// 1. Fetch Shakha Info
$stmt = $pdo->prepare("SELECT * FROM shakhas WHERE id = ?");
$stmt->execute([$shakha_id]);
$shakha = $stmt->fetch();

// 2. Fetch Panchang
$calc = new PanchangCalculator();
$panchang = $calc->getPanchang($date);
$tithiStr = $panchang['tithi'] . ' ' . $panchang['paksha'] . ', ' . $panchang['maah'] . ' ' . $panchang['vikram_samvat'];

// 3. Fetch Subhashit (latest before or on this date)
$stmt = $pdo->prepare("SELECT * FROM subhashits WHERE shakha_id = ? AND subhashit_date <= ? ORDER BY subhashit_date DESC LIMIT 1");
$stmt->execute([$shakha_id, $date]);
$subhashit = $stmt->fetch();

// 4. Fetch Timetable
$dayOfWeek = date('w', $ts);
// Check override first
$stmt = $pdo->prepare("SELECT slots FROM timetable_overrides WHERE shakha_id = ? AND override_date = ?");
$stmt->execute([$shakha_id, $date]);
$timetableRow = $stmt->fetch();

if (!$timetableRow) {
    $stmt = $pdo->prepare("SELECT slots FROM timetable_defaults WHERE shakha_id = ? AND day_of_week = ?");
    $stmt->execute([$shakha_id, $dayOfWeek]);
    $timetableRow = $stmt->fetch();
}
$timetable = $timetableRow ? json_decode($timetableRow['slots'], true) : [];

// 5. Fetch Notices
$stmt = $pdo->prepare("SELECT * FROM notices WHERE shakha_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$shakha_id]);
$notices = $stmt->fetchAll();

// 6. Fetch "Regular" Swayamsevaks (Top attenders in last 30 days)
$thirtyDaysAgo = date('Y-m-d', strtotime('-30 days', $ts));
$stmt = $pdo->prepare("
    SELECT s.*, 
    (SELECT COUNT(*) FROM attendance a JOIN daily_records dr ON a.daily_record_id = dr.id 
     WHERE a.swayamsevak_id = s.id AND dr.shakha_id = s.shakha_id 
     AND dr.record_date >= ? AND a.is_present = 1) as attend_count
    FROM swayamsevaks s
    WHERE s.shakha_id = ? AND s.is_active = 1
    ORDER BY attend_count DESC, s.name ASC
");
$stmt->execute([$thirtyDaysAgo, $shakha_id]);
$swayamsevaks = $stmt->fetchAll();

// Split swayamsevaks into two columns (Page 6 and Page 7)
$col1 = array_slice($swayamsevaks, 0, 15);
$col2 = array_slice($swayamsevaks, 15, 15);

// QR Code URL (Points to daily_record.php for this date)
$appUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]/pages/daily_record.php?date=" . urlencode($date);
$qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($appUrl);

// Hindi formatting
$hindiDays = ['रविवार', 'सोमवार', 'मंगलवार', 'बुधवार', 'गुरुवार', 'शुक्रवार', 'शनिवार'];
$dayName = $hindiDays[$dayOfWeek];
?>
<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <title>Paper Shakha - <?php echo $date; ?></title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Tiro+Devanagari+Hindi&family=Inter:wght@400;600;800&display=swap');
        
        @page {
            size: A4 landscape;
            margin: 0;
        }

        body {
            margin: 0;
            padding: 0;
            background: #f0f0f0;
            font-family: 'Inter', 'Tiro Devanagari Hindi', sans-serif;
            color: #111;
            /* For web preview */
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .zine-sheet {
            width: 297mm;
            height: 210mm;
            background: #fff;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            position: relative;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .row {
            display: flex;
            height: 105mm; /* Half of 210mm */
            width: 100%;
        }

        /* Top row is printed upside down */
        .row-top {
            transform: rotate(180deg);
        }

        .panel {
            width: 74.25mm; /* Quarter of 297mm */
            height: 105mm;
            box-sizing: border-box;
            border-right: 1px dashed #ccc;
            border-bottom: 1px dashed #ccc;
            padding: 8mm;
            overflow: hidden;
            position: relative;
        }

        /* Remove border from last panel in row */
        .panel:nth-child(4) {
            border-right: none;
        }
        .row-bottom .panel {
            border-bottom: none;
        }

        /* Panel Typography & Styling */
        h1 { font-size: 16pt; font-weight: 800; margin: 0 0 5mm 0; color: #d32f2f; text-align: center; }
        h2 { font-size: 12pt; font-weight: 600; margin: 0 0 3mm 0; border-bottom: 1px solid #eee; padding-bottom: 2mm; color: #333; }
        h3 { font-size: 10pt; font-weight: 600; margin: 2mm 0; }
        p { font-size: 9pt; line-height: 1.4; margin: 0 0 3mm 0; }
        .small { font-size: 8pt; color: #555; }
        
        .center { text-align: center; }

        /* Specific Page Styles */
        .cover-page {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            background: #fff9f5; /* Light saffron tint */
        }
        
        .cover-logo {
            font-size: 30pt;
            margin-bottom: 5mm;
        }

        .timetable-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .timetable-list li {
            font-size: 9pt;
            margin-bottom: 2mm;
            display: flex;
            justify-content: space-between;
            border-bottom: 1px solid #f5f5f5;
        }
        .timetable-list li span { font-weight: bold; }

        .sanskrit {
            font-family: 'Tiro Devanagari Hindi', serif;
            font-size: 11pt;
            color: #d32f2f;
            text-align: center;
            margin-bottom: 4mm;
        }

        .attendance-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .attendance-list li {
            font-size: 9pt;
            margin-bottom: 2mm;
            display: flex;
            align-items: center;
            border-bottom: 1px solid #eee;
            padding-bottom: 1mm;
        }
        .attendance-box {
            width: 4mm;
            height: 4mm;
            border: 1px solid #333;
            margin-right: 2mm;
            display: inline-block;
        }

        .qr-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-end;
            height: 100%;
        }
        .qr-img {
            width: 35mm;
            height: 35mm;
            margin-bottom: 3mm;
        }

        /* Print Specifics */
        @media print {
            body { background: #fff; }
            .zine-sheet { box-shadow: none; border: none; }
            .no-print { display: none; }
        }

        .print-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #ff5722;
            color: white;
            border: none;
            padding: 15px 25px;
            font-size: 16px;
            border-radius: 50px;
            cursor: pointer;
            box-shadow: 0 4px 10px rgba(0,0,0,0.3);
            font-family: 'Inter', sans-serif;
            font-weight: 600;
        }
        .print-btn:hover { background: #e64a19; }
        
        .fold-lines {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            pointer-events: none;
        }
        .fold-line-v {
            position: absolute;
            width: 1px;
            height: 100%;
            background: rgba(0,0,0,0.05);
        }
        .fold-line-h {
            position: absolute;
            height: 1px;
            width: 100%;
            background: rgba(0,0,0,0.05);
            top: 105mm;
        }
        .cut-line {
            position: absolute;
            height: 1px;
            width: 148.5mm; /* Two middle panels */
            left: 74.25mm;
            top: 105mm;
            border-top: 2px dashed #ff5722;
            background: transparent;
        }
        
    </style>
</head>
<body>

    <button class="print-btn no-print" onclick="window.print()">🖨️ प्रिंट करें (Print Zine)</button>

    <div class="zine-sheet">
        <!-- Fold Guides -->
        <div class="fold-lines no-print">
            <div class="fold-line-v" style="left: 74.25mm;"></div>
            <div class="fold-line-v" style="left: 148.5mm;"></div>
            <div class="fold-line-v" style="left: 222.75mm;"></div>
            <div class="fold-line-h"></div>
            <!-- The Cut -->
            <div class="cut-line"></div>
        </div>

        <!-- Top Row (Upside down) -->
        <div class="row row-top">
            
            <!-- PAGE 5: NOTICES -->
            <div class="panel">
                <h2>घोषणाएं (Notices)</h2>
                <?php if (empty($notices)): ?>
                    <p class="small">कोई नई सूचना नहीं।</p>
                <?php else: ?>
                    <?php foreach ($notices as $n): ?>
                        <div style="margin-bottom: 3mm;">
                            <h3><?php echo htmlspecialchars($n['subject']); ?></h3>
                            <p><?php echo nl2br(htmlspecialchars(substr($n['message'], 0, 150))); ?>...</p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <div style="margin-top: 10mm; border-top: 1px solid #eee; padding-top: 2mm;">
                    <h2>नोट्स (Notes)</h2>
                    <div style="border-bottom: 1px solid #ccc; margin-top: 6mm;"></div>
                    <div style="border-bottom: 1px solid #ccc; margin-top: 6mm;"></div>
                    <div style="border-bottom: 1px solid #ccc; margin-top: 6mm;"></div>
                </div>
            </div>

            <!-- PAGE 4: GEET / AMRIT VACHAN -->
            <div class="panel">
                <h2>गीत (Geet) / अमृत वचन</h2>
                <?php if ($subhashit && !empty($subhashit['hindi_meaning'])): ?>
                    <p class="small"><b>अमृत वचन:</b></p>
                    <p><?php echo nl2br(htmlspecialchars($subhashit['hindi_meaning'])); ?></p>
                <?php else: ?>
                    <p class="small">आज के लिए कोई अमृत वचन सेट नहीं है।</p>
                <?php endif; ?>
                
                <h3 style="margin-top: 5mm;">गीत अभ्यास</h3>
                <div style="border-bottom: 1px solid #ccc; margin-top: 6mm;"></div>
                <div style="border-bottom: 1px solid #ccc; margin-top: 6mm;"></div>
                <div style="border-bottom: 1px solid #ccc; margin-top: 6mm;"></div>
                <div style="border-bottom: 1px solid #ccc; margin-top: 6mm;"></div>
                <div style="border-bottom: 1px solid #ccc; margin-top: 6mm;"></div>
            </div>

            <!-- PAGE 3: SUBHASHIT -->
            <div class="panel">
                <h2>सुभाषित (Subhashit)</h2>
                <?php if ($subhashit): ?>
                    <div class="sanskrit">
                        <?php echo nl2br(htmlspecialchars($subhashit['sanskrit_text'])); ?>
                    </div>
                    <p class="small"><b>अर्थ:</b></p>
                    <p><?php echo nl2br(htmlspecialchars($subhashit['hindi_meaning'])); ?></p>
                <?php else: ?>
                    <p>आज कोई सुभाषित नहीं है।</p>
                <?php endif; ?>
            </div>

            <!-- PAGE 6: ATTENDANCE 1 -->
            <div class="panel">
                <h2>उपस्थिति (Attendance) 1/2</h2>
                <ul class="attendance-list">
                    <?php foreach ($col1 as $s): ?>
                        <li><div class="attendance-box"></div> <?php echo htmlspecialchars($s['name']); ?></li>
                    <?php endforeach; ?>
                    <?php 
                    // Fill remaining with blanks
                    for($i = count($col1); $i < 16; $i++): ?>
                        <li><div class="attendance-box"></div> __________________</li>
                    <?php endfor; ?>
                </ul>
            </div>

        </div>

        <!-- Bottom Row (Right side up) -->
        <div class="row row-bottom">
            
            <!-- PAGE 8: BACK COVER (QR) -->
            <div class="panel center">
                <div class="qr-section">
                    <h2>क्विक सिंक (Quick Sync)</h2>
                    <p class="small">शाखा के बाद हाजिरी भरने के लिए इस QR कोड को स्कैन करें।</p>
                    <img src="<?php echo $qrUrl; ?>" class="qr-img" alt="Sync QR" loading="lazy">
                    <p class="small" style="margin-top: 2mm;"><b>दिनांक:</b> <?php echo date('d/m/Y', $ts); ?></p>
                    
                    <div style="margin-top: auto; padding-top: 10mm;">
                        <p class="small" style="color: #999;">Paper Shakha - Digital Detox</p>
                    </div>
                </div>
            </div>

            <!-- PAGE 1: FRONT COVER -->
            <div class="panel cover-page">
                <div class="cover-logo">🚩</div>
                <h1><?php echo htmlspecialchars($shakha['name'] ?? 'शाखा'); ?></h1>
                
                <h2 style="border:none; margin-top: 5mm; color:#d32f2f;"><?php echo $dayName; ?></h2>
                <h3><?php echo date('d M Y', $ts); ?></h3>
                
                <div style="margin-top: 10mm; padding: 3mm; background: #fff; border-radius: 4px; border: 1px solid #f0f0f0; width: 80%;">
                    <p class="small" style="margin:0; font-weight: bold;">तिथि / पंचांग:</p>
                    <p class="small" style="margin:0;"><?php echo htmlspecialchars($tithiStr); ?></p>
                </div>
            </div>

            <!-- PAGE 2: TIMETABLE -->
            <div class="panel">
                <h2>समय सारिणी (Timetable)</h2>
                <ul class="timetable-list">
                    <?php 
                    $totalMins = 0;
                    if ($timetable):
                        foreach ($timetable as $slot): 
                            $duration = $slot['end_min'] - $slot['start_min'];
                            $totalMins += $duration;
                    ?>
                        <li>
                            <?php echo htmlspecialchars($slot['topic']); ?>
                            <span><?php echo $duration; ?> min</span>
                        </li>
                    <?php 
                        endforeach; 
                    else: ?>
                        <li><p class="small">कोई समय सारिणी सेट नहीं है।</p></li>
                    <?php endif; ?>
                </ul>
                <?php if ($totalMins > 0): ?>
                <div style="text-align: right; margin-top: 2mm; border-top: 1px solid #333; padding-top: 1mm;">
                    <p class="small"><b>कुल: <?php echo $totalMins; ?> min</b></p>
                </div>
                <?php endif; ?>
            </div>

            <!-- PAGE 7: ATTENDANCE 2 -->
            <div class="panel">
                <h2>उपस्थिति (Attendance) 2/2</h2>
                <ul class="attendance-list">
                    <?php foreach ($col2 as $s): ?>
                        <li><div class="attendance-box"></div> <?php echo htmlspecialchars($s['name']); ?></li>
                    <?php endforeach; ?>
                    <?php 
                    // Fill remaining with blanks
                    for($i = count($col2); $i < 16; $i++): ?>
                        <li><div class="attendance-box"></div> __________________</li>
                    <?php endfor; ?>
                </ul>
            </div>

        </div>
    </div>

</body>
</html>
