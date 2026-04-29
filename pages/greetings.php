<?php
require_once '../includes/auth.php';
require_once '../config/db.php';
requireLogin();

if (!isMukhyashikshak() && !isAdmin()) {
    header('Location: dashboard.php');
    exit;
}

// Ensure table exists and has tithi column
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS greetings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        shakha_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        tithi VARCHAR(255),
        message TEXT,
        image_path VARCHAR(255),
        greeting_date DATE NOT NULL,
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (shakha_id) REFERENCES shakhas(id) ON DELETE CASCADE,
        FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    
    // Check if tithi column exists
    $cols = $pdo->query("SHOW COLUMNS FROM greetings LIKE 'tithi'")->fetch();
    if (!$cols) {
        $pdo->exec("ALTER TABLE greetings ADD COLUMN tithi VARCHAR(255) AFTER title");
    }
} catch (PDOException $e) {}

$shakhaId = getCurrentShakhaId();
$success = '';
$error = '';

// Handle Delete
if (isset($_POST['delete_id'])) {
    $delId = $_POST['delete_id'];
    // Get image path to delete file
    $stmt = $pdo->prepare("SELECT image_path FROM greetings WHERE id = ? AND shakha_id = ?");
    $stmt->execute([$delId, $shakhaId]);
    $img = $stmt->fetchColumn();
    if ($img && file_exists("../" . $img)) {
        unlink("../" . $img);
    }
    
    $stmt = $pdo->prepare("DELETE FROM greetings WHERE id = ? AND shakha_id = ?");
    $stmt->execute([$delId, $shakhaId]);
    $success = "संदेश सफलतापूर्वक हटा दिया गया।";
}

// Fetch shakha name and logo
$stmtShakha = $pdo->prepare("SELECT name, logo FROM shakhas WHERE id = ?");
$stmtShakha->execute([$shakhaId]);
$shakhaData = $stmtShakha->fetch();
$shakhaName = $shakhaData['name'] ?? 'शाखा';
$shakhaLogoImg = $shakhaData['logo'] ?? '';

$greetingId = $_GET['id'] ?? null;
$title = '';
$tithi = '';
$message = '';
$image_path = '';
$greeting_date = date('Y-m-d');

if ($greetingId) {
    $stmt = $pdo->prepare("SELECT * FROM greetings WHERE id = ? AND shakha_id = ?");
    $stmt->execute([$greetingId, $shakhaId]);
    $existing = $stmt->fetch();
    if ($existing) {
        $title = $existing['title'];
        $tithi = $existing['tithi'] ?? '';
        $message = $existing['message'];
        $image_path = $existing['image_path'];
        $greeting_date = $existing['greeting_date'];
    } else {
        $greetingId = null;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete_id'])) {
    $title = trim($_POST['title'] ?? '');
    $tithi = trim($_POST['tithi'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $greeting_date = $_POST['greeting_date'] ?? date('Y-m-d');
    $greetingIdToSave = $_POST['greeting_id'] ?? null;
    $createdBy = $_SESSION['user_id'];
    
    // Handle File Upload — use existing path from hidden field as fallback
    $uploadedImagePath = trim($_POST['existing_image_path'] ?? $image_path);
    if (isset($_FILES['greeting_image']) && $_FILES['greeting_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../assets/uploads/greetings/';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0777, true);
        }
        
        $fileExtension = pathinfo($_FILES['greeting_image']['name'], PATHINFO_EXTENSION);
        $fileName = 'greeting_' . time() . '_' . rand(1000, 9999) . '.' . $fileExtension;
        $targetPath = $uploadDir . $fileName;
        
        if (@move_uploaded_file($_FILES['greeting_image']['tmp_name'], $targetPath)) {
            $uploadedImagePath = 'assets/uploads/greetings/' . $fileName;
        }
    }

    if ($title) {
        try {
            if ($greetingIdToSave) {
                $stmt = $pdo->prepare("UPDATE greetings SET title = ?, tithi = ?, message = ?, image_path = ?, greeting_date = ? WHERE id = ? AND shakha_id = ?");
                $stmt->execute([$title, $tithi, $message, $uploadedImagePath, $greeting_date, $greetingIdToSave, $shakhaId]);
                // Redirect back to the same greeting so layout + image persist
                header('Location: greetings.php?id=' . $greetingIdToSave . '&saved=1');
                exit;
            } else {
                $stmt = $pdo->prepare("INSERT INTO greetings (shakha_id, title, tithi, message, image_path, greeting_date, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$shakhaId, $title, $tithi, $message, $uploadedImagePath, $greeting_date, $createdBy]);
                $newId = $pdo->lastInsertId();
                // Redirect to the newly created greeting's edit page
                header('Location: greetings.php?id=' . $newId . '&saved=1');
                exit;
            }
        } catch (Exception $e) {
            $error = "त्रुटि: संदेश सहेजने में विफल। " . $e->getMessage();
        }
    } else {
        $error = "शीर्षक (Title) आवश्यक है।";
    }
}

// Fetch recent greetings
$stmtRecent = $pdo->prepare("SELECT * FROM greetings WHERE shakha_id = ? ORDER BY greeting_date DESC LIMIT 10");
$stmtRecent->execute([$shakhaId]);
$recentGreetings = $stmtRecent->fetchAll();

$pageTitle = 'शुभकामनाएं (Greetings)';

// Check if we just saved (redirect-after-post)
if (isset($_GET['saved'])) {
    $success = 'शुभकामना संदेश सफलतापूर्वक सहेजा गया!';
}

require_once '../includes/header.php';
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Yatra+One&family=Tiro+Devanagari+Hindi:ital@0;1&family=Hind:wght@400;500;600;700&family=Anek+Devanagari:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
    /* ======== MAIN LAYOUT ======== */
    .greeting-layout {
        display: grid;
        grid-template-columns: 340px 1fr;
        gap: 22px;
        align-items: start;
    }

    /* ======== CANVAS (Capture Area) ======== */
    .g-canvas {
        width: 520px;
        height: 520px;
        margin: 0 auto;
        position: relative;
        overflow: hidden;
        box-sizing: border-box;
        background: linear-gradient(160deg, #FFF8E1 0%, #FFE0B2 30%, #FFCC80 60%, #FFE082 100%);
        box-shadow: 0 6px 30px rgba(0,0,0,0.25);
        user-select: none;
        cursor: default;
    }

    /* ======== DRAGGABLE ELEMENTS ======== */
    .g-el {
        position: absolute;
        cursor: grab;
        user-select: none;
        /* NO transition — prevents laggy drag */
        touch-action: none;
    }
    .g-el:hover {
        outline: 2px dashed rgba(255,107,0,0.5);
        outline-offset: 3px;
    }
    .g-el.g-dragging {
        cursor: grabbing;
        outline: 2px solid rgba(255,107,0,0.9);
        outline-offset: 3px;
        z-index: 999 !important;
    }
    .g-el.g-selected {
        outline: 2px solid #FF6D00;
        outline-offset: 3px;
    }

    /* Image element — NO frame, transparent bg */
    .g-el-image {
        z-index: 1;
        background: transparent;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .g-el-image img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        pointer-events: none;
        display: block;
    }
    .g-el-image.has-border img {
        border: 3px solid rgba(255,255,255,0.85);
        border-radius: 8px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.15);
    }
    .g-el-image .g-placeholder {
        font-size: 48px;
        color: rgba(0,0,0,0.1);
        pointer-events: none;
    }

    /* Shakha Logo element */
    .g-el-logo {
        z-index: 12;
        background: transparent;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .g-el-logo img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        pointer-events: none;
        border-radius: 50%;
        background: #FFF;
        padding: 2px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    }

    /* Resize handle */
    .g-resize-handle {
        position: absolute;
        bottom: -4px;
        right: -4px;
        width: 14px;
        height: 14px;
        background: #FF6D00;
        border: 2px solid #FFF;
        border-radius: 2px;
        cursor: nwse-resize;
        z-index: 1000;
        display: none;
        touch-action: none;
    }
    .g-el.g-selected .g-resize-handle {
        display: block;
    }

    /* Title element — full canvas width, auto-wrap */
    .g-el-title {
        z-index: 10;
        font-family: 'Yatra One', 'Tiro Devanagari Hindi', cursive;
        font-size: 42px;
        font-weight: 400;
        color: #BF360C;
        line-height: 1.2;
        text-shadow:
            2px 2px 0px rgba(255,255,255,0.8),
            0px 4px 8px rgba(191,54,12,0.25),
            0px 0px 20px rgba(255,152,0,0.15);
        text-align: center;
        white-space: normal;
        word-wrap: break-word;
        overflow-wrap: break-word;
        padding: 4px 8px;
    }

    /* Message element */
    .g-el-message {
        z-index: 10;
        font-family: 'Anek Devanagari', 'Tiro Devanagari Hindi', 'Noto Sans Devanagari', serif;
        font-size: 17px;
        font-weight: 600;
        line-height: 1.5;
        color: #3E2723;
        text-shadow: 0px 1px 2px rgba(255,255,255,0.6);
        text-align: center;
        white-space: pre-wrap;
        word-break: break-word;
        padding: 6px 10px;
    }

    /* Tithi element */
    .g-el-tithi {
        z-index: 10;
        font-family: 'Hind', 'Noto Sans Devanagari', sans-serif;
        font-size: 12px;
        font-weight: 500;
        color: #5D4037;
        letter-spacing: 0.5px;
        text-align: center;
        padding: 3px 12px;
        background: rgba(255,255,255,0.5);
        border-radius: 12px;
    }

    /* Footer / Shakha name element */
    .g-el-footer {
        z-index: 10;
        font-family: 'Hind', 'Noto Sans Devanagari', sans-serif;
        font-size: 11px;
        font-weight: 500;
        color: #8D6E63;
        letter-spacing: 1px;
        text-align: center;
        opacity: 0.8;
        padding: 2px 8px;
    }
    .g-el-footer .g-footer-date {
        font-size: 10px;
        color: #A1887F;
        margin-top: 1px;
    }

    /* Decorative band elements — use fixed width, not left+right */
    .g-el-band-top, .g-el-band-bottom {
        z-index: 5;
        height: 24px;
        width: 520px;
        background: linear-gradient(90deg, #BF360C, #E65100, #FF6F00, #F57F17, #FF6F00, #E65100, #BF360C);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        letter-spacing: 12px;
        color: rgba(255,255,255,0.85);
    }

    /* ======== SIDEBAR PANELS ======== */
    .g-panel {
        background: rgba(30,30,45,0.92);
        backdrop-filter: blur(15px);
        border-radius: 16px;
        padding: 18px;
        border: 1px solid rgba(255,153,51,0.15);
        margin-bottom: 16px;
    }
    .g-panel-title {
        color: #FFB74D;
        font-size: 0.9rem;
        font-weight: 700;
        margin-bottom: 14px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .g-panel label {
        display: block;
        font-size: 0.8rem;
        color: #999;
        margin-bottom: 4px;
        margin-top: 10px;
    }
    .g-panel label:first-of-type { margin-top: 0; }
    .g-input {
        width: 100%;
        padding: 10px 12px;
        background: rgba(255,255,255,0.06);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 10px;
        color: #fff;
        font-size: 0.95rem;
        font-family: inherit;
        transition: border-color 0.2s;
    }
    .g-input:focus {
        outline: none;
        border-color: #FF9800;
        box-shadow: 0 0 0 2px rgba(255,152,0,0.12);
    }
    textarea.g-input {
        resize: vertical;
        min-height: 60px;
    }
    .g-btn {
        width: 100%;
        padding: 12px;
        border: none;
        border-radius: 10px;
        font-weight: 700;
        font-family: inherit;
        font-size: 0.95rem;
        cursor: pointer;
        transition: all 0.2s;
    }
    .g-btn-primary {
        background: linear-gradient(135deg, #FF6F00, #E65100);
        color: #FFF;
        box-shadow: 0 3px 12px rgba(230,81,0,0.35);
    }
    .g-btn-primary:hover { transform: translateY(-1px); box-shadow: 0 5px 16px rgba(230,81,0,0.5); }

    /* ======== DESIGN CONTROLS ======== */
    .g-controls-row {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-top: 8px;
        flex-wrap: wrap;
    }
    .g-ctrl-btn {
        padding: 5px 10px;
        background: rgba(255,255,255,0.06);
        border: 1px solid rgba(255,255,255,0.12);
        color: #ccc;
        border-radius: 6px;
        cursor: pointer;
        font-size: 0.78rem;
        transition: all 0.2s;
        font-family: inherit;
    }
    .g-ctrl-btn:hover { background: rgba(255,152,0,0.12); border-color: rgba(255,152,0,0.3); color: #FFB74D; }
    .g-ctrl-btn.active { background: rgba(255,107,0,0.2); border-color: #FF6F00; color: #FFB74D; }
    .g-slider-row {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-top: 6px;
    }
    .g-slider-row label {
        margin: 0;
        min-width: 60px;
        font-size: 0.75rem;
    }
    .g-slider {
        flex: 1;
        accent-color: #FF9800;
        height: 4px;
    }
    .g-slider-val {
        font-size: 0.75rem;
        color: #888;
        min-width: 30px;
        text-align: right;
    }
    .g-color-input {
        width: 32px;
        height: 32px;
        border: 2px solid rgba(255,255,255,0.15);
        border-radius: 6px;
        cursor: pointer;
        background: none;
        padding: 0;
    }

    /* Action buttons row */
    .g-action-row {
        display: flex;
        gap: 10px;
        justify-content: center;
        margin-bottom: 16px;
        flex-wrap: wrap;
    }

    /* Recent list */
    .g-recent-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px;
        border-bottom: 1px solid rgba(255,255,255,0.05);
    }
    .g-recent-item a {
        display: flex;
        gap: 10px;
        text-decoration: none;
        align-items: center;
        flex: 1;
    }
    .g-recent-thumb {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        object-fit: cover;
        border: 1px solid rgba(255,153,51,0.3);
    }
    .btn-delete {
        background: rgba(244,67,54,0.1);
        border: 1px solid rgba(244,67,54,0.3);
        color: #FF5252;
        padding: 5px 10px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 0.8rem;
    }
    .btn-delete:hover { background: #F44336; color: #FFF; }

    /* Responsive */
    @media (max-width: 1100px) {
        .greeting-layout {
            grid-template-columns: 1fr;
        }
        .col-preview {
            position: static !important;
            order: -1; /* Canvas appears first on mobile */
        }
        .g-canvas {
            width: 100%;
            max-width: 520px;
            height: auto;
            aspect-ratio: 1 / 1;
        }
        .g-el-band-top, .g-el-band-bottom {
            width: 100%;
        }
    }
    @media (max-width: 560px) {
        .g-canvas {
            max-width: 360px;
        }
        .g-panel {
            padding: 14px;
        }
        .g-controls-row {
            gap: 5px;
        }
        .g-ctrl-btn {
            padding: 4px 7px;
            font-size: 0.72rem;
        }
    }
</style>

<div class="page-header">
    <h1>🎨 शुभकामना डिज़ाइनर (Greeting Designer)</h1>
    <p style="color: #666;">सभी एलिमेंट्स को ड्रैग करें, आकार बदलें, और अपनी पसंद का कार्ड बनाएं।</p>
</div>

<?php if ($success): ?>
    <div class="alert alert-success">✅ <?php echo $success; ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger">⚠️ <?php echo $error; ?></div>
<?php endif; ?>

<div class="greeting-layout">
    <!-- ========== LEFT SIDEBAR — INPUTS & CONTROLS ========== -->
    <div class="col-inputs">
        <!-- Form -->
        <form method="POST" action="greetings.php" enctype="multipart/form-data" id="greeting-form">
            <input type="hidden" name="greeting_id" value="<?php echo htmlspecialchars($greetingId ?? ''); ?>">
            <input type="hidden" name="existing_image_path" value="<?php echo htmlspecialchars($image_path); ?>">

            <div class="g-panel">
                <div class="g-panel-title">✍️ संदेश</div>
                <label>अवसर / त्यौहार *</label>
                <input type="text" name="title" id="inp-title" class="g-input" placeholder="गणतंत्र दिवस" required value="<?php echo htmlspecialchars($title); ?>">
                <label>तिथि / पंचांग</label>
                <input type="text" name="tithi" id="inp-tithi" class="g-input" placeholder="माघ शुक्ल पक्ष..." value="<?php echo htmlspecialchars($tithi); ?>">
                <label>शुभकामना संदेश</label>
                <textarea name="message" id="inp-message" class="g-input" rows="3" placeholder="हार्दिक शुभकामनाएं..."><?php echo htmlspecialchars($message); ?></textarea>
                <label>चित्र (PNG/JPG)</label>
                <input type="file" name="greeting_image" id="inp-image" class="g-input" accept="image/*">
                <label>दिनांक</label>
                <input type="date" name="greeting_date" id="inp-date" class="g-input" value="<?php echo htmlspecialchars($greeting_date); ?>">
                <div style="margin-top:14px;">
                    <button type="submit" class="g-btn g-btn-primary">💾 सहेजें</button>
                </div>
                <?php if ($greetingId): ?>
                    <a href="greetings.php" style="display:block; text-align:center; margin-top:8px; color:#888; font-size:0.85rem;">➕ नया बनाएं</a>
                <?php endif; ?>
            </div>
        </form>

        <!-- Design Controls -->
        <div class="g-panel" id="design-panel">
            <div class="g-panel-title">🎛️ डिज़ाइन कंट्रोल</div>

            <label>कैनवास पृष्ठभूमि</label>
            <div class="g-controls-row">
                <input type="color" class="g-color-input" id="ctrl-bg-color" value="#FFE0B2" title="पृष्ठभूमि रंग">
                <button class="g-ctrl-btn" onclick="setCanvasBg('linear-gradient(160deg,#FFF8E1,#FFE0B2,#FFCC80,#FFE082)')">🌅 Warm</button>
                <button class="g-ctrl-btn" onclick="setCanvasBg('linear-gradient(160deg,#E8EAF6,#C5CAE9,#9FA8DA)')">🌊 Cool</button>
                <button class="g-ctrl-btn" onclick="setCanvasBg('linear-gradient(160deg,#FFEBEE,#FFCDD2,#EF9A9A)')">🌸 Rose</button>
                <button class="g-ctrl-btn" onclick="setCanvasBg('linear-gradient(160deg,#E8F5E9,#C8E6C9,#A5D6A7)')">🍃 Green</button>
            </div>

            <label>चित्र आकार</label>
            <div class="g-slider-row">
                <label>चौड़ाई</label>
                <input type="range" class="g-slider" id="ctrl-img-w" min="40" max="520" value="180">
                <span class="g-slider-val" id="val-img-w">180</span>
            </div>
            <div class="g-slider-row">
                <label>ऊँचाई</label>
                <input type="range" class="g-slider" id="ctrl-img-h" min="40" max="520" value="180">
                <span class="g-slider-val" id="val-img-h">180</span>
            </div>

            <label>शीर्षक आकार</label>
            <div class="g-slider-row">
                <label>फॉन्ट</label>
                <input type="range" class="g-slider" id="ctrl-title-size" min="16" max="72" value="42">
                <span class="g-slider-val" id="val-title-size">42</span>
            </div>

            <label>संदेश आकार</label>
            <div class="g-slider-row">
                <label>फॉन्ट</label>
                <input type="range" class="g-slider" id="ctrl-msg-size" min="10" max="36" value="17">
                <span class="g-slider-val" id="val-msg-size">17</span>
            </div>

            <label>शीर्षक रंग</label>
            <div class="g-controls-row">
                <input type="color" class="g-color-input" id="ctrl-title-color" value="#BF360C">
                <button class="g-ctrl-btn" onclick="setTitleColor('#BF360C')">🧡 Default</button>
                <button class="g-ctrl-btn" onclick="setTitleColor('#FFFFFF')">⬜ White</button>
                <button class="g-ctrl-btn" onclick="setTitleColor('#1B5E20')">💚 Green</button>
                <button class="g-ctrl-btn" onclick="setTitleColor('#283593')">💙 Blue</button>
            </div>

            <label>संदेश रंग</label>
            <div class="g-controls-row">
                <input type="color" class="g-color-input" id="ctrl-msg-color" value="#3E2723">
                <button class="g-ctrl-btn" onclick="setMsgColor('#3E2723')">🤎 Default</button>
                <button class="g-ctrl-btn" onclick="setMsgColor('#FFFFFF')">⬜ White</button>
            </div>

            <label>परत क्रम (Layer)</label>
            <div class="g-controls-row">
                <button class="g-ctrl-btn" onclick="setImageLayer('back')">📷 चित्र पीछे</button>
                <button class="g-ctrl-btn" onclick="setImageLayer('front')">📷 चित्र आगे</button>
            </div>

            <label>सजावटी पट्टी</label>
            <div class="g-controls-row">
                <button class="g-ctrl-btn" id="toggle-bands" onclick="toggleBands()">🎗️ दिखाएं / छुपाएं</button>
            </div>

            <label>चित्र बॉर्डर</label>
            <div class="g-controls-row">
                <button class="g-ctrl-btn" id="toggle-img-border" onclick="toggleImgBorder()">🖼️ बॉर्डर दिखाएं / छुपाएं</button>
            </div>

            <label>शाखा लोगो</label>
            <div class="g-controls-row">
                <button class="g-ctrl-btn" id="toggle-logo" onclick="toggleLogo()">🚩 लोगो दिखाएं / छुपाएं</button>
            </div>

            <div style="margin-top: 14px;">
                <button class="g-ctrl-btn" style="width:100%; padding: 8px; font-size: 0.85rem;" onclick="resetAllPositions()">↺ सभी स्थिति रीसेट</button>
            </div>
        </div>

        <!-- Recent greetings -->
        <?php if (!empty($recentGreetings)): ?>
        <div class="g-panel">
            <div class="g-panel-title">📋 हाल के संदेश</div>
            <div style="max-height: 250px; overflow-y: auto;">
                <?php foreach ($recentGreetings as $g): ?>
                    <div class="g-recent-item">
                        <a href="greetings.php?id=<?php echo $g['id']; ?>">
                            <?php if ($g['image_path']): ?>
                                <img src="../<?php echo $g['image_path']; ?>" class="g-recent-thumb">
                            <?php else: ?>
                                <div style="width:40px;height:40px;background:#333;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:18px;">🚩</div>
                            <?php endif; ?>
                            <div>
                                <div style="color:#fff;font-size:0.9rem;font-weight:500;"><?php echo htmlspecialchars($g['title']); ?></div>
                                <div style="color:#888;font-size:0.75rem;"><?php echo date('d-m-Y', strtotime($g['greeting_date'])); ?></div>
                            </div>
                        </a>
                        <form method="POST" onsubmit="return confirm('हटाएं?');">
                            <input type="hidden" name="delete_id" value="<?php echo $g['id']; ?>">
                            <button type="submit" class="btn-delete">🗑️</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- ========== RIGHT — CANVAS + ACTIONS (STICKY) ========== -->
    <div class="col-preview" style="text-align: center; position: sticky; top: 80px; align-self: start;">
        <div class="g-action-row">
            <button id="btn-download" class="btn btn-success" style="background:#2E7D32;padding:12px 28px;font-weight:700;">⬇️ डाउनलोड (JPG)</button>
            <button id="btn-share" class="btn btn-whatsapp" style="padding:12px 28px;font-weight:700;">📱 व्हाट्सएप</button>
        </div>
        <p style="color:#777;font-size:0.78rem;margin-bottom:10px;">💡 किसी भी एलिमेंट को ड्रैग करके हिलाएं। चुनने पर रिसाइज़ हैंडल दिखेगा।</p>

        <div id="capture-area" class="g-canvas">
            <!-- Top Band -->
            <div class="g-el g-el-band-top" id="el-band-top" style="top:0;left:0;" data-default-x="0" data-default-y="0">
                ✦ ✦ ✦ ✦ ✦
            </div>

            <!-- Image -->
            <div class="g-el g-el-image" id="el-image" style="left:170px;top:170px;width:180px;height:180px;" data-default-x="170" data-default-y="170">
                <img id="prev-img" src="" style="display:none;">
                <span id="prev-img-placeholder" class="g-placeholder">🙏</span>
                <div class="g-resize-handle" id="img-resize-handle"></div>
            </div>

            <!-- Shakha Logo (optional) -->
            <div class="g-el g-el-logo" id="el-logo" style="left:10px;top:30px;width:50px;height:50px;display:none;" data-default-x="10" data-default-y="30">
                <?php if ($shakhaLogoImg && file_exists("../" . $shakhaLogoImg)): ?>
                    <img src="../<?php echo htmlspecialchars($shakhaLogoImg); ?>" alt="Logo">
                <?php else: ?>
                    <img src="../assets/images/logo.svg" alt="Logo">
                <?php endif; ?>
                <div class="g-resize-handle" id="logo-resize-handle"></div>
            </div>

            <!-- Tithi -->
            <div class="g-el g-el-tithi" id="el-tithi" style="left:140px;top:34px;display:none;" data-default-x="140" data-default-y="34">
                <span id="prev-tithi">तिथि</span>
            </div>

            <!-- Title (full width) -->
            <div class="g-el g-el-title" id="el-title" style="left:10px;top:60px;width:500px;" data-default-x="10" data-default-y="60">
                <span id="prev-title">शीर्षक यहाँ</span>
                <div class="g-resize-handle" id="title-resize-handle"></div>
            </div>

            <!-- Message -->
            <div class="g-el g-el-message" id="el-message" style="left:10px;top:380px;width:500px;" data-default-x="10" data-default-y="380">
                <span id="prev-message">आपकी शुभकामना संदेश यहाँ दिखेगा...</span>
                <div class="g-resize-handle" id="msg-resize-handle"></div>
            </div>

            <!-- Footer -->
            <div class="g-el g-el-footer" id="el-footer" style="left:160px;top:480px;" data-default-x="160" data-default-y="480">
                <div class="g-footer-shakha"><?php echo htmlspecialchars($shakhaName); ?></div>
                <div class="g-footer-date" id="prev-footer-date"><?php echo date('d-m-Y'); ?></div>
            </div>

            <!-- Bottom Band -->
            <div class="g-el g-el-band-bottom" id="el-band-bottom" style="left:0;top:496px;" data-default-x="0" data-default-y="496">
                <!-- decorative only -->
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    'use strict';

    // ===== DOM REFS =====
    const canvas = document.getElementById('capture-area');
    const inpTitle = document.getElementById('inp-title');
    const inpTithi = document.getElementById('inp-tithi');
    const inpMessage = document.getElementById('inp-message');
    const inpImage = document.getElementById('inp-image');
    const inpDate = document.getElementById('inp-date');

    const elImage = document.getElementById('el-image');
    const elTitle = document.getElementById('el-title');
    const elMessage = document.getElementById('el-message');
    const elTithi = document.getElementById('el-tithi');
    const elFooter = document.getElementById('el-footer');
    const elBandTop = document.getElementById('el-band-top');
    const elBandBottom = document.getElementById('el-band-bottom');
    const elLogo = document.getElementById('el-logo');

    const prevImg = document.getElementById('prev-img');
    const prevPlaceholder = document.getElementById('prev-img-placeholder');
    const prevTitle = document.getElementById('prev-title');
    const prevMessage = document.getElementById('prev-message');
    const prevTithi = document.getElementById('prev-tithi');
    const prevFooterDate = document.getElementById('prev-footer-date');

    // Sliders
    const ctrlImgW = document.getElementById('ctrl-img-w');
    const ctrlImgH = document.getElementById('ctrl-img-h');
    const ctrlTitleSize = document.getElementById('ctrl-title-size');
    const ctrlMsgSize = document.getElementById('ctrl-msg-size');
    const ctrlBgColor = document.getElementById('ctrl-bg-color');
    const ctrlTitleColor = document.getElementById('ctrl-title-color');
    const ctrlMsgColor = document.getElementById('ctrl-msg-color');

    // ===== LIVE PREVIEW =====
    function updatePreview() {
        const titleText = inpTitle.value.trim() || 'शीर्षक यहाँ';
        prevTitle.innerText = titleText;

        // Auto-size title font to avoid overflow
        const len = titleText.length;
        if (len > 30) {
            elTitle.style.fontSize = '24px';
        } else if (len > 22) {
            elTitle.style.fontSize = '28px';
        } else if (len > 15) {
            elTitle.style.fontSize = '34px';
        } else {
            elTitle.style.fontSize = ctrlTitleSize.value + 'px';
        }
        
        if (inpTithi.value.trim()) {
            prevTithi.innerText = inpTithi.value.trim();
            elTithi.style.display = 'block';
        } else {
            elTithi.style.display = 'none';
        }

        const msgText = inpMessage.value.trim();
        prevMessage.innerHTML = msgText ? msgText.replace(/\n/g, '<br>') : 'शुभकामना संदेश यहाँ दिखेगा...';

        const d = new Date(inpDate.value);
        if (!isNaN(d)) {
            prevFooterDate.innerText = d.getDate().toString().padStart(2,'0') + '-' +
                (d.getMonth()+1).toString().padStart(2,'0') + '-' + d.getFullYear();
        }
    }

    inpTitle.addEventListener('input', updatePreview);
    inpTithi.addEventListener('input', updatePreview);
    inpMessage.addEventListener('input', updatePreview);
    inpDate.addEventListener('input', updatePreview);

    // Image upload
    inpImage.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                prevImg.src = e.target.result;
                prevImg.style.display = 'block';
                prevPlaceholder.style.display = 'none';
            };
            reader.readAsDataURL(this.files[0]);
        }
    });

    // Edit-mode init
    <?php if ($image_path): ?>
    prevImg.src = '../<?php echo $image_path; ?>';
    prevImg.style.display = 'block';
    prevPlaceholder.style.display = 'none';
    <?php endif; ?>
    updatePreview();

    // ===== DRAG & DROP SYSTEM =====
    let activeEl = null;
    let dragData = null;
    let selectedEl = null;

    // All draggable elements
    const draggables = document.querySelectorAll('.g-el');

    draggables.forEach(el => {
        el.addEventListener('mousedown', onDragStart);
        el.addEventListener('touchstart', onDragStart, { passive: false });
        el.addEventListener('click', (e) => { selectElement(el); e.stopPropagation(); });
    });

    canvas.addEventListener('click', (e) => {
        if (e.target === canvas) deselectAll();
    });

    function onDragStart(e) {
        // Don't drag if clicking resize handle
        if (e.target.classList.contains('g-resize-handle')) return;
        
        activeEl = this;
        activeEl.classList.add('g-dragging');
        selectElement(activeEl);
        
        const pos = getPointerPos(e);
        const rect = activeEl.getBoundingClientRect();
        dragData = {
            offsetX: pos.x - rect.left,
            offsetY: pos.y - rect.top
        };

        e.preventDefault();
        e.stopPropagation();
    }

    document.addEventListener('mousemove', onDragMove);
    document.addEventListener('touchmove', onDragMove, { passive: false });

    function onDragMove(e) {
        if (!activeEl) return;
        e.preventDefault();
        
        const pos = getPointerPos(e);
        const canvasRect = canvas.getBoundingClientRect();
        
        let newX = pos.x - canvasRect.left - dragData.offsetX;
        let newY = pos.y - canvasRect.top - dragData.offsetY;

        // Constrain to canvas (loose — allow slight overflow)
        newX = Math.max(-20, Math.min(canvas.offsetWidth - 20, newX));
        newY = Math.max(-20, Math.min(canvas.offsetHeight - 20, newY));

        activeEl.style.left = newX + 'px';
        activeEl.style.top = newY + 'px';
        activeEl.style.bottom = 'auto'; // override bottom positioning
    }

    document.addEventListener('mouseup', onDragEnd);
    document.addEventListener('touchend', onDragEnd);

    function onDragEnd() {
        if (activeEl) {
            activeEl.classList.remove('g-dragging');
            activeEl = null;
            dragData = null;
        }
    }

    function selectElement(el) {
        deselectAll();
        el.classList.add('g-selected');
        selectedEl = el;
    }

    function deselectAll() {
        document.querySelectorAll('.g-el.g-selected').forEach(e => e.classList.remove('g-selected'));
        selectedEl = null;
    }

    function getPointerPos(e) {
        if (e.touches && e.touches.length > 0) return { x: e.touches[0].clientX, y: e.touches[0].clientY };
        return { x: e.clientX, y: e.clientY };
    }

    // ===== RESIZE HANDLES =====
    function setupResize(handleId, targetEl, mode) {
        const handle = document.getElementById(handleId);
        if (!handle) return;

        let resizing = false;
        let startX, startY, startW, startH;

        handle.addEventListener('mousedown', startResize);
        handle.addEventListener('touchstart', startResize, { passive: false });

        function startResize(e) {
            e.preventDefault();
            e.stopPropagation();
            resizing = true;
            const pos = getPointerPos(e);
            startX = pos.x;
            startY = pos.y;
            startW = targetEl.offsetWidth;
            startH = targetEl.offsetHeight;
            document.addEventListener('mousemove', doResize);
            document.addEventListener('touchmove', doResize, { passive: false });
            document.addEventListener('mouseup', endResize);
            document.addEventListener('touchend', endResize);
        }

        function doResize(e) {
            if (!resizing) return;
            e.preventDefault();
            const pos = getPointerPos(e);
            const dw = pos.x - startX;
            const dh = pos.y - startY;

            if (mode === 'both' || mode === 'width') {
                targetEl.style.width = Math.max(40, startW + dw) + 'px';
            }
            if (mode === 'both') {
                targetEl.style.height = Math.max(40, startH + dh) + 'px';
            }

            // Sync sliders if applicable
            if (targetEl === elImage) {
                ctrlImgW.value = targetEl.offsetWidth;
                ctrlImgH.value = targetEl.offsetHeight;
                document.getElementById('val-img-w').textContent = targetEl.offsetWidth;
                document.getElementById('val-img-h').textContent = targetEl.offsetHeight;
            }
        }

        function endResize() {
            resizing = false;
            document.removeEventListener('mousemove', doResize);
            document.removeEventListener('touchmove', doResize);
            document.removeEventListener('mouseup', endResize);
            document.removeEventListener('touchend', endResize);
        }
    }

    setupResize('img-resize-handle', elImage, 'both');
    setupResize('title-resize-handle', elTitle, 'width');
    setupResize('msg-resize-handle', elMessage, 'width');
    setupResize('logo-resize-handle', elLogo, 'both');

    // ===== SLIDER CONTROLS =====
    ctrlImgW.addEventListener('input', () => {
        elImage.style.width = ctrlImgW.value + 'px';
        document.getElementById('val-img-w').textContent = ctrlImgW.value;
    });
    ctrlImgH.addEventListener('input', () => {
        elImage.style.height = ctrlImgH.value + 'px';
        document.getElementById('val-img-h').textContent = ctrlImgH.value;
    });
    ctrlTitleSize.addEventListener('input', () => {
        elTitle.style.fontSize = ctrlTitleSize.value + 'px';
        document.getElementById('val-title-size').textContent = ctrlTitleSize.value;
    });
    ctrlMsgSize.addEventListener('input', () => {
        elMessage.style.fontSize = ctrlMsgSize.value + 'px';
        document.getElementById('val-msg-size').textContent = ctrlMsgSize.value;
    });
    ctrlBgColor.addEventListener('input', () => {
        canvas.style.background = ctrlBgColor.value;
    });
    ctrlTitleColor.addEventListener('input', () => {
        elTitle.style.color = ctrlTitleColor.value;
    });
    ctrlMsgColor.addEventListener('input', () => {
        elMessage.style.color = ctrlMsgColor.value;
    });

    // ===== DESIGN FUNCTIONS (global) =====
    window.setCanvasBg = function(bg) {
        canvas.style.background = bg;
    };
    window.setTitleColor = function(c) {
        elTitle.style.color = c;
        ctrlTitleColor.value = c;
        // Update text-shadow for light colors
        if (c === '#FFFFFF' || c === '#ffffff') {
            elTitle.style.textShadow = '2px 2px 4px rgba(0,0,0,0.5), 0 0 10px rgba(0,0,0,0.3)';
        } else {
            elTitle.style.textShadow = '2px 2px 0px rgba(255,255,255,0.8), 0px 4px 8px rgba(191,54,12,0.25)';
        }
    };
    window.setMsgColor = function(c) {
        elMessage.style.color = c;
        ctrlMsgColor.value = c;
        if (c === '#FFFFFF' || c === '#ffffff') {
            elMessage.style.textShadow = '1px 1px 3px rgba(0,0,0,0.6)';
        } else {
            elMessage.style.textShadow = '0px 1px 2px rgba(255,255,255,0.6)';
        }
    };
    window.setImageLayer = function(layer) {
        if (layer === 'back') {
            elImage.style.zIndex = '1';
        } else {
            elImage.style.zIndex = '50';
        }
    };

    let bandsVisible = true;
    window.toggleBands = function() {
        bandsVisible = !bandsVisible;
        elBandTop.style.display = bandsVisible ? 'flex' : 'none';
        elBandBottom.style.display = bandsVisible ? 'flex' : 'none';
    };

    let imgBorderOn = false;
    window.toggleImgBorder = function() {
        imgBorderOn = !imgBorderOn;
        elImage.classList.toggle('has-border', imgBorderOn);
    };

    let logoVisible = false;
    window.toggleLogo = function() {
        logoVisible = !logoVisible;
        elLogo.style.display = logoVisible ? 'flex' : 'none';
    };

    window.resetAllPositions = function() {
        draggables.forEach(el => {
            const dx = el.dataset.defaultX;
            const dy = el.dataset.defaultY;
            if (dx !== undefined) el.style.left = dx + 'px';
            if (dy !== undefined) el.style.top = dy + 'px';
            el.style.bottom = 'auto';
        });
        elImage.style.width = '180px';
        elImage.style.height = '180px';
        ctrlImgW.value = 180;
        ctrlImgH.value = 180;
        document.getElementById('val-img-w').textContent = '180';
        document.getElementById('val-img-h').textContent = '180';
        elTitle.style.width = '500px';
        elTitle.style.fontSize = '42px';
        ctrlTitleSize.value = 42;
        document.getElementById('val-title-size').textContent = '42';
        elMessage.style.width = '500px';
        elMessage.style.fontSize = '17px';
        ctrlMsgSize.value = 17;
        document.getElementById('val-msg-size').textContent = '17';
        elLogo.style.display = 'none';
        elLogo.style.width = '50px';
        elLogo.style.height = '50px';
        logoVisible = false;
        imgBorderOn = false;
        elImage.classList.remove('has-border');
        canvas.style.background = 'linear-gradient(160deg, #FFF8E1 0%, #FFE0B2 30%, #FFCC80 60%, #FFE082 100%)';
        setTitleColor('#BF360C');
        setMsgColor('#3E2723');
        elImage.style.zIndex = '1';
        bandsVisible = true;
        elBandTop.style.display = 'flex';
        elBandBottom.style.display = 'flex';
    };

    // ===== CAPTURE / DOWNLOAD / SHARE =====
    async function generateImage() {
        // Temporarily hide selection outlines
        deselectAll();
        const el = document.getElementById('capture-area');
        const canvas2 = await html2canvas(el, {
            scale: 3,
            useCORS: true,
            backgroundColor: null,
            logging: false
        });
        return canvas2;
    }

    document.getElementById('btn-download').addEventListener('click', async () => {
        const btn = document.getElementById('btn-download');
        const orig = btn.innerHTML;
        btn.innerText = '⏳ प्रक्रिया में...';
        btn.disabled = true;
        try {
            const c = await generateImage();
            if (window.FlutterShareChannel) {
                window.FlutterShareChannel.postMessage(JSON.stringify({
                    image: c.toDataURL('image/jpeg', 0.95),
                    text: inpTitle.value || 'शुभकामनाएं',
                    filename: 'greeting_' + (inpTitle.value || 'wish') + '.jpg'
                }));
            } else {
                const a = document.createElement('a');
                a.download = 'greeting_' + (inpTitle.value || 'wish') + '.jpg';
                a.href = c.toDataURL('image/jpeg', 0.92);
                a.click();
            }
        } catch (e) { console.error(e); alert('त्रुटि हुई।'); }
        btn.innerHTML = orig;
        btn.disabled = false;
    });

    document.getElementById('btn-share').addEventListener('click', async () => {
        const btn = document.getElementById('btn-share');
        const orig = btn.innerHTML;
        btn.innerText = '⏳ तैयार हो रहा है...';
        btn.disabled = true;
        try {
            const c = await generateImage();
            const title = inpTitle.value || 'शुभकामनाएं';
            if (window.FlutterShareChannel) {
                window.FlutterShareChannel.postMessage(JSON.stringify({
                    image: c.toDataURL('image/jpeg', 0.95),
                    text: title + ' — <?php echo $shakhaName; ?>',
                    filename: 'greeting_' + (inpTitle.value || 'wish') + '.jpg'
                }));
            } else {
                const blob = await new Promise(r => c.toBlob(r, 'image/jpeg', 0.92));
                const file = new File([blob], 'greeting.jpg', { type: 'image/jpeg' });
                if (navigator.share && navigator.canShare && navigator.canShare({ files: [file] })) {
                    await navigator.share({ title: 'शुभकामनाएं', text: title, files: [file] });
                } else {
                    const a = document.createElement('a');
                    a.download = 'greeting.jpg';
                    a.href = c.toDataURL('image/jpeg', 0.95);
                    a.click();
                    window.open('https://wa.me/?text=' + encodeURIComponent(title), '_blank');
                }
            }
        } catch (e) { console.error(e); }
        btn.innerHTML = '📱 व्हाट्सएप';
        btn.disabled = false;
    });

})();
</script>

<?php require_once '../includes/footer.php'; ?>
