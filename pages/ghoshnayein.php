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
    $pdo->exec("CREATE TABLE IF NOT EXISTS ghoshnayein (
        id INT AUTO_INCREMENT PRIMARY KEY,
        shakha_id INT NOT NULL,
        slogan_sanskrit TEXT,
        slogan_hindi TEXT,
        context TEXT,
        ghoshna_date DATE NOT NULL,
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (shakha_id) REFERENCES shakhas(id) ON DELETE CASCADE,
        FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
} catch (PDOException $e) {}

$shakhaId = getCurrentShakhaId();
$success = '';
$error = '';

$ghoshnaId = $_GET['id'] ?? null;
if ($ghoshnaId) {
    $stmt = $pdo->prepare("SELECT * FROM ghoshnayein WHERE id = ? AND shakha_id = ?");
    $stmt->execute([$ghoshnaId, $shakhaId]);
    $existing = $stmt->fetch();
    if ($existing) {
        $slogan_sanskrit = $existing['slogan_sanskrit'];
        $slogan_hindi = $existing['slogan_hindi'];
        $context = $existing['context'];
        $ghoshnaDate = $existing['ghoshna_date'];
    } else {
        $ghoshnaId = null; 
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ghoshnaIdToSave = $_POST['ghoshna_id'] ?? null;
    $slogan_sanskrit = trim($_POST['slogan_sanskrit'] ?? '');
    $slogan_hindi = trim($_POST['slogan_hindi'] ?? '');
    $context = trim($_POST['context'] ?? '');
    $ghoshnaDate = trim($_POST['ghoshna_date'] ?? date('Y-m-d'));
    $createdBy = $_SESSION['user_id'];

    if ($slogan_sanskrit || $slogan_hindi) {
        try {
            if ($ghoshnaIdToSave) {
                // Update
                $stmt = $pdo->prepare("UPDATE ghoshnayein SET slogan_sanskrit = ?, slogan_hindi = ?, context = ?, ghoshna_date = ? WHERE id = ? AND shakha_id = ?");
                $stmt->execute([$slogan_sanskrit, $slogan_hindi, $context, $ghoshnaDate, $ghoshnaIdToSave, $shakhaId]);
                $success = "घोषणा सफलतापूर्वक अपडेट की गई!";
            } else {
                // Insert
                $stmt = $pdo->prepare("INSERT INTO ghoshnayein (shakha_id, slogan_sanskrit, slogan_hindi, context, ghoshna_date, created_by) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$shakhaId, $slogan_sanskrit, $slogan_hindi, $context, $ghoshnaDate, $createdBy]);
                $success = "घोषणा सफलतापूर्वक सहेजी गई!";
                
                $slogan_sanskrit = '';
                $slogan_hindi = '';
                $context = '';
                $ghoshnaId = null;
            }
        } catch (Exception $e) {
            $error = "त्रुटि: घोषणा सहेजने में विफल। " . $e->getMessage();
        }
    } else {
        $error = "कम से कम एक भाषा (संस्कृत या हिंदी) में घोषणा आवश्यक है।";
    }
}

// Fetch recent
$stmt = $pdo->prepare("SELECT id, slogan_sanskrit, slogan_hindi, ghoshna_date FROM ghoshnayein WHERE shakha_id = ? ORDER BY ghoshna_date DESC LIMIT 15");
$stmt->execute([$shakhaId]);
$recentGhoshnayein = $stmt->fetchAll();

$pageTitle = 'घोषणाएं (Ghoshnayein)';
require_once '../includes/header.php';
?>

<div class="page-header">
    <h1>🗣️ घोषणाएं (Ghoshnayein)</h1>
    <?php if (isAdmin() && !empty($ghoshnaId)): ?>
        <button class="btn btn-warning" onclick="openShareModal('ghoshna', <?php echo $ghoshnaId; ?>)">🔗 Share across Shakhas</button>
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
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <span>घोषणा दर्ज करें</span>
                <button type="button" class="btn btn-sm btn-info" onclick="suggestAiGhoshna()" id="btn-ai-ghoshna">✨ AI: उत्सव अनुसार सुझाव लें</button>
            </div>
            <form method="POST" action="ghoshnayein.php">
                <input type="hidden" name="ghoshna_id" value="<?php echo htmlspecialchars($ghoshnaId ?? ''); ?>">
                
                <div class="form-group">
                    <label>संस्कृत घोषणा (Sanskrit Slogan)</label>
                    <textarea name="slogan_sanskrit" id="inp-sanskrit" class="form-control" rows="2" placeholder="उदा. भारत माता की जय..."><?php echo htmlspecialchars($slogan_sanskrit ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label>हिंदी अर्थ / हिंदी घोषणा (Hindi Slogan)</label>
                    <textarea name="slogan_hindi" id="inp-hindi" class="form-control" rows="2" placeholder="हिंदी में घोषणा या उसका अर्थ..."><?php echo htmlspecialchars($slogan_hindi ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label>दिनांक (Date)</label>
                    <input type="date" name="ghoshna_date" class="form-control" value="<?php echo htmlspecialchars($ghoshnaDate ?? date('Y-m-d')); ?>">
                </div>

                <div class="form-group">
                    <label>संदर्भ (Context / Occasion)</label>
                    <input type="text" name="context" id="inp-context" class="form-control" placeholder="उदा. विजयादशमी उत्सव हेतु, शारीरिक अभ्यास हेतु" value="<?php echo htmlspecialchars($context ?? ''); ?>">
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; margin-bottom: 5px;">
                    <?php echo $ghoshnaId ? '💾 अपडेट करें (Update)' : '💾 सहेजें (Save to Database)'; ?>
                </button>
                <?php if ($ghoshnaId): ?>
                    <a href="ghoshnayein.php" class="btn btn-outline" style="width: 100%; text-align: center; display: block;">➕ नई घोषणा बनाएं (Create New)</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Sidebar Recent -->
    <div style="flex: 1; min-width: 250px;">
        <?php if (!empty($recentGhoshnayein)): ?>
            <div class="card">
                <div class="card-header">हाल की घोषणाएं</div>
                <div class="list-group" style="padding: 10px 0; max-height: 500px; overflow-y: auto;">
                    <?php foreach ($recentGhoshnayein as $g): ?>
                        <a href="ghoshnayein.php?id=<?php echo $g['id']; ?>" class="list-group-item <?php echo ($ghoshnaId == $g['id']) ? 'active' : ''; ?>" style="display: block; padding: 10px; border-bottom: 1px solid #eee; text-decoration: none; color: inherit; background: <?php echo ($ghoshnaId == $g['id']) ? '#FFF3E0' : 'transparent'; ?>">
                            <strong><?php echo htmlspecialchars($g['slogan_sanskrit'] ?: $g['slogan_hindi']); ?></strong><br>
                            <small style="color: #666;"><?php echo date('d-m-Y', strtotime($g['ghoshna_date'])); ?></small>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
async function suggestAiGhoshna() {
    const utsav = prompt("किस उत्सव या विषय के लिए घोषणा चाहिए? (उदा. विजयादशमी, रक्षाबंधन, शारीरिक अभ्यास)");
    if (!utsav) return;

    const btn = document.getElementById('btn-ai-ghoshna');
    const originalText = btn.innerHTML;
    btn.innerHTML = '⏳ AI सुझाव खोज रहा है...';
    btn.disabled = true;

    try {
        const response = await fetch('../api/ai_content.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'suggest_ghoshna', context: utsav })
        });
        
        const data = await response.json();
        if (data.success) {
            document.getElementById('inp-sanskrit').value = data.result.sanskrit || '';
            document.getElementById('inp-hindi').value = data.result.hindi || '';
            document.getElementById('inp-context').value = utsav + ' हेतु';
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
