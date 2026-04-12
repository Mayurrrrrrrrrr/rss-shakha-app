<?php
/**
 * Swayamsevak Management - स्वयंसेवक प्रबंधन
 */
$pageTitle = 'स्वयंसेवक';
require_once '../includes/header.php';
require_once '../config/db.php';
requireLogin();

if (isSwayamsevak()) {
    header('Location: swayamsevak_dashboard.php');
    exit;
}

$msg = $_GET['msg'] ?? '';
$editId = $_GET['edit'] ?? null;
$editData = null;
$shakhaId = getCurrentShakhaId();

if ($editId) {
    $stmt = $pdo->prepare("SELECT * FROM swayamsevaks WHERE id = ? AND is_active = 1 AND shakha_id = ?");
    $stmt->execute([$editId, $shakhaId]);
    $editData = $stmt->fetch();
}

$stmt = $pdo->prepare("SELECT * FROM swayamsevaks WHERE is_active = 1 AND shakha_id = ? ORDER BY name");
$stmt->execute([$shakhaId]);
$swayamsevaks = $stmt->fetchAll();
?>

<div class="page-header">
    <h1>👥 स्वयंसेवक प्रबंधन</h1>
</div>

<?php if ($msg === 'saved'): ?>
    <div class="alert alert-success">✅ स्वयंसेवक सफलतापूर्वक सहेजा गया!</div>
<?php elseif ($msg === 'deleted'): ?>
    <div class="alert alert-success">✅ स्वयंसेवक हटाया गया!</div>
<?php endif; ?>

<!-- Add/Edit Form -->
<div class="card">
    <div class="card-header">
        <?php echo $editData ? '✏️ स्वयंसेवक संपादित करें' : '➕ नया स्वयंसेवक जोड़ें'; ?>
    </div>
    <form method="POST" action="../actions/swayamsevak_save.php">
        <?php if ($editData): ?>
            <input type="hidden" name="id" value="<?php echo $editData['id']; ?>">
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
            <div class="form-group">
                <label for="name">नाम *</label>
                <input type="text" id="name" name="name" class="form-control" required placeholder="पूरा नाम"
                    value="<?php echo htmlspecialchars($editData['name'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="phone">फ़ोन नंबर</label>
                <input type="tel" id="phone" name="phone" class="form-control" placeholder="मोबाइल नंबर"
                    value="<?php echo htmlspecialchars($editData['phone'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="age">उम्र</label>
                <input type="number" id="age" name="age" class="form-control" min="5" max="100" placeholder="उम्र"
                    value="<?php echo htmlspecialchars($editData['age'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="category">श्रेणी *</label>
                <select id="category" name="category" class="form-control" required>
                    <option value="Tarun" <?php echo (($editData['category'] ?? '') === 'Tarun') ? 'selected' : ''; ?>>तरुण</option>
                    <option value="Baal" <?php echo (($editData['category'] ?? '') === 'Baal') ? 'selected' : ''; ?>>बाल</option>
                    <option value="Praudh" <?php echo (($editData['category'] ?? '') === 'Praudh') ? 'selected' : ''; ?>>प्रौढ़</option>
                    <option value="Abhyagat" <?php echo (($editData['category'] ?? '') === 'Abhyagat') ? 'selected' : ''; ?>>अभ्यागत</option>
                </select>
            </div>
            <div class="form-group">
                <label for="username">लॉगिन आईडी (वैकल्पिक)</label>
                <input type="text" id="username" name="username" class="form-control" placeholder="ऐप लॉगिन के लिए आईडी"
                    value="<?php echo htmlspecialchars($editData['username'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="password">पासवर्ड <?php echo $editData ? '(बदलने के लिए भरें)' : '(वैकल्पिक)'; ?></label>
                <input type="password" id="password" name="password" class="form-control" placeholder="पासवर्ड">
            </div>
        </div>
        <div class="form-group">
            <label for="address">पता</label>
            <textarea id="address" name="address" class="form-control" rows="2"
                placeholder="पता दर्ज करें"><?php echo htmlspecialchars($editData['address'] ?? ''); ?></textarea>
        </div>
        <div class="d-flex gap-1">
            <button type="submit" class="btn btn-primary">💾 सहेजें</button>
            <?php if ($editData): ?>
                <a href="../pages/swayamsevaks.php" class="btn btn-outline">❌ रद्द करें</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- List -->
<div class="card">
    <div class="card-header">📋 स्वयंसेवक सूची (
        <?php echo count($swayamsevaks); ?>)
    </div>
    <?php if (empty($swayamsevaks)): ?>
        <div class="empty-state">
            <div class="icon">👤</div>
            <p>अभी तक कोई स्वयंसेवक नहीं जोड़ा गया।</p>
        </div>
    <?php else: ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>नाम</th>
                        <th>श्रेणी</th>
                        <th>फ़ोन</th>
                        <th>उम्र</th>
                        <th>पता</th>
                        <th>कार्रवाई</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($swayamsevaks as $i => $s): ?>
                        <tr>
                            <td>
                                <?php echo $i + 1; ?>
                            </td>
                            <td><strong>
                                    <?php echo htmlspecialchars($s['name']); ?>
                                </strong></td>
                            <td>
                                <?php 
                                $catMap = ['Baal' => 'बाल', 'Tarun' => 'तरुण', 'Praudh' => 'प्रौढ़', 'Abhyagat' => 'अभ्यागत'];
                                echo $catMap[$s['category'] ?? 'Tarun'] ?? 'तरुण'; 
                                ?>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($s['phone'] ?: '-'); ?>
                            </td>
                            <td>
                                <?php echo $s['age'] ?: '-'; ?>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($s['address'] ?: '-'); ?>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <a href="../pages/swayamsevaks.php?edit=<?php echo $s['id']; ?>"
                                        class="btn btn-sm btn-outline">✏️</a>
                                    <a href="../actions/swayamsevak_delete.php?id=<?php echo $s['id']; ?>" class="btn btn-sm btn-danger"
                                        data-confirm="क्या आप इस स्वयंसेवक को हटाना चाहते हैं?">🗑️</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
