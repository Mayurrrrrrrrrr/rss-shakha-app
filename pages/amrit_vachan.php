<?php
require_once '../includes/auth.php';
require_once '../config/db.php';
requireLogin();

if (!isMukhyashikshak() && !isAdmin()) {
    header('Location: dashboard.php');
    exit;
}

// Auto-create table
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS amrit_vachan (
        id INT AUTO_INCREMENT PRIMARY KEY,
        shakha_id INT NOT NULL,
        content TEXT NOT NULL,
        author VARCHAR(255),
        vachan_date DATE NOT NULL,
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (shakha_id) REFERENCES shakhas(id) ON DELETE CASCADE,
        FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
} catch (PDOException $e) {
    // table already exists or other error
}

$shakhaId = getCurrentShakhaId();
$success = '';
$error = '';

// Handle Delete
if (isset($_POST['delete_id'])) {
    $stmt = $pdo->prepare("UPDATE amrit_vachan SET is_deleted = 1, updated_at = NOW() WHERE id = ? AND (shakha_id = ? OR ? = 1)");
    $stmt->execute([$_POST['delete_id'], $shakhaId, isAdmin() ? 1 : 0]);
    $success = 'अमृत वचन हटा दिया गया है।';
}

// Handle Add/Edit
if (isset($_POST['save_vachan'])) {
    $content = trim($_POST['content']);
    $author = trim($_POST['author']);
    $vachan_date = $_POST['vachan_date'] ?: date('Y-m-d');
    $id = $_POST['id'] ?? null;

    if (empty($content)) {
        $error = 'सामग्री अनिवार्य है।';
    } else {
        if ($id) {
            $stmt = $pdo->prepare("UPDATE amrit_vachan SET content = ?, author = ?, vachan_date = ?, updated_at = NOW() WHERE id = ? AND (shakha_id = ? OR ? = 1)");
            $stmt->execute([$content, $author, $vachan_date, $id, $shakhaId, isAdmin() ? 1 : 0]);
            $success = 'अमृत वचन अपडेट कर दिया गया है।';
        } else {
            $stmt = $pdo->prepare("INSERT INTO amrit_vachan (shakha_id, content, author, vachan_date, created_by, updated_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$shakhaId, $content, $author, $vachan_date, $_SESSION['user_id']]);
            $success = 'नया अमृत वचन जोड़ दिया गया है।';
        }
    }
}

$pageTitle = 'अमृत वचन';
require_once '../includes/header.php';

// Fetch items for all (Admin and Mukhya Shikshak see all now, but can only edit their own)
$stmt = $pdo->query("SELECT a.*, s.name as shakha_name FROM amrit_vachan a LEFT JOIN shakhas s ON a.shakha_id = s.id WHERE a.is_deleted = 0 ORDER BY vachan_date DESC LIMIT 30");
$items = $stmt->fetchAll();

$editItem = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM amrit_vachan WHERE id = ? AND is_deleted = 0 AND (shakha_id = ? OR ? = 1)");
    $stmt->execute([$_GET['edit'], $shakhaId, isAdmin() ? 1 : 0]);
    $editItem = $stmt->fetch();
}
?>

<div class="page-header">
    <h1>💎 अमृत वचन (Amrit Vachan)</h1>
</div>

<?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>

<div class="grid-2">
    <div class="card">
        <div class="card-header"><?php echo $editItem ? '✏️ अमृत वचन संपादित करें' : '➕ नया अमृत वचन जोड़ें'; ?></div>
        <form method="POST">
            <?php if ($editItem): ?><input type="hidden" name="id" value="<?php echo $editItem['id']; ?>"><?php endif; ?>
            
            <div class="form-group">
                <label>सामग्री (Content) *</label>
                <textarea name="content" class="form-control" rows="5" required><?php echo $editItem ? htmlspecialchars($editItem['content']) : ''; ?></textarea>
            </div>
            
            <div class="form-group">
                <label>लेखक / वक्ता (Author/Speaker)</label>
                <input type="text" name="author" class="form-control" value="<?php echo $editItem ? htmlspecialchars($editItem['author']) : ''; ?>" placeholder="उदा. स्वामी विवेकानंद">
            </div>
            
            <div class="form-group">
                <label>तारीख</label>
                <input type="date" name="vachan_date" class="form-control" value="<?php echo $editItem ? $editItem['vachan_date'] : date('Y-m-d'); ?>">
            </div>
            
            <div style="margin-top: 20px;">
                <button type="submit" name="save_vachan" class="btn btn-primary"><?php echo $editItem ? 'अपडेट करें' : 'सुरक्षित करें'; ?></button>
                <?php if ($editItem): ?><a href="amrit_vachan.php" class="btn btn-outline">रद्द करें</a><?php endif; ?>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="card-header">📋 अमृत वचन सूची</div>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>तारीख</th>
                        <th>सामग्री</th>
                        <th>शाखा</th>
                        <th>कार्रवाई</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($items)): ?>
                        <tr><td colspan="<?php echo isAdmin() ? 4 : 3; ?>" style="text-align: center;">कोई अमृत वचन नहीं मिला।</td></tr>
                    <?php else: ?>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($item['vachan_date'])); ?></td>
                                <td>
                                    <div style="font-weight: 600;"><?php echo mb_substr(htmlspecialchars($item['content']), 0, 50); ?>...</div>
                                    <?php if ($item['author']): ?><small>— <?php echo htmlspecialchars($item['author']); ?></small><?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($item['shakha_name'] ?? '-'); ?></td>
                                <td>
                                    <?php if ($item['shakha_id'] == $shakhaId || isAdmin()): ?>
                                        <div class="table-actions">
                                            <a href="?edit=<?php echo $item['id']; ?>" class="btn btn-sm btn-outline">✏️</a>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('क्या आप वाकई इसे हटाना चाहते हैं?');">
                                                <input type="hidden" name="delete_id" value="<?php echo $item['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline" style="color: red;">🗑️</button>
                                            </form>
                                        </div>
                                    <?php else: ?>
                                        <div class="table-actions">
                                            <a href="whatsapp://send?text=<?php echo urlencode($item['content'] . ($item['author'] ? "\n— " . $item['author'] : '') . "\n🚩 " . $item['shakha_name']); ?>" class="btn btn-sm btn-whatsapp">📱 Share</a>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
