<?php
require_once '../includes/auth.php';
/**
 * Swayamsevak Management - स्वयंसेवक प्रबंधन
 */
$pageTitle = 'स्वयंसेवक';
require_once '../includes/header.php';
require_once '../config/db.php';
requireLogin();

$isReadOnly = isSwayamsevak();

$msg = $_GET['msg'] ?? '';
$editId = $_GET['edit'] ?? null;
$editData = null;
$shakhaId = getCurrentShakhaId();

if ($editId) {
    $stmt = $pdo->prepare("SELECT * FROM swayamsevaks WHERE id = ? AND is_active = 1 AND shakha_id = ?");
    $stmt->execute([$editId, $shakhaId]);
    $editData = $stmt->fetch();
}

$stmt = $pdo->prepare("SELECT * FROM swayamsevaks WHERE is_active = 1 AND shakha_id = ? ORDER BY FIELD(category, 'Baal', 'Tarun', 'Praudh', 'Abhyagat'), COALESCE(NULLIF(gat, ''), 'zzzzzzzz') ASC, is_gat_nayak DESC, name ASC");
$stmt->execute([$shakhaId]);
$swayamsevaks = $stmt->fetchAll();

// Fetch active categories and customizable roles for this Shakha
$stmtShakha = $pdo->prepare("SELECT shakha_gat, shakha_roles FROM shakhas WHERE id = ?");
$stmtShakha->execute([$shakhaId]);
$shakhaDetails = $stmtShakha->fetch();
$activeGats = !empty($shakhaDetails['shakha_gat']) ? explode(',', $shakhaDetails['shakha_gat']) : ['Baal', 'Tarun', 'Praudh', 'Abhyagat'];
$shakhaRoles = !empty($shakhaDetails['shakha_roles']) ? array_map('trim', explode(',', $shakhaDetails['shakha_roles'])) : ['Swayamsevak', 'Seva Karyakarta', 'Mukhya Shikshak', 'Shakha Karyavaah', 'Gat Nayak'];
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
<?php if (!$isReadOnly): ?>
<div class="card">
    <div class="card-header">
        <?php echo $editData ? '✏️ स्वयंसेवक संपादित करें' : '➕ नया स्वयंसेवक जोड़ें'; ?>
    </div>
    <form method="POST" action="../api/actions/swayamsevak_save.php">
        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
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
                    <?php
                    $catMap = ['Baal' => 'बाल', 'Tarun' => 'तरुण', 'Praudh' => 'प्रौढ़', 'Abhyagat' => 'अभ्यागत'];
                    $showGats = $activeGats;
                    if ($editData && !empty($editData['category']) && !in_array($editData['category'], $showGats)) {
                        $showGats[] = $editData['category'];
                    }
                    foreach ($showGats as $cat):
                        $label = $catMap[$cat] ?? $cat;
                        $selected = (($editData['category'] ?? '') === $cat) ? 'selected' : '';
                        if (!$editData && $cat === 'Tarun' && in_array('Tarun', $activeGats)) {
                            $selected = 'selected';
                        }
                    ?>
                        <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $selected; ?>><?php echo htmlspecialchars($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="role">भूमिका / पद</label>
                <select id="role" name="role" class="form-control">
                    <?php foreach ($shakhaRoles as $r): 
                        $selected = (($editData['role'] ?? 'Swayamsevak') === $r) ? 'selected' : '';
                    ?>
                        <option value="<?php echo htmlspecialchars($r); ?>" <?php echo $selected; ?>><?php echo htmlspecialchars($r); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="gat">गट (Gat)</label>
                <input type="text" id="gat" name="gat" class="form-control" placeholder="गट का नाम (उदा. शिवाजी गट)"
                    value="<?php echo htmlspecialchars($editData['gat'] ?? ''); ?>">
            </div>
            <div class="form-group" style="display: flex; align-items: center; margin-top: auto; padding-bottom: 8px;">
                <label class="checkbox-item" style="cursor: pointer; display: flex; align-items: center; gap: 8px;">
                    <input type="checkbox" id="is_gat_nayak" name="is_gat_nayak" value="1" style="width: 18px; height: 18px; cursor: pointer;"
                        <?php echo (!empty($editData['is_gat_nayak'])) ? 'checked' : ''; ?>>
                    <span class="checkbox-label" style="font-weight: 500; font-size: 0.95rem; color: #4A1C00;">गट नायक (Gat Nayak)</span>
                </label>
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
<?php endif; ?>

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
        <?php if (!$isReadOnly): ?>
        <form method="POST" action="../api/actions/swayamsevaks_batch_gat.php" id="batch-gat-form">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            
            <!-- Batch controls -->
            <div style="background: #FFF3E0; padding: 12px 16px; border-radius: 6px; margin-bottom: 16px; display: flex; flex-wrap: wrap; gap: 12px; align-items: center; border: 1px solid #FFCC80;">
                <span style="font-weight: bold; color: #E64A19; font-size: 0.95rem;">🔄 बहु-चयन गट अपडेट (Batch Update):</span>
                <input type="text" name="batch_gat" class="form-control" placeholder="गट का नाम दर्ज करें (उदा. शिवाजी गट)" style="max-width: 250px; margin: 0; padding: 6px 12px; height: auto;" required>
                <button type="submit" class="btn btn-primary" style="padding: 6px 16px; height: auto; font-size: 0.95rem;">गट बदलें / असाइन करें</button>
            </div>
        <?php endif; ?>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <?php if (!$isReadOnly): ?>
                            <th style="width: 40px; text-align: center;"><input type="checkbox" id="select-all" style="width: 18px; height: 18px; cursor: pointer;"></th>
                            <?php endif; ?>
                            <th>#</th>
                            <th>नाम</th>
                            <th>भूमिका / पद</th>
                            <th>गट (गट नायक)</th>
                            <th>श्रेणी</th>
                            <?php if (!$isReadOnly): ?>
                            <th>फ़ोन</th>
                            <?php endif; ?>
                            <th>उम्र</th>
                            <?php if (!$isReadOnly): ?>
                            <th>पता</th>
                            <th>कार्रवाई</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($swayamsevaks as $i => $s): ?>
                            <tr>
                                <?php if (!$isReadOnly): ?>
                                <td style="text-align: center;">
                                    <input type="checkbox" name="selected_ids[]" value="<?php echo $s['id']; ?>" class="swayamsevak-select" style="width: 18px; height: 18px; cursor: pointer;">
                                </td>
                                <?php endif; ?>
                                <td>
                                    <?php echo $i + 1; ?>
                                </td>
                                <td><strong>
                                        <?php echo htmlspecialchars($s['name']); ?>
                                    </strong></td>
                                <td>
                                    <?php echo htmlspecialchars($s['role'] ?? 'Swayamsevak'); ?>
                                </td>
                                <td>
                                    <?php 
                                    if (!empty($s['gat'])) {
                                        echo htmlspecialchars($s['gat']);
                                        if ($s['is_gat_nayak']) {
                                            echo ' <span class="badge" style="background:#E65100; color:#fff; font-size: 0.75rem; padding: 2px 6px; border-radius: 4px; margin-left: 5px; font-weight: bold; display: inline-block;">गट नायक</span>';
                                        }
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    $catMap = ['Baal' => 'बाल', 'Tarun' => 'तरुण', 'Praudh' => 'प्रौढ़', 'Abhyagat' => 'अभ्यागत'];
                                    echo $catMap[$s['category'] ?? 'Tarun'] ?? 'तरुण'; 
                                    ?>
                                </td>
                                <?php if (!$isReadOnly): ?>
                                <td>
                                    <?php echo htmlspecialchars($s['phone'] ?: '-'); ?>
                                </td>
                                <?php endif; ?>
                                <td>
                                    <?php echo $s['age'] ?: '-'; ?>
                                </td>
                                <?php if (!$isReadOnly): ?>
                                <td>
                                    <?php echo htmlspecialchars($s['address'] ?: '-'); ?>
                                </td>
                                <td>
                                    <div class="table-actions">
                                        <a href="../pages/swayamsevaks.php?edit=<?php echo $s['id']; ?>"
                                            class="btn btn-sm btn-outline">✏️</a>
                                        <a href="../api/actions/swayamsevak_delete.php?id=<?php echo $s['id']; ?>&csrf_token=<?php echo csrf_token(); ?>" class="btn btn-sm btn-danger"
                                            data-confirm="क्या आप इस स्वयंसेवक को हटाना चाहते हैं?">🗑️</a>
                                    </div>
                                </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php if (!$isReadOnly): ?>
        </form>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('select-all');
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.swayamsevak-select');
            checkboxes.forEach(cb => cb.checked = this.checked);
        });
    }

    const form = document.getElementById('batch-gat-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            const checked = document.querySelectorAll('.swayamsevak-select:checked');
            if (checked.length === 0) {
                e.preventDefault();
                alert('कृपया पहले कम से कम एक स्वयंसेवक चुनें।');
            }
        });
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>
