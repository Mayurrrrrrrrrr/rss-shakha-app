<?php
require_once '../includes/auth.php';
/**
 * Print Straight Content (साधारण साहित्य प्रिंटआउट - Non-Folding)
 */
require_once '../config/db.php';
requireLogin();

$shakha_id = getCurrentShakhaId();
if (isAdmin() && isset($_GET['shakha_id'])) {
    $shakha_id = intval($_GET['shakha_id']);
}

if (!$shakha_id) {
    die("शाखा आईडी प्राप्त नहीं हुई (Shakha ID not found).");
}

$date = $_GET['date'] ?? date('Y-m-d');
$ts = strtotime($date);

// Get shakha info
$stmt = $pdo->prepare("SELECT * FROM shakhas WHERE id = ?");
$stmt->execute([$shakha_id]);
$shakha = $stmt->fetch();

$selected_subhashit_ids = $_POST['subhashit_ids'] ?? [];
$selected_vachan_ids = $_POST['vachan_ids'] ?? [];
$selected_geet_ids = $_POST['geet_ids'] ?? [];
$include_prarthna = isset($_POST['include_prarthna']) ? intval($_POST['include_prarthna']) : 0;
$include_vandematram = isset($_POST['include_vandematram']) ? intval($_POST['include_vandematram']) : 0;
$include_janaganamana = isset($_POST['include_janaganamana']) ? intval($_POST['include_janaganamana']) : 0;
$custom_content = trim($_POST['custom_content'] ?? '');
$columns_layout = $_POST['columns_layout'] ?? '1'; // 1 or 2 columns

$subhashits = [];
$amrit_vachans = [];
$geets = [];

$is_print_mode = ($_SERVER['REQUEST_METHOD'] === 'POST' && (
    $include_prarthna || $include_vandematram || $include_janaganamana || 
    !empty($selected_subhashit_ids) || !empty($selected_vachan_ids) || 
    !empty($selected_geet_ids) || !empty($custom_content)
));

if ($is_print_mode) {
    if (!empty($selected_subhashit_ids)) {
        $placeholders = implode(',', array_fill(0, count($selected_subhashit_ids), '?'));
        $stmt = $pdo->prepare("SELECT * FROM subhashits WHERE id IN ($placeholders)");
        $stmt->execute($selected_subhashit_ids);
        $subhashits = $stmt->fetchAll();
    }

    if (!empty($selected_vachan_ids)) {
        $placeholders = implode(',', array_fill(0, count($selected_vachan_ids), '?'));
        $stmt = $pdo->prepare("SELECT * FROM amrit_vachan WHERE id IN ($placeholders)");
        $stmt->execute($selected_vachan_ids);
        $amrit_vachans = $stmt->fetchAll();
    }

    if (!empty($selected_geet_ids)) {
        $placeholders = implode(',', array_fill(0, count($selected_geet_ids), '?'));
        $stmt = $pdo->prepare("SELECT * FROM geet WHERE id IN ($placeholders)");
        $stmt->execute($selected_geet_ids);
        $geets = $stmt->fetchAll();
    }
}

if (!$is_print_mode) {
    // Selection mode
    $stmt = $pdo->prepare("SELECT * FROM subhashits WHERE shakha_id = ? OR shakha_id IS NULL ORDER BY subhashit_date DESC LIMIT 50");
    $stmt->execute([$shakha_id]);
    $subhashits_list = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT * FROM amrit_vachan WHERE shakha_id = ? ORDER BY vachan_date DESC LIMIT 50");
    $stmt->execute([$shakha_id]);
    $vachans_list = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT * FROM geet WHERE shakha_id = ? ORDER BY geet_date DESC LIMIT 50");
    $stmt->execute([$shakha_id]);
    $geets_list = $stmt->fetchAll();

    $pageTitle = 'साधारण प्रिंटआउट';
    require_once '../includes/header.php';
?>
    <div class="page-header">
        <h1>🖨️ साधारण प्रिंटआउट (A4 Straight Print)</h1>
    </div>

    <div class="card">
        <div class="card-header">📖 प्रिंट करने योग्य सामग्री का चयन करें (बहु-चयन उपलब्ध)</div>
        <form method="POST" action="print_straight.php" style="display: grid; gap: 20px; max-width: 800px; margin: 20px 0;">
            <?php if (isAdmin() && isset($_GET['shakha_id'])): ?>
                <input type="hidden" name="shakha_id" value="<?php echo htmlspecialchars($_GET['shakha_id']); ?>">
            <?php endif; ?>

            <div class="form-group" style="display: flex; flex-wrap: wrap; gap: 20px; align-items: center; background: #FFF3E0; padding: 12px; border-radius: 6px; border: 1px solid #FFCC80;">
                <label class="checkbox-item" style="cursor: pointer; display: flex; align-items: center; gap: 8px;">
                    <input type="checkbox" name="include_prarthna" value="1" style="width: 18px; height: 18px; cursor: pointer;">
                    <span class="checkbox-label" style="font-weight: bold; color: #E64A19;">🚩 संघ प्रार्थना (Prarthna)</span>
                </label>
                <label class="checkbox-item" style="cursor: pointer; display: flex; align-items: center; gap: 8px;">
                    <input type="checkbox" name="include_vandematram" value="1" style="width: 18px; height: 18px; cursor: pointer;">
                    <span class="checkbox-label" style="font-weight: bold; color: #E64A19;">🇮🇳 वन्दे मातरम् (Vande Mataram - पूर्ण)</span>
                </label>
                <label class="checkbox-item" style="cursor: pointer; display: flex; align-items: center; gap: 8px;">
                    <input type="checkbox" name="include_janaganamana" value="1" style="width: 18px; height: 18px; cursor: pointer;">
                    <span class="checkbox-label" style="font-weight: bold; color: #E64A19;">🇮🇳 जन गण मन (Jana Gana Mana)</span>
                </label>
            </div>

            <!-- Subhashit Checkbox List -->
            <div class="form-group">
                <label style="font-weight: bold; display: block; margin-bottom: 8px;">📜 सुभाषित चयन करें (Select Subhashits):</label>
                <div style="max-height: 160px; overflow-y: auto; border: 1px solid #ccc; padding: 12px; border-radius: 6px; background: #FFF9F2;">
                    <?php if (empty($subhashits_list)): ?>
                        <p class="small" style="margin:0; color:#888;">कोई सुभाषित उपलब्ध नहीं है।</p>
                    <?php else: ?>
                        <?php foreach ($subhashits_list as $s): ?>
                            <label style="display: flex; gap: 8px; margin-bottom: 8px; cursor: pointer; align-items: flex-start; font-size: 0.9rem;">
                                <input type="checkbox" name="subhashit_ids[]" value="<?php echo $s['id']; ?>" style="margin-top: 3px;">
                                <span><strong>[<?php echo date('d/m/Y', strtotime($s['subhashit_date'])); ?>]</strong> <?php echo mb_substr(htmlspecialchars($s['sanskrit_text']), 0, 80); ?>...</span>
                            </label>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Amrit Vachan Checkbox List -->
            <div class="form-group">
                <label style="font-weight: bold; display: block; margin-bottom: 8px;">💎 अमृत वचन चयन करें (Select Amrit Vachans):</label>
                <div style="max-height: 160px; overflow-y: auto; border: 1px solid #ccc; padding: 12px; border-radius: 6px; background: #FFF9F2;">
                    <?php if (empty($vachans_list)): ?>
                        <p class="small" style="margin:0; color:#888;">कोई अमृत वचन उपलब्ध नहीं है।</p>
                    <?php else: ?>
                        <?php foreach ($vachans_list as $v): ?>
                            <label style="display: flex; gap: 8px; margin-bottom: 8px; cursor: pointer; align-items: flex-start; font-size: 0.9rem;">
                                <input type="checkbox" name="vachan_ids[]" value="<?php echo $v['id']; ?>" style="margin-top: 3px;">
                                <span><strong>[<?php echo date('d/m/Y', strtotime($v['vachan_date'])); ?>]</strong> <?php echo mb_substr(htmlspecialchars($v['content']), 0, 80); ?>... (<?php echo htmlspecialchars($v['author'] ?: 'अज्ञात'); ?>)</span>
                            </label>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Geet Checkbox List -->
            <div class="form-group">
                <label style="font-weight: bold; display: block; margin-bottom: 8px;">🎵 गीत चयन करें (Select Geets):</label>
                <div style="max-height: 160px; overflow-y: auto; border: 1px solid #ccc; padding: 12px; border-radius: 6px; background: #FFF9F2;">
                    <?php if (empty($geets_list)): ?>
                        <p class="small" style="margin:0; color:#888;">कोई गीत उपलब्ध नहीं है।</p>
                    <?php else: ?>
                        <?php foreach ($geets_list as $g): ?>
                            <label style="display: flex; gap: 8px; margin-bottom: 8px; cursor: pointer; align-items: flex-start; font-size: 0.9rem;">
                                <input type="checkbox" name="geet_ids[]" value="<?php echo $g['id']; ?>" style="margin-top: 3px;">
                                <span><strong>[<?php echo date('d/m/Y', strtotime($g['geet_date'])); ?>]</strong> <?php echo htmlspecialchars($g['title']); ?> (<?php echo htmlspecialchars($g['geet_type'] === 'Ekal' ? 'एकल' : 'संघिक'); ?>)</span>
                            </label>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Custom Content Textarea -->
            <div class="form-group">
                <label for="custom_content" style="font-weight: bold;">📝 अतिरिक्त/कस्टम सामग्री (Custom Text / Notes)</label>
                <textarea id="custom_content" name="custom_content" class="form-control" rows="4" placeholder="यहाँ कोई भी अतिरिक्त सूचना, कविता या नोट्स लिखें जो आप प्रिंट करना चाहते हैं..."></textarea>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group">
                    <label for="columns_layout" style="font-weight: bold;">📐 लेआउट (Layout Columns)</label>
                    <select id="columns_layout" name="columns_layout" class="form-control">
                        <option value="1">1 कॉलम (Single Column - standard)</option>
                        <option value="2">2 कॉलम (Two Columns)</option>
                        <option value="3">3 कॉलम (Three Columns)</option>
                        <option value="4">4 कॉलम (Four Columns)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="date" style="font-weight: bold;">तिथि (Date on Print)</label>
                    <input type="date" id="date" name="date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                </div>
            </div>

            <div style="margin-top: 10px;">
                <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">🖨️ प्रिंट प्रिव्यू देखें</button>
            </div>
        </form>
    </div>

<?php 
    require_once '../includes/footer.php';
    exit;
}

// Render Standard printout screen
$appUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]/home.php";
$qrUrl = "https://chart.googleapis.com/chart?cht=qr&chs=120x120&chl=" . urlencode($appUrl);
$hindiDays = ['रविवार', 'सोमवार', 'मंगलवार', 'बुधवार', 'गुरुवार', 'शुक्रवार', 'शनिवार'];
$dayName = $hindiDays[date('w', $ts)];

$tithiStr = '';
$stmt = $pdo->prepare("SELECT yugabdh, vikram_samvat, shaka_samvat, hindi_month, paksh, tithi, utsav FROM daily_records WHERE record_date = ? AND shakha_id = ?");
$stmt->execute([$date, $shakha_id]);
$record = $stmt->fetch();
if ($record && !empty($record['tithi'])) {
    $tithiStr = $record['tithi'] . ' ' . $record['paksh'] . ', ' . $record['hindi_month'] . ' (संवत् ' . $record['vikram_samvat'] . ', युगाब्द ' . $record['yugabdh'] . ')';
    if (!empty($record['utsav'])) {
        $tithiStr .= ' - ' . $record['utsav'];
    }
} else {
    // Check AI Panchang cache
    $cacheKey = "shakha_{$shakha_id}_{$date}";
    $stmtC = $pdo->prepare("SELECT response_json FROM ai_content_cache WHERE content_type='panchang' AND content_key=?");
    $stmtC->execute([$cacheKey]);
    $cached = $stmtC->fetchColumn();
    if ($cached) {
        $aiData = json_decode($cached, true);
        if ($aiData) {
            $t = $aiData['tithi'] ?? '';
            $p = $aiData['paksha'] ?? '';
            $m = '';
            if (isset($aiData['maah'])) {
                $m = $aiData['maah']['purnimant'] ?? ($aiData['maah']['amant'] ?? '');
            }
            $v = '';
            $y = '';
            if (isset($aiData['samvat'])) {
                $v = $aiData['samvat']['vikram'] ?? '';
                $y = $aiData['samvat']['yugabdha'] ?? '';
            }
            $tithiParts = [];
            if ($t) {
                $tithiParts[] = $t;
            }
            if ($p && mb_strpos($t, $p) === false) {
                $tithiParts[] = $p;
            }
            if ($m && mb_strpos($t, $m) === false) {
                $tithiParts[] = $m;
            }
            $tithiStr = implode(' ', $tithiParts);
            
            $samvatParts = [];
            if ($v) {
                $samvatParts[] = "संवत् " . $v;
            }
            if ($y) {
                $samvatParts[] = "युगाब्द " . $y;
            }
            if (!empty($samvatParts)) {
                $tithiStr .= ' (' . implode(', ', $samvatParts) . ')';
            }
            if (!empty($aiData['vrat_tyohar']) && $aiData['vrat_tyohar'] !== 'null') {
                $tithiStr .= ' - ' . $aiData['vrat_tyohar'];
            }
        }
    }
    
    if (empty($tithiStr)) {
        require_once '../includes/PanchangCalculator.php';
        $calc = new PanchangCalculator();
        $panchang = $calc->getPanchang($date);
        if ($panchang) {
            $tithiStr = $panchang['tithi'] . ' ' . $panchang['paksha'] . ', ' . $panchang['maah'] . ' (संवत् ' . $panchang['vikram_samvat'] . ', युगाब्द ' . $panchang['yugabdh'] . ')';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <title>सांस्कृतिक साहित्य संकलन प्रिंट - <?php echo date('d/m/Y', $ts); ?></title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Tiro+Devanagari+Hindi&family=Inter:wght@400;500;600;700;800&display=swap');
        
        @page {
            size: A4 portrait;
            margin: 8mm;
        }

        body {
            margin: 0;
            padding: 0;
            background: #f5f5f5;
            font-family: 'Inter', 'Tiro Devanagari Hindi', sans-serif;
            color: #222;
            line-height: 1.5;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .print-container {
            width: 100%;
            max-width: 100%;
            background: #fff;
            padding: 4mm;
            box-shadow: none;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
        }

        /* Header design */
        .print-header {
            border-bottom: 3px double #FF6B00;
            padding-bottom: 4mm;
            margin-bottom: 8mm;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-title-section {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header-flag {
            font-size: 28pt;
            margin: 0;
        }

        .header-text h1 {
            font-size: 20pt;
            margin: 0;
            color: #d32f2f;
            font-weight: 800;
        }

        .header-text h2 {
            font-size: 11pt;
            margin: 2px 0 0 0;
            color: #555;
            font-weight: 500;
            border: none;
            padding: 0;
        }

        .header-date-section {
            text-align: right;
            font-size: 10pt;
            color: #333;
        }

        .header-date-section strong {
            color: #d32f2f;
            font-size: 11pt;
        }

        /* Content block */
        .content-section {
            display: <?php echo $columns_layout !== '1' ? 'grid' : 'block'; ?>;
            grid-template-columns: <?php 
                if ($columns_layout === '2') echo 'repeat(2, 1fr)';
                elseif ($columns_layout === '3') echo 'repeat(3, 1fr)';
                elseif ($columns_layout === '4') echo 'repeat(4, 1fr)';
                else echo '1fr';
            ?>;
            gap: <?php 
                if ($columns_layout === '4') echo '10px';
                elseif ($columns_layout === '3') echo '12px';
                else echo '20px';
            ?>;
            flex-grow: 1;
        }

        /* Dynamic Font & Padding Reductions for Multi-Column Layouts */
        .columns-3 .card-block {
            padding: 3mm !important;
            margin-bottom: 3.5mm !important;
        }
        .columns-3 .card-block h3 {
            font-size: 10.5pt !important;
        }
        .columns-3 .sanskrit {
            font-size: 9.5pt !important;
        }
        .columns-3 .meaning {
            font-size: 8pt !important;
            padding: 2mm !important;
        }
        .columns-3 .lyrics {
            font-size: 8.5pt !important;
        }
        .columns-3 .custom-note-text {
            font-size: 8.5pt !important;
        }

        .columns-4 .card-block {
            padding: 2.5mm !important;
            margin-bottom: 3mm !important;
        }
        .columns-4 .card-block h3 {
            font-size: 9pt !important;
        }
        .columns-4 .sanskrit {
            font-size: 8pt !important;
        }
        .columns-4 .meaning {
            font-size: 7.2pt !important;
            padding: 1.5mm !important;
        }
        .columns-4 .lyrics {
            font-size: 7.8pt !important;
        }
        .columns-4 .custom-note-text {
            font-size: 7.5pt !important;
        }

        .card-block {
            border: 1px solid #FFCC80;
            background: #FFFBF7;
            border-radius: 8px;
            padding: 5mm;
            margin-bottom: 6mm;
            page-break-inside: avoid;
            box-sizing: border-box;
        }

        .card-block h3 {
            font-size: 13pt;
            font-weight: 700;
            color: #E64A19;
            margin: 0 0 3mm 0;
            border-bottom: 2px solid #FFCC80;
            padding-bottom: 1.5mm;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .sanskrit {
            font-family: 'Tiro Devanagari Hindi', serif;
            font-size: 12pt;
            color: #d32f2f;
            line-height: 1.6;
            margin-bottom: 3mm;
            font-weight: 500;
            text-align: center;
        }

        .meaning {
            font-size: 9.5pt;
            line-height: 1.45;
            color: #4A1C00;
            background: #FFF5EB;
            padding: 3mm;
            border-left: 3px solid #FFB74D;
            border-radius: 0 4px 4px 0;
        }

        .lyrics {
            font-family: 'Tiro Devanagari Hindi', serif;
            font-size: 10.5pt;
            line-height: 1.5;
            white-space: pre-line;
            color: #333;
            margin-bottom: 3mm;
        }

        .custom-note-text {
            font-size: 10pt;
            line-height: 1.5;
            white-space: pre-line;
            color: #222;
        }

        /* Footer */
        .print-footer {
            border-top: 1px solid #ddd;
            padding-top: 5mm;
            margin-top: 8mm;
            display: flex;
            justify-content: space-between;
            align-items: center;
            page-break-inside: avoid;
        }

        .footer-logo {
            font-size: 11pt;
            font-weight: bold;
            color: #FF5722;
        }

        .footer-qr {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 8.5pt;
            color: #666;
            max-width: 320px;
        }

        .footer-qr img {
            width: 25mm;
            height: 25mm;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        /* Buttons fixed */
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
            z-index: 1000;
        }
        .print-btn:hover { background: #e64a19; }

        .back-btn {
            position: fixed;
            bottom: 20px;
            left: 20px;
            background: #607d8b;
            color: white;
            border: none;
            padding: 15px 25px;
            font-size: 16px;
            border-radius: 50px;
            cursor: pointer;
            box-shadow: 0 4px 10px rgba(0,0,0,0.3);
            font-family: 'Inter', sans-serif;
            font-weight: 600;
            z-index: 1000;
            text-decoration: none;
            text-align: center;
        }
        .back-btn:hover { background: #455a64; }

        @media print {
            html, body {
                height: auto;
                overflow: visible;
                font-size: 9.5pt;
                background: #fff;
                padding: 0;
                margin: 0;
            }
            @page {
                size: A4 portrait;
                margin: 10mm;
            }
            .print-container {
                box-shadow: none;
                border: none;
                padding: 0;
                width: 100%;
                height: auto;
                min-height: auto;
                display: flex;
                flex-direction: column;
            }
            .no-print { display: none; }
            .print-header {
                margin-bottom: 4mm;
                padding-bottom: 2mm;
            }
            .print-header h1 {
                font-size: 18pt;
            }
            .print-header h2 {
                font-size: 10pt;
            }
            .content-section {
                display: <?php echo $columns_layout !== '1' ? 'grid' : 'block'; ?>;
                grid-template-columns: <?php 
                    if ($columns_layout === '2') echo 'repeat(2, 1fr)';
                    elseif ($columns_layout === '3') echo 'repeat(3, 1fr)';
                    elseif ($columns_layout === '4') echo 'repeat(4, 1fr)';
                    else echo '1fr';
                ?>;
                gap: <?php 
                    if ($columns_layout === '4') echo '8px';
                    elseif ($columns_layout === '3') echo '10px';
                    else echo '15px';
                ?>;
            }
            .card-block {
                margin-bottom: 4mm;
                padding: 4mm;
                border-radius: 6px;
                page-break-inside: avoid;
                height: auto;
                min-height: auto;
            }
            .card-block h3 {
                font-size: 11pt;
                margin-bottom: 2mm;
                padding-bottom: 1mm;
            }
            .sanskrit {
                font-size: 10.5pt;
                margin-bottom: 2.5mm;
                line-height: 1.45;
            }
            .meaning {
                font-size: 8.5pt;
                padding: 2.5mm;
            }
            .lyrics {
                font-size: 9.5pt;
                line-height: 1.4;
                margin-bottom: 2.5mm;
            }
            .print-footer {
                margin-top: 5mm;
                padding-top: 3mm;
                page-break-inside: avoid;
            }
            .footer-qr img {
                width: 22mm;
                height: 22mm;
            }
        }
    </style>
</head>
<body>

    <a href="print_straight.php" class="back-btn no-print">⬅️ वापस जाएं (Selection Screen)</a>
    <button class="print-btn no-print" onclick="window.print()">🖨️ प्रिंट करें (Print Page)</button>

    <div class="print-container">
        


        <!-- Content List -->
        <div class="content-section columns-<?php echo $columns_layout; ?>">

            <!-- RSS PRARTHNA -->
            <?php if ($include_prarthna): ?>
                <div class="card-block">
                    <h3>🚩 संघ प्रार्थना (RSS Prarthna)</h3>
                    <div class="sanskrit" style="font-size: 11pt; line-height: 1.55;">
                        नमस्ते सदा वत्सले मातृभूमे<br>
                        त्वया हिन्दुभूमे सुखं वर्धितोऽहम्।<br>
                        महामङ्गले पुण्यभूमे त्वदर्थे<br>
                        पतत्वेष कायो नमस्ते नमस्ते ॥ १ ॥<br><br>
                        प्रभो शक्तिमन् हिन्दुराष्ट्राङ्गभूता<br>
                        इमे सादरं त्वां नमन्तो वयम्।<br>
                        त्वदीयाय कार्याय बद्धा कटीयं<br>
                        शुभामाशिषं देहि तत्पूर्तये ॥ २ ॥<br><br>
                        समुत्कर्ष निःश्रेयसस्यैकमुग्रं<br>
                        परं साधनं नाम वीरव्रतम्।<br>
                        तदन्तः स्फुरत्वक्षया ध्येयनिष्ठा<br>
                        हृदन्तः प्रजागर्तु तीव्रा निशम् ॥ ३ ॥<br><br>
                        विजेत्री च नः संहता कार्यशक्तिर्<br>
                        विधायास्य धर्मस्य संरक्षणम्।<br>
                        परं वैभवं नेतुमेतत् स्वराष्ट्रं<br>
                        समर्था भवत्वाशिषा ते भृशम् ॥ ४ ॥
                    </div>
                </div>
            <?php endif; ?>

            <!-- VANDE MATARAM -->
            <?php if ($include_vandematram): ?>
                <div class="card-block">
                    <h3>🇮🇳 वन्दे मातरम् (Vande Mataram - पूर्ण)</h3>
                    <div class="sanskrit" style="font-size: 10pt; line-height: 1.5; text-align: left;">
                        वन्दे मातरम्।<br>
                        सुजलां सुफलां मलयजशीतलाम्<br>
                        शस्यशामलां मातरम्। वन्दे मातरम्॥<br><br>
                        शुभ्रज्योत्स्नापुलकितयामिनीं<br>
                        फुल्लकुसुमितद्रुमदलशोभिनीं<br>
                        सुहासिनीं सुमधुरभाषिणीं<br>
                        सुखदां वरदां मातरम्॥ वन्दे मातरम्॥१॥<br><br>
                        कोटि-कोटि-कण्ठ-कल-कल-निनाद-कराले<br>
                        कोटि-कोटि-भुजैर्धृत-खरकरवाले,<br>
                        अबला केन मा एतो बले!<br>
                        बहुबलधारिणीं नमामि तारिणीं<br>
                        रिपुदलवारिणीं मातरम्॥ वन्दे मातरम्॥२॥<br><br>
                        तुम्ही विद्या, तुम्ही धर्म<br>
                        तुम्ही हृदि, तुम्ही मर्म<br>
                        त्वं हि प्राणाः शरीरे<br>
                        बाहुते तुम्ही मा शक्ति,<br>
                        हृदये तुम्ही मा भक्ति,<br>
                        तोमारेई प्रतिमा गड़ि मन्दिरे-मन्दिरे॥ वन्दे मातरम्॥३॥<br><br>
                        त्वं हि दुर्गा दशप्रहरणधारिणी<br>
                        कमला कमलदलविहारिणी<br>
                        वाणी विद्यादायिनी, नमामि त्वाम्<br>
                        नमामि कमलां अमलां अतुलां<br>
                        सुजलां सुफलां मातरम्॥ वन्दे मातरम्॥४॥<br><br>
                        श्यामलां सरलां सुस्मितां भूषितां<br>
                        धरणीं भरणीं मातरम्॥ वन्दे मातरम्॥५॥
                    </div>
                </div>
            <?php endif; ?>

            <!-- JANA GANA MANA -->
            <?php if ($include_janaganamana): ?>
                <div class="card-block">
                    <h3>🇮🇳 जन गण मन (Jana Gana Mana)</h3>
                    <div class="sanskrit" style="font-size: 11pt; line-height: 1.6; text-align: center;">
                        जनगणमन-अधिनायक जय हे भारतभाग्यविधाता!<br>
                        पंजाब-सिंधु-गुजरात-मराठा-द्राविड़-उत्कल-बंग<br>
                        विंध्य-हिमाचल-यमुना-गंगा उच्छल-जलधि-तरंग<br>
                        तव शुभ नामे जागे, तव शुभ आशिष मागे,<br>
                        गाहे तव जयगाथा।<br>
                        जनगणमंगलदायक जय हे भारतभाग्यविधाता!<br>
                        जय हे, जय हे, जय हे, जय जय जय जय हे॥
                    </div>
                </div>
            <?php endif; ?>

            <!-- SUBHASHITS -->
            <?php foreach ($subhashits as $s): ?>
                <div class="card-block">
                    <h3>📜 सुभाषित (Subhashit)</h3>
                    <div class="sanskrit"><?php echo nl2br(htmlspecialchars($s['sanskrit_text'])); ?></div>
                    <div class="meaning">
                        <strong>अर्थ:</strong><br>
                        <?php echo nl2br(htmlspecialchars($s['hindi_meaning'])); ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <!-- AMRIT VACHANS -->
            <?php foreach ($amrit_vachans as $v): ?>
                <div class="card-block">
                    <h3>💎 अमृत वचन (Amrit Vachan)</h3>
                    <p style="font-size: 11pt; line-height: 1.5; font-style: italic; color: #4A1C00; margin-bottom: 3mm;">
                        "<?php echo htmlspecialchars($v['content']); ?>"
                    </p>
                    <?php if ($v['author']): ?>
                        <div style="text-align: right; font-weight: bold; font-size: 10pt;">
                            — <?php echo htmlspecialchars($v['author']); ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <!-- GEETS -->
            <?php foreach ($geets as $g): ?>
                <div class="card-block">
                    <h3>🎵 गीत: <?php echo htmlspecialchars($g['title']); ?></h3>
                    <div class="lyrics"><?php echo htmlspecialchars($g['lyrics']); ?></div>
                    <?php if (!empty($g['meaning_or_context'])): ?>
                        <div class="meaning">
                            <strong>भावार्थ / संदर्भ:</strong><br>
                            <?php echo nl2br(htmlspecialchars($g['meaning_or_context'])); ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <!-- CUSTOM NOTES -->
            <?php if (!empty($custom_content)): ?>
                <div class="card-block">
                    <h3>📝 अतिरिक्त विवरण / नोट्स</h3>
                    <div class="custom-note-text"><?php echo nl2br(htmlspecialchars($custom_content)); ?></div>
                </div>
            <?php endif; ?>

        </div>



    </div>

</body>
</html>
