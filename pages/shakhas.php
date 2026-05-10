<?php
require_once '../includes/auth.php';
/**
 * Shakhas Management - शाखाएँ प्रबंधन
 */
$pageTitle = 'शाखाएँ';
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
    $stmt = $pdo->prepare("SELECT * FROM shakhas WHERE id = ?");
    $stmt->execute([$editId]);
    $editData = $stmt->fetch();
}

$shakhas = $pdo->query("SELECT s.*, 
    (SELECT COUNT(*) FROM admin_users WHERE role = 'mukhyashikshak' AND shakha_id = s.id) as mukhyashikshak_count,
    (SELECT COUNT(*) FROM swayamsevaks WHERE shakha_id = s.id AND is_active = 1) as swayamsevak_count
    FROM shakhas s ORDER BY name")->fetchAll();
?>

<div class="page-header">
    <h1><img src="../assets/images/flag_icon.png" class="brand-icon" style="height: 1.25em;" alt="🚩" loading="lazy"> शाखा प्रबंधन</h1>
</div>

<?php if ($msg === 'saved'): ?>
    <div class="alert alert-success">✅ शाखा सफलतापूर्वक सहेजी गई!</div>
<?php elseif ($msg === 'deleted'): ?>
    <div class="alert alert-success">✅ शाखा हटाई गई!</div>
<?php elseif ($msg === 'error'): ?>
    <div class="alert alert-danger">❌ कोई त्रुटि हुई।</div>
<?php endif; ?>

<!-- Add/Edit Form -->
<div class="card">
    <div class="card-header">
        <?php echo $editData ? '✏️ शाखा संपादित करें' : '➕ नई शाखा जोड़ें'; ?>
    </div>
    <form method="POST" action="../api/actions/shakha_save.php">
        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
        <?php if ($editData): ?>
            <input type="hidden" name="id" value="<?php echo $editData['id']; ?>">
        <?php endif; ?>

        <div class="form-group">
            <label for="name">शाखा का नाम *</label>
            <input type="text" id="name" name="name" class="form-control" required placeholder="शाखा का नाम"
                value="<?php echo htmlspecialchars($editData['name'] ?? ''); ?>">
        </div>
        <div class="d-flex gap-1" style="margin-top: 15px;">
            <button type="submit" class="btn btn-primary">💾 सहेजें</button>
            <?php if ($editData): ?>
                <a href="../pages/shakhas.php" class="btn btn-outline">❌ रद्द करें</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- List -->
<div class="card">
    <div class="card-header">📋 शाखाओं की सूची (
        <?php echo count($shakhas); ?>)
    </div>
    <?php if (empty($shakhas)): ?>
        <div class="empty-state">
            <div class="icon"><img src="../assets/images/flag_icon.png" class="brand-icon" style="height: 1.5em; opacity: 0.5;" alt="🚩" loading="lazy"></div>
            <p>अभी तक कोई शाखा नहीं जोड़ी गई।</p>
        </div>
    <?php else: ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>नाम</th>
                        <th>मुख्य शिक्षक</th>
                        <th>स्वयंसेवक</th>
                        <th>कार्रवाई</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($shakhas as $i => $s): ?>
                        <tr>
                            <td>
                                <?php echo $i + 1; ?>
                            </td>
                            <td><strong>
                                    <?php echo htmlspecialchars($s['name']); ?>
                                </strong></td>
                            <td>
                                <?php echo $s['mukhyashikshak_count']; ?>
                            </td>
                            <td>
                                <?php echo $s['swayamsevak_count']; ?>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <a href="../pages/shakhas.php?edit=<?php echo $s['id']; ?>" class="btn btn-sm btn-outline">✏️</a>
                                    <a href="../api/actions/shakha_delete.php?id=<?php echo $s['id']; ?>" class="btn btn-sm btn-danger"
                                        data-confirm="क्या आप इस शाखा को हटाना चाहते हैं? इससे जुड़े सभी रिकॉर्ड भी हट सकते हैं।">🗑️</a>
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
