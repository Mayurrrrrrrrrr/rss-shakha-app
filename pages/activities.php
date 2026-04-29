<?php
/**
 * Activities Management - गतिविधियाँ प्रबंधन
 */
$pageTitle = 'गतिविधियाँ';
require_once '../includes/header.php';
require_once '../config/db.php';
requireLogin();

if (!isMukhyashikshak() && !isAdmin()) {
    header('Location: swayamsevak_dashboard.php');
    exit;
}

$shakhaId = getCurrentShakhaId();
$stmt = $pdo->prepare("SELECT * FROM activities WHERE is_active = 1 AND (shakha_id IS NULL OR shakha_id = ?) ORDER BY sort_order ASC, id ASC");
$stmt->execute([$shakhaId]);
$activities = $stmt->fetchAll();
?>

<div class="page-header">
    <h1>📋 गतिविधियाँ</h1>
    <button type="button" class="btn btn-primary" onclick="openModal('addActivityModal')">➕ नई गतिविधि जोड़ें</button>
</div>

<div class="card">
    <div class="card-header">दैनिक गतिविधियों की सूची</div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">✅
            <?php echo htmlspecialchars($_GET['success']); ?>
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger">⚠️
            <?php echo htmlspecialchars($_GET['error']); ?>
        </div>
    <?php endif; ?>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>नाम</th>
                    <th>क्रम (Order)</th>
                    <th>प्रकार</th>
                    <th>कार्रवाई</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($activities as $act): ?>
                    <tr>
                        <td>
                            <?php echo htmlspecialchars($act['name']); ?>
                        </td>
                        <td>
                            <?php echo $act['sort_order']; ?>
                        </td>
                        <td>
                            <?php if ($act['shakha_id']): ?>
                                <span class="badge badge-saffron">कस्टम</span>
                            <?php else: ?>
                                <span class="badge badge-green">डिफ़ॉल्ट</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="table-actions">
                                <?php if ($act['shakha_id']): ?>
                                    <button type="button" class="btn btn-sm btn-outline"
                                        onclick="editActivity(<?php echo htmlspecialchars(json_encode($act)); ?>)">✏️
                                        एडिट</button>
                                    <form method="POST" action="../actions/activity_save.php" style="display:inline;"
                                        onsubmit="return confirm('क्या आप वाकई इस गतिविधि को हटाना चाहते हैं?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $act['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">🗑️ हटाएं</button>
                                    </form>
                                <?php else: ?>
                                    <span style="font-size: 12px; color: #888;">(संशोधित नहीं किया जा सकता)</span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add/Edit Activity Modal -->
<div id="addActivityModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">नई गतिविधि जोड़ें</h2>
            <button class="close-modal" onclick="closeModal('addActivityModal')">&times;</button>
        </div>
        <form id="activityForm" method="POST" action="../actions/activity_save.php">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="id" id="activityId" value="">

            <div class="form-group">
                <label for="name">गतिविधि का नाम <span style="color:red;">*</span></label>
                <input type="text" id="name" name="name" class="form-control" required placeholder="जैसे: ध्वज प्रणाम">
            </div>

            <div class="form-group">
                <label for="sort_order">क्रम (Sort Order)</label>
                <input type="number" id="sort_order" name="sort_order" class="form-control" value="10">
                <small>छोटी संख्या वाली गतिविधियाँ पहले दिखाई देंगी</small>
            </div>

            <div class="form-actions" style="margin-top: 20px;">
                <button type="button" class="btn btn-outline" onclick="closeModal('addActivityModal')">रद्द
                    करें</button>
                <button type="submit" class="btn btn-primary">💾 सहेजें</button>
            </div>
        </form>
    </div>
</div>

<script>
    function editActivity(act) {
        document.getElementById('modalTitle').innerText = 'गतिविधि एडिट करें';
        document.getElementById('formAction').value = 'edit';
        document.getElementById('activityId').value = act.id;
        document.getElementById('name').value = act.name;
        document.getElementById('sort_order').value = act.sort_order;
        openModal('addActivityModal');
    }

    // Reset form when opening to add new
    const originalOpenModal = window.openModal;
    window.openModal = function (id) {
        if (id === 'addActivityModal' && document.getElementById('formAction').value === 'edit') {
            // If it was opened via edit button, we don't clear.
            // If it's opened via "Add New" button, we clear it.
            if (arguments.length === 1) { // called from Add New button
                document.getElementById('modalTitle').innerText = 'नई गतिविधि जोड़ें';
                document.getElementById('formAction').value = 'add';
                document.getElementById('activityId').value = '';
                document.getElementById('name').value = '';
                document.getElementById('sort_order').value = '10';
            }
        }
        originalOpenModal(id);
    }
</script>

<?php require_once '../includes/footer.php'; ?>