<?php
require_once '../includes/auth.php';
require_once '../config/db.php';
requireLogin();

if (!isAdmin() && !isMukhyashikshak()) {
    header('Location: dashboard.php');
    exit;
}

// Auto-create table
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS geet (
        id INT AUTO_INCREMENT PRIMARY KEY,
        shakha_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        geet_type ENUM('Ekal', 'Sanghik') NOT NULL DEFAULT 'Sanghik',
        lyrics TEXT NOT NULL,
        meaning_or_context TEXT,
        geet_date DATE NOT NULL,
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (shakha_id) REFERENCES shakhas(id) ON DELETE CASCADE,
        FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
} catch (PDOException $e) {}

$shakhaId = getCurrentShakhaId();
$success = '';
$error = '';

$geetId = $_GET['id'] ?? null;
if ($geetId) {
    // Verify it belongs to this shakha or superadmin
    $stmt = $pdo->prepare("SELECT * FROM geet WHERE id = ? AND shakha_id = ?");
    $stmt->execute([$geetId, $shakhaId]);
    $existing = $stmt->fetch();
    if ($existing) {
        $title = $existing['title'];
        $geet_type = $existing['geet_type'];
        $lyrics = $existing['lyrics'];
        $meaning = $existing['meaning_or_context'];
        $geetDate = $existing['geet_date'];
    } else {
        $geetId = null; 
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $geetIdToSave = $_POST['geet_id'] ?? null;
    $title = trim($_POST['title'] ?? '');
    $geet_type = $_POST['geet_type'] ?? 'Sanghik';
    $lyrics = trim($_POST['lyrics'] ?? '');
    $meaning = trim($_POST['meaning'] ?? '');
    $geetDate = trim($_POST['geet_date'] ?? date('Y-m-d'));
    $createdBy = $_SESSION['user_id'];

    if ($title && $lyrics) {
        try {
            if ($geetIdToSave) {
                // Update
                $stmt = $pdo->prepare("UPDATE geet SET title = ?, geet_type = ?, lyrics = ?, meaning_or_context = ?, geet_date = ? WHERE id = ? AND shakha_id = ?");
                $stmt->execute([$title, $geet_type, $lyrics, $meaning, $geetDate, $geetIdToSave, $shakhaId]);
                $success = "गीत सफलतापूर्वक अपडेट किया गया!";
            } else {
                // Insert
                $stmt = $pdo->prepare("INSERT INTO geet (shakha_id, title, geet_type, lyrics, meaning_or_context, geet_date, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$shakhaId, $title, $geet_type, $lyrics, $meaning, $geetDate, $createdBy]);
                $success = "गीत सफलतापूर्वक सहेजा गया!";
                
                $title = '';
                $lyrics = '';
                $meaning = '';
                $geetId = null;
            }
        } catch (Exception $e) {
            $error = "त्रुटि: गीत सहेजने में विफल। " . $e->getMessage();
        }
    } else {
        $error = "शीर्षक और गीत के बोल आवश्यक हैं।";
    }
}

// Fetch recent geet
$stmt = $pdo->prepare("SELECT id, title, geet_type, geet_date FROM geet WHERE shakha_id = ? ORDER BY geet_date DESC LIMIT 15");
$stmt->execute([$shakhaId]);
$recentGeet = $stmt->fetchAll();

$pageTitle = 'गीत (Geet)';
require_once '../includes/header.php';
?>

<div class="page-header">
    <h1>🎵 गीत (Geet)</h1>
    <?php if (isAdmin() && !empty($geetId)): ?>
        <button class="btn btn-warning" onclick="openShareModal('geet', <?php echo $geetId; ?>)">🔗 Share across Shakhas</button>
    <?php endif; ?>
</div>

<?php if ($success): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<div style="display: flex; gap: 20px; flex-wrap: wrap;">
    <!-- Form Side -->
    <div style="flex: 2; min-width: 300px;">
        <div class="card">
            <div class="card-header">गीत विवरण दर्ज करें</div>
            <form method="POST" action="geet.php">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <input type="hidden" name="geet_id" value="<?php echo htmlspecialchars($geetId ?? ''); ?>">
                
                <div class="form-group">
                    <label>गीत का प्रकार (Type)</label>
                    <select name="geet_type" class="form-control" required>
                        <option value="Sanghik" <?php echo (isset($geet_type) && $geet_type == 'Sanghik') ? 'selected' : ''; ?>>सांघिक गीत (Chorus)</option>
                        <option value="Ekal" <?php echo (isset($geet_type) && $geet_type == 'Ekal') ? 'selected' : ''; ?>>एकल गीत (Solo)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>शीर्षक (Title) <span class="required">*</span></label>
                    <input type="text" name="title" id="inp-title" class="form-control" placeholder="उदा. ध्येय पथ पर बढ़ चले..." required value="<?php echo htmlspecialchars($title ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label>दिनांक (Date)</label>
                    <input type="date" name="geet_date" class="form-control" value="<?php echo htmlspecialchars($geetDate ?? date('Y-m-d')); ?>">
                </div>

                <div class="form-group">
                    <label>बोल (Lyrics) <span class="required">*</span></label>
                    <textarea name="lyrics" id="inp-lyrics" class="form-control" rows="8" required placeholder="गीत के बोल यहाँ लिखें..."><?php echo htmlspecialchars($lyrics ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label style="display: flex; justify-content: space-between; align-items: center;">
                        <span>अर्थ / संदर्भ / अमृत वचन (Meaning/Context)</span>
                        <button type="button" class="btn btn-sm btn-info" onclick="generateAiContext()" id="btn-ai-geet">✨ AI से अर्थ व अमृत वचन निकालें</button>
                    </label>
                    <textarea name="meaning" id="inp-meaning" class="form-control" rows="5" placeholder="गीत का भावार्थ या इससे जुड़ा कोई अमृत वचन यहाँ लिखें..."><?php echo htmlspecialchars($meaning ?? ''); ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; margin-bottom: 5px;">
                    <?php echo $geetId ? '💾 अपडेट करें (Update)' : '💾 सहेजें (Save to Database)'; ?>
                </button>
                <?php if ($geetId): ?>
                    <a href="geet.php" class="btn btn-outline" style="width: 100%; text-align: center; display: block;">➕ नया गीत बनाएं (Create New)</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Sidebar Recent -->
    <div style="flex: 1; min-width: 250px;">
        <?php if (!empty($recentGeet)): ?>
            <div class="card">
                <div class="card-header">हाल के गीत</div>
                <div class="list-group" style="padding: 10px 0; max-height: 500px; overflow-y: auto;">
                    <?php foreach ($recentGeet as $g): ?>
                        <a href="geet.php?id=<?php echo $g['id']; ?>" class="list-group-item <?php echo ($geetId == $g['id']) ? 'active' : ''; ?>" style="display: block; padding: 10px; border-bottom: 1px solid #eee; text-decoration: none; color: inherit; background: <?php echo ($geetId == $g['id']) ? '#FFF3E0' : 'transparent'; ?>">
                            <strong><?php echo htmlspecialchars($g['title']); ?></strong><br>
                            <span style="font-size: 0.8em; padding: 2px 6px; border-radius: 4px; background: <?php echo $g['geet_type'] == 'Ekal' ? '#E3F2FD; color:#1565C0;' : '#E8F5E9; color:#2E7D32;'; ?>">
                                <?php echo $g['geet_type'] == 'Ekal' ? 'एकल' : 'सांघिक'; ?>
                            </span>
                            <small style="color: #666; float: right;"><?php echo date('d-m-Y', strtotime($g['geet_date'])); ?></small>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
async function generateAiContext() {
    const lyrics = document.getElementById('inp-lyrics').value.trim();
    if (!lyrics) {
        alert("कृपया पहले गीत के बोल (Lyrics) दर्ज करें।");
        return;
    }

    const btn = document.getElementById('btn-ai-geet');
    const originalText = btn.innerHTML;
    btn.innerHTML = '⏳ AI सोच रहा है...';
    btn.disabled = true;

    try {
        const response = await fetch('../api/ai_content.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'geet_meaning', lyrics: lyrics })
        });
        
        const data = await response.json();
        if (data.success) {
            document.getElementById('inp-meaning').value = data.result;
        } else {
            alert("त्रुटि: " + (data.message || "Unknown error"));
        }
    } catch (e) {
        console.error(e);
        alert("सर्वर से जुड़ने में त्रुटि।");
    }

    btn.innerHTML = originalText;
    btn.disabled = false;
}
</script>

<?php require_once '../includes/share_modal.php'; ?>
<?php require_once '../includes/footer.php'; ?>
