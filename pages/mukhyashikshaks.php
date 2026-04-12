<?php
/**
 * Mukhyashikshak Management - मुख्य शिक्षक प्रबंधन
 */
$pageTitle = 'मुख्य शिक्षक';
require_once '../includes/header.php';
require_once '../config/db.php';
requireLogin();

if (!isAdmin()) {
    header('Location: index.php');
    exit;
}

$msg = $_GET['msg'] ?? '';
$editId = $_GET['edit'] ?? null;
$editData = null;

if ($editId) {
    $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE id = ? AND role = 'mukhyashikshak'");
    $stmt->execute([$editId]);
    $editData = $stmt->fetch();
}

$mukhyashikshaks = $pdo->query("SELECT a.*, s.name as shakha_name 
    FROM admin_users a 
    LEFT JOIN shakhas s ON a.shakha_id = s.id 
    WHERE a.role = 'mukhyashikshak' 
    ORDER BY a.name")->fetchAll();

$allShakhas = $pdo->query("SELECT * FROM shakhas ORDER BY name")->fetchAll();
?>

<div class="page-header">
    <h1>👤 मुख्य शिक्षक प्रबंधन</h1>
</div>

<?php if ($msg === 'saved'): ?>
    <div class="alert alert-success">✅ मुख्य शिक्षक सफलतापूर्वक सहेजा गया!</div>
<?php elseif ($msg === 'deleted'): ?>
    <div class="alert alert-success">✅ मुख्य शिक्षक हटाया गया!</div>
<?php elseif ($msg === 'error_username'): ?>
    <div class="alert alert-danger">❌ यह उपयोगकर्ता नाम पहले से मौजूद है!</div>
<?php elseif ($msg === 'error'): ?>
    <div class="alert alert-danger">❌ कोई त्रुटि हुई।</div>
<?php endif; ?>

<!-- Add/Edit Form -->
<div class="card">
    <div class="card-header">
        <?php echo $editData ? '✏️ मुख्य शिक्षक संपादित करें' : '➕ नया मुख्य शिक्षक जोड़ें'; ?>
    </div>
    <form method="POST" action="../actions/mukhyashikshak_save.php">
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
                <label for="username">लॉगिन आईडी (Username) *</label>
                <input type="text" id="username" name="username" class="form-control" required placeholder="लॉगिन के लिए आईडी"
                    value="<?php echo htmlspecialchars($editData['username'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="password">पासवर्ड <?php echo $editData ? '(बदलने के लिए भरें)' : '*'; ?></label>
                <input type="password" id="password" name="password" class="form-control" 
                    placeholder="पासवर्ड" <?php echo $editData ? '' : 'required'; ?>>
            </div>
            <div class="form-group">
                <label for="shakha_id">शाखा चुनें *</label>
                <select id="shakha_id" name="shakha_id" class="form-control" required>
                    <option value="">-- शाखा चुनें --</option>
                    <?php foreach ($allShakhas as $shakha): ?>
                        <option value="<?php echo $shakha['id']; ?>" 
                            <?php echo ($editData && $editData['shakha_id'] == $shakha['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($shakha['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <div class="d-flex gap-1" style="margin-top: 15px;">
            <button type="submit" class="btn btn-primary">💾 सहेजें</button>
            <?php if ($editData): ?>
                <a href="../pages/mukhyashikshaks.php" class="btn btn-outline">❌ रद्द करें</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- List -->
<div class="card">
    <div class="card-header">📋 मुख्य शिक्षकों की सूची (<?php echo count($mukhyashikshaks); ?>)</div>
    <?php if (empty($mukhyashikshaks)): ?>
        <div class="empty-state">
            <div class="icon">👤</div>
            <p>अभी तक कोई मुख्य शिक्षक नहीं जोड़ा गया।</p>
        </div>
    <?php else: ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>नाम</th>
                        <th>लॉगिन आईडी</th>
                        <th>शाखा</th>
                        <th>कार्रवाई</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($mukhyashikshaks as $i => $m): ?>
                        <tr>
                            <td><?php echo $i + 1; ?></td>
                            <td><strong><?php echo htmlspecialchars($m['name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($m['username']); ?></td>
                            <td><?php echo htmlspecialchars($m['shakha_name'] ?: 'None'); ?></td>
                            <td>
                                <div class="table-actions">
                                    <a href="../pages/mukhyashikshaks.php?edit=<?php echo $m['id']; ?>" class="btn btn-sm btn-outline">✏️</a>
                                    <a href="../actions/mukhyashikshak_delete.php?id=<?php echo $m['id']; ?>" class="btn btn-sm btn-danger"
                                        data-confirm="क्या आप इस मुख्य शिक्षक को हटाना चाहते हैं?">🗑️</a>
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
