<?php
require_once '../includes/auth.php';
/**
 * Daily Flipbook
 * An 8-page digital booklet for Swayamsevaks to view daily content post-shakha.
 */
require_once '../config/db.php';
requireLogin();

$shakhaId = getCurrentShakhaId();
if (isAdmin() && isset($_GET['shakha_id'])) {
    $shakhaId = intval($_GET['shakha_id']);
}
$date = $_GET['date'] ?? date('Y-m-d');

// Auto-create tables to prevent errors if visited first
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS geet (
        id INT AUTO_INCREMENT PRIMARY KEY, shakha_id INT NOT NULL, title VARCHAR(255) NOT NULL,
        geet_type ENUM('Ekal', 'Sanghik') NOT NULL DEFAULT 'Sanghik', lyrics TEXT NOT NULL,
        meaning_or_context TEXT, geet_date DATE NOT NULL, created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    $pdo->exec("CREATE TABLE IF NOT EXISTS ghoshnayein (
        id INT AUTO_INCREMENT PRIMARY KEY, shakha_id INT NOT NULL, slogan_sanskrit TEXT,
        slogan_hindi TEXT, context TEXT, ghoshna_date DATE NOT NULL, created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
} catch (PDOException $e) {}

// 1. Fetch Shakha Name
$stmt = $pdo->prepare("SELECT name FROM shakhas WHERE id = ?");
$stmt->execute([$shakhaId]);
$shakhaName = $stmt->fetchColumn() ?: 'शाखा';

// 2. Fetch Daily Record & Attendance Summary
$record = null;
$attendanceCount = 0;
$activitiesDone = [];
$stmt = $pdo->prepare("SELECT * FROM daily_records WHERE shakha_id = ? AND record_date = ?");
$stmt->execute([$shakhaId, $date]);
$record = $stmt->fetch();

if ($record) {
    // Count attendance
    $stmtA = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE daily_record_id = ? AND is_present = 1");
    $stmtA->execute([$record['id']]);
    $attendanceCount = $stmtA->fetchColumn();

    // Fetch activities done
    $stmtAct = $pdo->prepare("SELECT a.name, s.name as conductor_name 
        FROM daily_activities da 
        JOIN activities a ON da.activity_id = a.id 
        LEFT JOIN swayamsevaks s ON da.conducted_by = s.id 
        WHERE da.daily_record_id = ? AND da.is_done = 1");
    $stmtAct->execute([$record['id']]);
    $activitiesDone = $stmtAct->fetchAll();
}

// 3. Fetch Subhashit
$stmt = $pdo->prepare("SELECT * FROM subhashits WHERE shakha_id = ? AND subhashit_date = ? LIMIT 1");
$stmt->execute([$shakhaId, $date]);
$subhashit = $stmt->fetch();

// 4. Fetch Geet
$stmt = $pdo->prepare("SELECT * FROM geet WHERE shakha_id = ? AND geet_date = ? LIMIT 1");
$stmt->execute([$shakhaId, $date]);
$geet = $stmt->fetch();

// 5. Fetch Ghoshnayein
$stmt = $pdo->prepare("SELECT * FROM ghoshnayein WHERE shakha_id = ? AND ghoshna_date = ? LIMIT 5");
$stmt->execute([$shakhaId, $date]);
$ghoshnayein = $stmt->fetchAll();

// 6. Fetch Notices
$stmt = $pdo->prepare("SELECT * FROM notices WHERE shakha_id = ? ORDER BY created_at DESC LIMIT 3");
$stmt->execute([$shakhaId]);
$notices = $stmt->fetchAll();

// Hindi date formatting
$hindiMonths = ['जनवरी', 'फ़रवरी', 'मार्च', 'अप्रैल', 'मई', 'जून', 'जुलाई', 'अगस्त', 'सितंबर', 'अक्टूबर', 'नवंबर', 'दिसंबर'];
$hindiDays = ['रविवार', 'सोमवार', 'मंगलवार', 'बुधवार', 'गुरुवार', 'शुक्रवार', 'शनिवार'];
$ts = strtotime($date);
$formattedDate = $hindiDays[date('w', $ts)] . ", " . date('j', $ts) . " " . $hindiMonths[date('n', $ts) - 1] . " " . date('Y', $ts);

?>
<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>दैनिक वृत्त (Daily Flipbook) - <?php echo htmlspecialchars($shakhaName); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Devanagari:wght@400;600;800&family=Tiro+Devanagari+Hindi&display=swap" rel="stylesheet">
    
    <!-- Swiper.js for the Book Flip Effect -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css" />
    
    <style>
        :root {
            --bhagwa: #FF6B00;
            --bhagwa-light: #FFF3E0;
            --text-dark: #3E2723;
            --bg-page: #FDFBF7;
        }

        body {
            margin: 0;
            padding: 0;
            background: #1a1a1a;
            font-family: 'Noto Sans Devanagari', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            overflow: hidden;
        }

        .swiper {
            width: 100vw;
            height: 100vh;
            max-width: 450px; /* Phone size on desktop */
            max-height: 900px;
            background: #000;
        }

        .swiper-slide {
            background: var(--bg-page);
            display: flex;
            flex-direction: column;
            box-shadow: inset 0 0 50px rgba(0,0,0,0.05);
            position: relative;
            overflow: hidden;
        }

        /* Page Texture & Borders */
        .page-border {
            position: absolute;
            top: 15px; left: 15px; right: 15px; bottom: 15px;
            border: 2px solid rgba(255, 107, 0, 0.2);
            pointer-events: none;
            z-index: 10;
        }
        .page-border::before, .page-border::after {
            content: '❀';
            position: absolute;
            color: var(--bhagwa);
            font-size: 20px;
            background: var(--bg-page);
            padding: 5px;
        }
        .page-border::before { top: -15px; left: -15px; }
        .page-border::after { bottom: -15px; right: -15px; }

        .page-content {
            flex: 1;
            padding: 40px 30px;
            overflow-y: auto;
            color: var(--text-dark);
            position: relative;
            z-index: 5;
            display: flex;
            flex-direction: column;
        }

        /* Page Headers */
        .page-title {
            text-align: center;
            font-family: 'Tiro Devanagari Hindi', serif;
            font-size: 28px;
            color: #C2185B;
            margin-bottom: 20px;
            border-bottom: 1px dashed #C2185B;
            padding-bottom: 10px;
        }

        .cover-page {
            justify-content: center;
            align-items: center;
            text-align: center;
            background: linear-gradient(135deg, #FF9800 0%, #FF5722 100%);
            color: white;
        }
        .cover-title {
            font-family: 'Tiro Devanagari Hindi', serif;
            font-size: 42px;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        .cover-shakha {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 30px;
            letter-spacing: 1px;
        }
        .cover-date {
            background: rgba(255,255,255,0.2);
            padding: 10px 20px;
            border-radius: 30px;
            font-size: 16px;
            backdrop-filter: blur(5px);
        }

        /* Content Styles */
        .sanskrit-text {
            font-family: 'Tiro Devanagari Hindi', serif;
            font-size: 22px;
            color: #1B5E20;
            text-align: center;
            line-height: 1.8;
            margin: 20px 0;
            white-space: pre-wrap;
        }
        .hindi-meaning {
            background: rgba(255, 152, 0, 0.1);
            padding: 15px;
            border-radius: 10px;
            border-left: 4px solid var(--bhagwa);
            font-size: 16px;
            line-height: 1.6;
            white-space: pre-wrap;
        }

        .data-card {
            background: white;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            border: 1px solid rgba(0,0,0,0.05);
        }

        .shabdarth-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 15px;
            font-size: 14px;
        }
        .shabdarth-item {
            background: #F1F8E9;
            padding: 8px;
            border-radius: 6px;
            text-align: center;
            border: 1px dashed #81C784;
        }

        /* Nav helpers */
        .swipe-hint {
            position: absolute;
            bottom: 25px;
            left: 0; right: 0;
            text-align: center;
            font-size: 12px;
            color: rgba(0,0,0,0.4);
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { opacity: 0.4; transform: translateX(0); }
            50% { opacity: 1; transform: translateX(-5px); }
            100% { opacity: 0.4; transform: translateX(0); }
        }
        .cover-swipe-hint { color: rgba(255,255,255,0.8); }

        .page-number {
            position: absolute;
            bottom: 5px;
            right: 20px;
            font-size: 12px;
            color: #999;
        }
        
        .empty-state {
            text-align: center;
            color: #999;
            margin-top: 50px;
            font-style: italic;
        }
    </style>
</head>
<body>

<div class="swiper mySwiper">
    <div class="swiper-wrapper">
        
        <!-- PAGE 1: Cover -->
        <div class="swiper-slide cover-page">
            <div class="page-border" style="border-color: rgba(255,255,255,0.3);"></div>
            <div style="font-size: 50px; margin-bottom: 10px;">🚩</div>
            <div class="cover-title">दैनिक वृत्त</div>
            <div class="cover-shakha"><?php echo htmlspecialchars($shakhaName); ?></div>
            <div class="cover-date"><?php echo $formattedDate; ?></div>
            
            <?php if ($record && !empty($record['tithi'])): ?>
                <div style="margin-top: 20px; font-size: 14px; opacity: 0.9;">
                    तिथि: <?php echo htmlspecialchars($record['tithi']); ?><br>
                    <?php if (!empty($record['utsav'])) echo "उत्सव: " . htmlspecialchars($record['utsav']); ?>
                </div>
            <?php endif; ?>

            <div class="swipe-hint cover-swipe-hint">पढ़ने के लिए स्वाइप करें 👈</div>
        </div>

        <!-- PAGE 2: Attendance & Highlights -->
        <div class="swiper-slide">
            <div class="page-border"></div>
            <div class="page-content">
                <div class="page-title">उपस्थिति व वृत्त</div>
                
                <?php if ($record): ?>
                    <div class="data-card" style="text-align: center; background: #FFF3E0; border-color: #FFB74D;">
                        <div style="font-size: 14px; color: #E65100;">कुल उपस्थिति</div>
                        <div style="font-size: 48px; font-weight: bold; color: #FF6B00;"><?php echo $attendanceCount; ?></div>
                    </div>

                    <?php if ($record['custom_message']): ?>
                        <div class="hindi-meaning" style="margin-top: 20px; font-style: italic;">
                            "<?php echo nl2br(htmlspecialchars($record['custom_message'])); ?>"
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="empty-state">आज का वृत्त (Record) अभी नहीं भरा गया है।</div>
                <?php endif; ?>
                <div class="page-number">2 / 8</div>
            </div>
        </div>

        <!-- PAGE 3: Activities Done -->
        <div class="swiper-slide">
            <div class="page-border"></div>
            <div class="page-content">
                <div class="page-title">आज के कार्यक्रम</div>
                
                <?php if (!empty($activitiesDone)): ?>
                    <?php foreach ($activitiesDone as $act): ?>
                        <div class="data-card" style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-weight: bold; color: #33691E;">✅ <?php echo htmlspecialchars($act['name']); ?></span>
                            <?php if ($act['conductor_name']): ?>
                                <span style="background: #E8F5E9; color: #2E7D32; padding: 4px 10px; border-radius: 20px; font-size: 12px;">
                                    👤 <?php echo htmlspecialchars($act['conductor_name']); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">कार्यक्रम की जानकारी उपलब्ध नहीं है।</div>
                <?php endif; ?>
                <div class="page-number">3 / 8</div>
            </div>
        </div>

        <!-- PAGE 4: Subhashit -->
        <div class="swiper-slide">
            <div class="page-border"></div>
            <div class="page-content">
                <div class="page-title">सुभाषित</div>
                
                <?php if ($subhashit): ?>
                    <div class="sanskrit-text">
                        <?php echo nl2br(htmlspecialchars($subhashit['sanskrit_text'])); ?>
                    </div>
                    
                    <?php if ($subhashit['hindi_meaning']): ?>
                        <div style="font-weight: bold; color: #C2185B; margin-bottom: 5px; font-size: 14px;">भावार्थ:</div>
                        <div class="hindi-meaning">
                            <?php echo nl2br(htmlspecialchars($subhashit['hindi_meaning'])); ?>
                        </div>
                    <?php endif; ?>

                    <?php 
                    $shabd = json_decode($subhashit['shabdarth'], true);
                    if (!empty($shabd)): 
                    ?>
                        <div style="margin-top: 20px; font-weight: bold; color: #388E3C; font-size: 14px;">कठिन शब्दार्थ:</div>
                        <div class="shabdarth-grid">
                            <?php foreach ($shabd as $s): ?>
                                <div class="shabdarth-item">
                                    <span style="color:#1B5E20; font-weight:bold;"><?php echo htmlspecialchars($s['shabd']); ?></span><br>
                                    <span style="color:#558B2F; font-size: 12px;"><?php echo htmlspecialchars($s['arth']); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="empty-state">आज कोई सुभाषित नहीं है।</div>
                <?php endif; ?>
                <div class="page-number">4 / 8</div>
            </div>
        </div>

        <!-- PAGE 5: Geet -->
        <div class="swiper-slide">
            <div class="page-border"></div>
            <div class="page-content">
                <div class="page-title">गीत</div>
                
                <?php if ($geet): ?>
                    <div style="text-align: center; margin-bottom: 15px;">
                        <span style="background: <?php echo $geet['geet_type']=='Ekal' ? '#E3F2FD' : '#E8F5E9'; ?>; color: <?php echo $geet['geet_type']=='Ekal' ? '#1565C0' : '#2E7D32'; ?>; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: bold;">
                            <?php echo $geet['geet_type']=='Ekal' ? 'एकल गीत' : 'सांघिक गीत'; ?>
                        </span>
                    </div>
                    <div style="font-size: 20px; font-weight: bold; text-align: center; color: #E65100; margin-bottom: 15px;">
                        <?php echo htmlspecialchars($geet['title']); ?>
                    </div>
                    <div style="text-align: center; line-height: 1.8; font-size: 16px; color: #4E342E; white-space: pre-wrap; margin-bottom: 20px;">
                        <?php echo htmlspecialchars($geet['lyrics']); ?>
                    </div>
                    <?php if ($geet['meaning_or_context']): ?>
                        <div class="hindi-meaning" style="background: rgba(33, 150, 243, 0.05); border-color: #2196F3;">
                            <strong style="color: #1976D2; display: block; margin-bottom: 5px;">भाव / अमृत वचन:</strong>
                            <?php echo nl2br(htmlspecialchars($geet['meaning_or_context'])); ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="empty-state">आज कोई गीत नहीं है।</div>
                <?php endif; ?>
                <div class="page-number">5 / 8</div>
            </div>
        </div>

        <!-- PAGE 6: Ghoshnayein -->
        <div class="swiper-slide">
            <div class="page-border"></div>
            <div class="page-content">
                <div class="page-title">घोषणाएं</div>
                
                <?php if (!empty($ghoshnayein)): ?>
                    <div style="text-align: center; margin-bottom: 20px; color: #666; font-size: 14px;">
                        आज इन घोषणाओं का अभ्यास हुआ:
                    </div>
                    <?php foreach ($ghoshnayein as $g): ?>
                        <div class="data-card" style="border-left: 4px solid #C2185B;">
                            <?php if ($g['slogan_sanskrit']): ?>
                                <div style="font-family: 'Tiro Devanagari Hindi', serif; font-size: 18px; color: #880E4F; font-weight: bold; margin-bottom: 5px;">
                                    <?php echo nl2br(htmlspecialchars($g['slogan_sanskrit'])); ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($g['slogan_hindi']): ?>
                                <div style="color: #4A148C; font-size: 15px;">
                                    <?php echo nl2br(htmlspecialchars($g['slogan_hindi'])); ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($g['context']): ?>
                                <div style="margin-top: 8px; font-size: 12px; color: #999; background: #f5f5f5; display: inline-block; padding: 2px 8px; border-radius: 4px;">
                                    📌 <?php echo htmlspecialchars($g['context']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">आज कोई घोषणा नहीं है।</div>
                <?php endif; ?>
                <div class="page-number">6 / 8</div>
            </div>
        </div>

        <!-- PAGE 7: Notices -->
        <div class="swiper-slide">
            <div class="page-border"></div>
            <div class="page-content">
                <div class="page-title">सूचनाएं</div>
                
                <?php if (!empty($notices)): ?>
                    <?php foreach ($notices as $n): ?>
                        <div class="data-card" style="background: #FFFDE7; border-color: #FFF59D;">
                            <div style="font-weight: bold; color: #F57F17; font-size: 18px; margin-bottom: 8px;">
                                📢 <?php echo htmlspecialchars($n['subject']); ?>
                            </div>
                            <div style="font-size: 14px; color: #3E2723; white-space: pre-wrap; line-height: 1.5;">
                                <?php echo htmlspecialchars($n['message']); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">आज कोई नई सूचना नहीं है।</div>
                <?php endif; ?>
                <div class="page-number">7 / 8</div>
            </div>
        </div>

        <!-- PAGE 8: Back Cover -->
        <div class="swiper-slide cover-page" style="background: linear-gradient(135deg, #4CAF50 0%, #2E7D32 100%);">
            <div class="page-border" style="border-color: rgba(255,255,255,0.3);"></div>
            <div style="font-size: 60px; margin-bottom: 20px;">🙏</div>
            <div style="font-size: 30px; font-family: 'Tiro Devanagari Hindi', serif; margin-bottom: 30px; line-height: 1.4;">
                शाखा में पुनः<br>पधारें
            </div>
            
            <a href="dashboard.php" style="background: white; color: #2E7D32; padding: 12px 25px; border-radius: 25px; text-decoration: none; font-weight: bold; font-size: 16px; display: inline-block; margin-top: 20px; box-shadow: 0 4px 10px rgba(0,0,0,0.2);">
                डैशबोर्ड पर जाएं
            </a>
            
            <div class="page-number" style="color: rgba(255,255,255,0.5);">8 / 8</div>
        </div>

    </div>
</div>

<!-- Swiper JS -->
<script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>

<!-- Initialize Swiper -->
<script>
    var swiper = new Swiper(".mySwiper", {
        effect: "cards", // This creates a beautiful 3D deck/flipbook effect
        grabCursor: true,
        cardsEffect: {
            slideShadows: true,
            perSlideOffset: 8, // Offset of the background cards
            perSlideRotate: 2, // Rotation of background cards
        },
    });
</script>

</body>
</html>
