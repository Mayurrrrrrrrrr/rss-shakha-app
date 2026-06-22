<?php
require_once '../includes/auth.php';
/**
 * Paper Content (साहित्य प्रिंटआउट)
 * 8-Panel Zine Fold Layout (A4 Landscape)
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

$selected_subhashit_id = $_GET['subhashit_id'] ?? null;
$selected_vachan_id = $_GET['vachan_id'] ?? null;
$selected_geet_id = $_GET['geet_id'] ?? null;
$include_prarthna = isset($_GET['include_prarthna']) ? intval($_GET['include_prarthna']) : 0;
$include_vandematram = isset($_GET['include_vandematram']) ? intval($_GET['include_vandematram']) : 0;
$include_janaganamana = isset($_GET['include_janaganamana']) ? intval($_GET['include_janaganamana']) : 0;

$subhashit = null;
$amrit_vachan = null;
$geet = null;

if ($selected_subhashit_id) {
    $stmt = $pdo->prepare("SELECT * FROM subhashits WHERE id = ?");
    $stmt->execute([$selected_subhashit_id]);
    $subhashit = $stmt->fetch();
}

if ($selected_vachan_id) {
    $stmt = $pdo->prepare("SELECT * FROM amrit_vachan WHERE id = ?");
    $stmt->execute([$selected_vachan_id]);
    $amrit_vachan = $stmt->fetch();
}

if ($selected_geet_id) {
    $stmt = $pdo->prepare("SELECT * FROM geet WHERE id = ?");
    $stmt->execute([$selected_geet_id]);
    $geet = $stmt->fetch();
}

// Check if we are in printable display mode or select mode
$is_print_mode = ($selected_subhashit_id || $selected_vachan_id || $selected_geet_id || $include_prarthna || $include_vandematram || $include_janaganamana || isset($_GET['generate']));

if (!$is_print_mode) {
    // Selection mode: Fetch all lists to show in selection dropdowns
    $stmt = $pdo->prepare("SELECT * FROM subhashits WHERE shakha_id = ? OR shakha_id IS NULL ORDER BY subhashit_date DESC LIMIT 50");
    $stmt->execute([$shakha_id]);
    $subhashits_list = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT * FROM amrit_vachan WHERE shakha_id = ? ORDER BY vachan_date DESC LIMIT 50");
    $stmt->execute([$shakha_id]);
    $vachans_list = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT * FROM geet WHERE shakha_id = ? ORDER BY geet_date DESC LIMIT 50");
    $stmt->execute([$shakha_id]);
    $geets_list = $stmt->fetchAll();

    $pageTitle = 'साहित्य प्रिंटआउट';
    require_once '../includes/header.php';
?>
    <div class="page-header">
        <h1>📄 साहित्य प्रिंटआउट (Zine Booklet)</h1>
    </div>

    <div class="card">
        <div class="card-header">📖 प्रिंट करने योग्य सामग्री चुनें</div>
        <form method="GET" action="paper_content.php" style="display: grid; gap: 20px; max-width: 600px; margin: 20px 0;">
            <input type="hidden" name="generate" value="1">
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
                    <span class="checkbox-label" style="font-weight: bold; color: #E64A19;">🇮🇳 वन्दे मातरम् (पूर्ण)</span>
                </label>
                <label class="checkbox-item" style="cursor: pointer; display: flex; align-items: center; gap: 8px;">
                    <input type="checkbox" name="include_janaganamana" value="1" style="width: 18px; height: 18px; cursor: pointer;">
                    <span class="checkbox-label" style="font-weight: bold; color: #E64A19;">🇮🇳 जन गण मन (Jana Gana)</span>
                </label>
            </div>

            <div class="form-group">
                <label for="subhashit_id" style="font-weight: bold;">📜 सुभाषित (Subhashit)</label>
                <select id="subhashit_id" name="subhashit_id" class="form-control">
                    <option value="">-- सुभाषित न चुनें --</option>
                    <?php foreach ($subhashits_list as $s): ?>
                        <option value="<?php echo $s['id']; ?>">
                            <?php echo date('d/m/Y', strtotime($s['subhashit_date'])); ?> - <?php echo mb_substr(htmlspecialchars($s['sanskrit_text']), 0, 45); ?>...
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="vachan_id" style="font-weight: bold;">💎 अमृत वचन (Amrit Vachan)</label>
                <select id="vachan_id" name="vachan_id" class="form-control">
                    <option value="">-- अमृत वचन न चुनें --</option>
                    <?php foreach ($vachans_list as $v): ?>
                        <option value="<?php echo $v['id']; ?>">
                            <?php echo date('d/m/Y', strtotime($v['vachan_date'])); ?> - <?php echo mb_substr(htmlspecialchars($v['content']), 0, 45); ?>...
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="geet_id" style="font-weight: bold;">🎵 गीत (Geet)</label>
                <select id="geet_id" name="geet_id" class="form-control">
                    <option value="">-- गीत न चुनें --</option>
                    <?php foreach ($geets_list as $g): ?>
                        <option value="<?php echo $g['id']; ?>">
                            <?php echo date('d/m/Y', strtotime($g['geet_date'])); ?> - <?php echo htmlspecialchars($g['title']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="date" style="font-weight: bold;">कवर तिथि (Date for Cover)</label>
                <input type="date" id="date" name="date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
            </div>

            <div style="margin-top: 10px;">
                <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">🖨️ प्रिंट प्रिव्यू जनरेट करें</button>
            </div>
        </form>
    </div>

<?php 
    require_once '../includes/footer.php';
    exit;
}

// If in print mode, render the zine layout
// Fetch Tithi
$tithiStr = '';
$stmt = $pdo->prepare("SELECT yugabdh, vikram_samvat, shaka_samvat, hindi_month, paksh, tithi, utsav FROM daily_records WHERE record_date = ? AND shakha_id = ?");
$stmt->execute([$date, $shakha_id]);
$record = $stmt->fetch();
if ($record && !empty($record['tithi'])) {
    $tithiStr = $record['tithi'] . ' ' . $record['paksh'] . ', ' . $record['hindi_month'] . ' (संवत् ' . $record['vikram_samvat'] . ', युगाब्द ' . $record['yugabdh'] . ')';
    if (!empty($record['utsav'])) {
        $tithiStr .= ' - ' . $record['utsav'];
    }
    // Fallback to PanchangHelper
    require_once '../includes/PanchangHelper.php';
    $panchang = PanchangHelper::getForDate($pdo, $date, $shakha_id);
    if ($panchang) {
        $tithiStr = $panchang['tithi'] . ' ' . $panchang['paksha'] . ', ' . $panchang['vikram_month'] . ' (संवत् ' . $panchang['vikram_samvat'] . ', युगाब्द ' . $panchang['yugabdha'] . ')';
        if (!empty($panchang['utsav'])) {
            $tithiStr .= ' - ' . $panchang['utsav'];
        }
    }
}

// QR Code URL (Points to home portal)
$appUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]/index.php";
$qrUrl = "https://chart.googleapis.com/chart?cht=qr&chs=150x150&chl=" . urlencode($appUrl);
$hindiDays = ['रविवार', 'सोमवार', 'मंगलवार', 'बुधवार', 'गुरुवार', 'शुक्रवार', 'शनिवार'];
$dayName = $hindiDays[date('w', $ts)];
?>
<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <title>साहित्य संकलन प्रिंट - <?php echo date('d/m/Y', $ts); ?></title>
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

        .row-top {
            transform: rotate(180deg);
        }

        .panel {
            width: 74.25mm; /* Quarter of 297mm */
            height: 105mm;
            box-sizing: border-box;
            border-right: 1px dashed #ccc;
            border-bottom: 1px dashed #ccc;
            padding: 7mm;
            overflow: hidden;
            position: relative;
            display: flex;
            flex-direction: column;
        }

        .panel:nth-child(4) {
            border-right: none;
        }
        .row-bottom .panel {
            border-bottom: none;
        }

        h1 { font-size: 15pt; font-weight: 800; margin: 0 0 4mm 0; color: #d32f2f; text-align: center; }
        h2 { font-size: 11pt; font-weight: 600; margin: 0 0 2mm 0; border-bottom: 1px solid #eee; padding-bottom: 1.5mm; color: #333; }
        h3 { font-size: 9pt; font-weight: 600; margin: 1.5mm 0; color: #d32f2f; }
        p { font-size: 8.5pt; line-height: 1.35; margin: 0 0 2.5mm 0; }
        .small { font-size: 7.5pt; color: #555; line-height: 1.3; }
        
        .center { text-align: center; }

        .cover-page {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            background: #fff9f5;
        }
        
        .cover-logo {
            font-size: 26pt;
            margin-bottom: 4mm;
        }

        .sanskrit {
            font-family: 'Tiro Devanagari Hindi', serif;
            font-size: 9.5pt;
            color: #d32f2f;
            text-align: center;
            margin-bottom: 3mm;
            line-height: 1.35;
        }

        .qr-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-end;
            height: 100%;
        }
        .qr-img {
            width: 32mm;
            height: 32mm;
            margin-bottom: 2mm;
        }

        /* Lined paper helper */
        .lined-space {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-around;
            padding-top: 2mm;
        }
        .line {
            border-bottom: 1px solid #ccc;
            height: 6mm;
        }

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
            width: 148.5mm;
            left: 74.25mm;
            top: 105mm;
            border-top: 2px dashed #ff5722;
            background: transparent;
        }
    </style>
</head>
<body>

    <a href="paper_content.php" class="back-btn no-print">⬅️ वापस जाएं (Select Screen)</a>
    <button class="print-btn no-print" onclick="window.print()">🖨️ प्रिंट करें (Print Zine)</button>

    <div class="zine-sheet">
        <!-- Fold Guides -->
        <div class="fold-lines no-print">
            <div class="fold-line-v" style="left: 74.25mm;"></div>
            <div class="fold-line-v" style="left: 148.5mm;"></div>
            <div class="fold-line-v" style="left: 222.75mm;"></div>
            <div class="fold-line-h"></div>
            <div class="cut-line"></div>
        </div>

        <!-- Top Row (Upside down) -->
        <div class="row row-top">
            
            <!-- PAGE 5: GEET (Lyrics) -->
            <div class="panel">
                <h2>🎵 संघ गीत (Lyrics)</h2>
                <?php if ($geet): ?>
                    <h3 style="margin-bottom: 2mm;"><?php echo htmlspecialchars($geet['title']); ?></h3>
                    <div class="small" style="white-space: pre-line; line-height: 1.3; overflow-y: hidden; max-height: 75mm;">
                        <?php echo htmlspecialchars($geet['lyrics']); ?>
                    </div>
                <?php else: ?>
                    <p class="small">कोई गीत चयनित नहीं है।</p>
                <?php endif; ?>
            </div>

            <!-- PAGE 4: GEET MEANING -->
            <div class="panel">
                <h2>🎵 गीत का भावार्थ / संदेश</h2>
                <?php if ($geet && !empty($geet['meaning_or_context'])): ?>
                    <p class="small" style="white-space: pre-line;"><?php echo htmlspecialchars($geet['meaning_or_context']); ?></p>
                <?php else: ?>
                    <p class="small">गीत भावार्थ उपलब्ध नहीं है। आप यहाँ अपने विचार लिख सकते हैं।</p>
                    <div class="lined-space">
                        <div class="line"></div>
                        <div class="line"></div>
                        <div class="line"></div>
                        <div class="line"></div>
                        <div class="line"></div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- PAGE 3: SUBHASHIT -->
            <div class="panel">
                <h2>📜 सुभाषित (Subhashit)</h2>
                <?php if ($subhashit): ?>
                    <div class="sanskrit" style="font-size: 8.5pt;">
                        <?php echo nl2br(htmlspecialchars($subhashit['sanskrit_text'])); ?>
                    </div>
                    <p class="small" style="font-weight: bold; margin-bottom: 1mm;">अर्थ:</p>
                    <p class="small" style="margin: 0; line-height: 1.3; overflow-y: hidden; max-height: 50mm;"><?php echo nl2br(htmlspecialchars($subhashit['hindi_meaning'])); ?></p>
                <?php else: ?>
                    <p class="small">कोई सुभाषित चयनित नहीं है।</p>
                <?php endif; ?>
            </div>

            <!-- PAGE 6: DYNAMIC BLOCK 2 / NOTES -->
            <div class="panel">
                <?php
                $active_blocks = [];
                if ($include_prarthna) $active_blocks[] = 'prarthna';
                if ($include_vandematram) $active_blocks[] = 'vandematram';
                if ($include_janaganamana) $active_blocks[] = 'janaganamana';

                $block1 = array_shift($active_blocks);
                $block2 = array_shift($active_blocks);

                if ($block2 === 'prarthna'): ?>
                    <h2>🚩 संघ प्रार्थना</h2>
                    <div class="sanskrit" style="font-size: 6.8pt; text-align: left; margin: 0; line-height: 1.2; font-family: 'Tiro Devanagari Hindi', serif;">
                        नमस्ते सदा वत्सले मातृभूमे<br>
                        त्वया हिन्दुभूमे सुखं वर्धितोऽहम्।<br>
                        महामङ्गले पुण्यभूमे त्वदर्थे<br>
                        पतत्वेष कायो नमस्ते नमस्ते ॥१॥<br><br>
                        प्रभो शक्तिमन् हिन्दुराष्ट्राङ्गभूता<br>
                        इमे सादरं त्वां नमन्तो वयम्।<br>
                        त्वदीयाय कार्याय बद्धा कटीयं<br>
                        शुभामाशिषं देहि तत्पूर्तये ॥२॥<br><br>
                        समुत्कर्ष निःश्रेयसस्यैकमुग्रं<br>
                        परं साधनं नाम वीरव्रतम्।<br>
                        तदन्तः स्फुरत्वक्षया ध्येयनिष्ठा<br>
                        हृदन्तः प्रजागर्तु तीव्रा निशम् ॥३॥
                    </div>
                <?php elseif ($block2 === 'vandematram'): ?>
                    <h2>🇮🇳 वन्दे मातरम्</h2>
                    <div class="sanskrit" style="font-size: 5.2pt; text-align: left; margin: 0; line-height: 1.12; font-family: 'Tiro Devanagari Hindi', serif;">
                        वन्दे मातरम्।<br>
                        सुजलां सुफलां मलयजशीतलाम्<br>
                        शस्यशामलां मातरम्। वन्दे मातरम्॥<br>
                        शुभ्रज्योत्स्नापुलकितयामिनीं<br>
                        फुल्लकुसुमितद्रुमदलशोभिनीं<br>
                        सुहासिनीं सुमधुरभाषिणीं<br>
                        सुखदां वरदां मातरम्॥ वन्दे मातरम्॥१॥<br>
                        कोटि-कोटि-कण्ठ-कल-कल-निनाद-कराले<br>
                        कोटि-कोटि-भुजैर्धृत-खरकरवाले,<br>
                        अबला केन मा एतो बले!<br>
                        बहुबलधारिणीं नमामि तारिणीं<br>
                        रिपुदलवारिणीं मातरम्॥ वन्दे मातरम्॥२॥<br>
                        तुम्ही विद्या, तुम्ही धर्म<br>
                        तुम्ही हृदि, तुम्ही मर्म<br>
                        त्वं हि प्राणाः शरीरे<br>
                        बाहुते तुम्ही मा शक्ति,<br>
                        हृदये तुम्ही मा भक्ति,<br>
                        तोमारेई प्रतिमा गड़ि मन्दिरे-मन्दिरे॥ वन्दे मातरम्॥३॥<br>
                        त्वं हि दुर्गा दशप्रहरणधारिणी<br>
                        कमला कमलदलविहारिणी<br>
                        वाणी विद्यादायिनी, नमामि त्वाम्<br>
                        नमामि कमलां अमलां अतुलां<br>
                        सुजलां सुफलां मातरम्॥ वन्दे मातरम्॥४॥<br>
                        श्यामलां सरलां सुस्मितां भूषितां<br>
                        धरणीं भरणीं मातरम्॥ वन्दे मातरम्॥५॥
                    </div>
                <?php elseif ($block2 === 'janaganamana'): ?>
                    <h2>🇮🇳 जन गण मन</h2>
                    <div class="sanskrit" style="font-size: 6.8pt; text-align: center; margin: 0; line-height: 1.25; font-family: 'Tiro Devanagari Hindi', serif;">
                        जनगणमन-अधिनायक जय हे भारतभाग्यविधाता!<br>
                        पंजाब-सिंधु-गुजरात-मराठा-द्राविड़-उत्कल-बंग<br>
                        विंध्य-हिमाचल-यमुना-गंगा उच्छल-जलधि-तरंग<br>
                        तव शुभ नामे जागे, तव शुभ आशिष मागे,<br>
                        गाहे तव जयगाथा।<br>
                        जनगणमंगलदायक जय हे भारतभाग्यविधाता!<br>
                        जय हे, जय हे, जय हे, जय जय जय जय हे॥
                    </div>
                <?php else: ?>
                    <h2>📝 व्यक्तिगत नोट्स (Personal Notes)</h2>
                    <div class="lined-space" style="margin-top: 4mm;">
                        <div class="line"></div>
                        <div class="line"></div>
                        <div class="line"></div>
                        <div class="line"></div>
                        <div class="line"></div>
                        <div class="line"></div>
                        <div class="line"></div>
                        <div class="line"></div>
                    </div>
                <?php endif; ?>
            </div>

        </div>

        <!-- Bottom Row (Right side up) -->
        <div class="row row-bottom">
            
            <!-- PAGE 8: BACK COVER -->
            <div class="panel center">
                <div class="qr-section">
                    <h2>संघस्थान डिजिटल</h2>
                    <p class="small">सांस्कृतिक संग्रह, पंचांग एवं दैनिक पठन के लिए स्कैन करें।</p>
                    <img src="<?php echo $qrUrl; ?>" class="qr-img" alt="Sync QR" loading="lazy">
                    <p class="small" style="margin-top: 2mm;"><b>प्रिंट तिथि:</b> <?php echo date('d/m/Y', $ts); ?></p>
                    
                    <div style="margin-top: auto; padding-top: 5mm;">
                        <p class="small" style="color: #888; font-weight: bold;">🚩 राष्ट्राय स्वाहा इदं न मम 🚩</p>
                    </div>
                </div>
            </div>

            <!-- PAGE 1: FRONT COVER -->
            <div class="panel cover-page">
                <div class="cover-logo">🚩</div>
                <h1>सांस्कृतिक साहित्य संकलन</h1>
                <h3 style="margin: 2mm 0;"><?php echo htmlspecialchars($shakha['name'] ?? 'संघस्थान'); ?></h3>
                
                <h2 style="border:none; margin-top: 4mm; color:#d32f2f; font-size: 10.5pt;"><?php echo $dayName; ?></h2>
                <h3 style="font-size: 8.5pt; color: #555;"><?php echo date('d M Y', $ts); ?></h3>
                
                <div style="margin-top: 8mm; padding: 2.5mm; background: #fff; border-radius: 4px; border: 1px solid #ffcc80; width: 85%;">
                    <p class="small" style="margin:0; font-weight: bold; color: #e64a19;">पंचांग:</p>
                    <p class="small" style="margin:0; color: #4A1C00; font-weight: 600;"><?php echo htmlspecialchars($tithiStr); ?></p>
                </div>
            </div>

            <!-- PAGE 2: DYNAMIC BLOCK 1 / PRARTHNA -->
            <div class="panel">
                <?php if ($block1 === 'prarthna'): ?>
                    <h2>🚩 संघ प्रार्थना</h2>
                    <div class="sanskrit" style="font-size: 6.8pt; text-align: left; margin: 0; line-height: 1.2; font-family: 'Tiro Devanagari Hindi', serif;">
                        नमस्ते सदा वत्सले मातृभूमे<br>
                        त्वया हिन्दुभूमे सुखं वर्धितोऽहम्।<br>
                        महामङ्गले पुण्यभूमे त्वदर्थे<br>
                        पतत्वेष कायो नमस्ते नमस्ते ॥१॥<br><br>
                        प्रभो शक्तिमन् हिन्दुराष्ट्राङ्गभूता<br>
                        इमे सादरं त्वां नमन्तो वयम्।<br>
                        त्वदीयाय कार्याय बद्धा कटीयं<br>
                        शुभामाशिषं देहि तत्पूर्तये ॥२॥<br><br>
                        समुत्कर्ष निःश्रेयसस्यैकमुग्रं<br>
                        परं साधनं नाम वीरव्रतम्।<br>
                        तदन्तः स्फुरत्वक्षया ध्येयनिष्ठा<br>
                        हृदन्तः प्रजागर्तु तीव्रा निशम् ॥३॥
                    </div>
                <?php elseif ($block1 === 'vandematram'): ?>
                    <h2>🇮🇳 वन्दे मातरम्</h2>
                    <div class="sanskrit" style="font-size: 5.2pt; text-align: left; margin: 0; line-height: 1.12; font-family: 'Tiro Devanagari Hindi', serif;">
                        वन्दे मातरम्।<br>
                        सुजलां सुफलां मलयजशीतलाम्<br>
                        शस्यशामलां मातरम्। वन्दे मातरम्॥<br>
                        शुभ्रज्योत्स्नापुलकितयामिनीं<br>
                        फुल्लकुसुमितद्रुमदलशोभिनीं<br>
                        सुहासिनीं सुमधुरभाषिणीं<br>
                        सुखदां वरदां मातरम्॥ वन्दे मातरम्॥१॥<br>
                        कोटि-कोटि-कण्ठ-कल-कल-निनाद-कराले<br>
                        कोटि-कोटि-भुजैर्धृत-खरकरवाले,<br>
                        अबला केन मा एतो बले!<br>
                        बहुबलधारिणीं नमामि तारिणीं<br>
                        रिपुदलवारिणीं मातरम्॥ वन्दे मातरम्॥२॥<br>
                        तुम्ही विद्या, तुम्ही धर्म<br>
                        तुम्ही हृदि, तुम्ही मर्म<br>
                        त्वं हि प्राणाः शरीरे<br>
                        बाहुते तुम्ही मा शक्ति,<br>
                        हृदये तुम्ही मा भक्ति,<br>
                        तोमारेई प्रतिमा गड़ि मन्दिरे-मन्दिरे॥ वन्दे मातरम्॥३॥<br>
                        त्वं हि दुर्गा दशप्रहरणधारिणी<br>
                        कमला कमलदलविहारिणी<br>
                        वाणी विद्यादायिनी, नमामि त्वाम्<br>
                        नमामि कमलां अमलां अतुलां<br>
                        सुजलां सुफलां मातरम्॥ वन्दे मातरम्॥४॥<br>
                        श्यामलां सरलां सुस्मितां भूषितां<br>
                        धरणीं भरणीं मातरम्॥ वन्दे मातरम्॥५॥
                    </div>
                <?php elseif ($block1 === 'janaganamana'): ?>
                    <h2>🇮🇳 जन गण मन</h2>
                    <div class="sanskrit" style="font-size: 6.8pt; text-align: center; margin: 0; line-height: 1.25; font-family: 'Tiro Devanagari Hindi', serif;">
                        जनगणमन-अधिनायक जय हे भारतभाग्यविधाता!<br>
                        पंजाब-सिंधु-गुजरात-मराठा-द्राविड़-उत्कल-बंग<br>
                        विंध्य-हिमाचल-यमुना-गंगा उच्छल-जलधि-तरंग<br>
                        तव शुभ नामे जागे, तव शुभ आशिष मागे,<br>
                        गाहे तव जयगाथा।<br>
                        जनगणमंगलदायक जय हे भारतभाग्यविधाता!<br>
                        जय हे, जय हे, जय हे, जय जय जय जय हे॥
                    </div>
                <?php else: ?>
                    <h2>📝 व्यक्तिगत नोट्स</h2>
                    <p class="small">प्रार्थना शामिल नहीं की गई है। यहाँ आप अपने मनपसंद विचार या मंत्र लिख सकते हैं।</p>
                    <div class="lined-space">
                        <div class="line"></div>
                        <div class="line"></div>
                        <div class="line"></div>
                        <div class="line"></div>
                        <div class="line"></div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- PAGE 7: AMRIT VACHAN -->
            <div class="panel">
                <h2>💎 अमृत वचन (Amrit Vachan)</h2>
                <?php if ($amrit_vachan): ?>
                    <p class="small" style="font-size: 8.5pt; line-height: 1.4; color: #4A1C00; font-style: italic;">
                        "<?php echo htmlspecialchars($amrit_vachan['content']); ?>"
                    </p>
                    <?php if (!empty($amrit_vachan['author'])): ?>
                        <p class="small" style="text-align: right; font-weight: bold; margin-top: 2mm;">
                            — <?php echo htmlspecialchars($amrit_vachan['author']); ?>
                        </p>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="small">कोई अमृत वचन चयनित नहीं है।</p>
                <?php endif; ?>
            </div>

        </div>
    </div>

</body>
</html>
