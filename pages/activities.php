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
                    <th style="width: 40px;"></th>
                    <th>नाम</th>
                    <th>क्रम (Order)</th>
                    <th>प्रकार</th>
                    <th>कार्रवाई</th>
                </tr>
            </thead>
            <tbody id="sortable-activities">
                <?php foreach ($activities as $act): ?>
                    <tr class="draggable-row" draggable="true" data-id="<?php echo $act['id']; ?>">
                        <td style="vertical-align: middle; text-align: center;">
                            <span class="drag-handle" style="cursor: grab; font-size: 1.2rem; color: #bbb; padding: 4px 8px; user-select: none;">☰</span>
                        </td>
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
                                <button type="button" class="btn btn-sm btn-outline"
                                    onclick="editActivity(<?php echo htmlspecialchars(json_encode($act)); ?>)">✏️
                                    एडिट</button>
                                <?php if ($act['shakha_id']): ?>
                                    <form method="POST" action="../api/actions/activity_save.php" style="display:inline;"
                                        onsubmit="return confirm('क्या आप वाकई इस गतिविधि को हटाना चाहते हैं?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $act['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">🗑️ हटाएं</button>
                                    </form>
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
        <form id="activityForm" method="POST" action="../api/actions/activity_save.php">
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

    // HTML5 Drag and Drop ordering of activities
    const tbody = document.getElementById('sortable-activities');
    let dragSrcEl = null;

    tbody.querySelectorAll('.draggable-row').forEach(row => {
        row.addEventListener('dragstart', handleDragStart);
        row.addEventListener('dragover', handleDragOver);
        row.addEventListener('dragenter', handleDragEnter);
        row.addEventListener('dragleave', handleDragLeave);
        row.addEventListener('drop', handleDrop);
        row.addEventListener('dragend', handleDragEnd);
    });

    function handleDragStart(e) {
        this.style.opacity = '0.5';
        dragSrcEl = this;
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/html', this.innerHTML);
        this.classList.add('dragging');
    }

    function handleDragOver(e) {
        if (e.preventDefault) {
            e.preventDefault();
        }
        e.dataTransfer.dropEffect = 'move';
        return false;
    }

    function handleDragEnter(e) {
        this.classList.add('over');
    }

    function handleDragLeave(e) {
        this.classList.remove('over');
    }

    function handleDrop(e) {
        if (e.stopPropagation) {
            e.stopPropagation();
        }
        
        if (dragSrcEl !== this) {
            let allRows = Array.from(tbody.querySelectorAll('.draggable-row'));
            let srcIndex = allRows.indexOf(dragSrcEl);
            let targetIndex = allRows.indexOf(this);
            
            if (srcIndex < targetIndex) {
                this.parentNode.insertBefore(dragSrcEl, this.nextSibling);
            } else {
                this.parentNode.insertBefore(dragSrcEl, this);
            }
            
            saveNewOrder();
        }
        return false;
    }

    function handleDragEnd(e) {
        this.style.opacity = '1.0';
        tbody.querySelectorAll('.draggable-row').forEach(row => {
            row.classList.remove('over');
            row.classList.remove('dragging');
        });
    }
    
    function saveNewOrder() {
        const rows = Array.from(tbody.querySelectorAll('.draggable-row'));
        const orderIds = rows.map(r => r.getAttribute('data-id'));
        
        showToast('⏳ क्रम सहेजा जा रहा है...');
        
        const formData = new FormData();
        formData.append('csrf_token', '<?php echo csrf_token(); ?>');
        formData.append('order_ids', JSON.stringify(orderIds));
        
        fetch('../api/actions/activities_reorder.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showToast('✅ क्रम सफलतापूर्वक अपडेट किया गया!', 2000);
                rows.forEach((row, index) => {
                    const orderCell = row.cells[2];
                    if (orderCell) {
                        orderCell.textContent = (index + 1) * 10;
                    }
                });
            } else {
                showToast('⚠️ त्रुटि: ' + data.message, 3000);
            }
        })
        .catch(err => {
            console.error(err);
            showToast('⚠️ नेटवर्क त्रुटि हुई', 3000);
        });
    }
    
    function showToast(message, duration = 3000) {
        let toast = document.getElementById('sort-toast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'sort-toast';
            toast.style.position = 'fixed';
            toast.style.bottom = '80px';
            toast.style.left = '50%';
            toast.style.transform = 'translateX(-50%)';
            toast.style.background = '#4E342E';
            toast.style.color = '#fff';
            toast.style.padding = '10px 20px';
            toast.style.borderRadius = '20px';
            toast.style.zIndex = '9999';
            toast.style.boxShadow = '0 4px 12px rgba(0,0,0,0.2)';
            toast.style.fontSize = '14px';
            toast.style.fontWeight = 'bold';
            toast.style.transition = 'opacity 0.3s ease';
            document.body.appendChild(toast);
        }
        toast.textContent = message;
        toast.style.opacity = '1';
        
        if (duration > 0) {
            setTimeout(() => {
                toast.style.opacity = '0';
            }, duration);
        }
    }
});
</script>

<style>
.draggable-row {
    transition: background-color 0.2s ease;
}
.draggable-row.dragging {
    background-color: #FFF3E0 !important;
    border: 2px dashed #FF6B00;
}
.draggable-row.over {
    border-top: 3px solid #FF6B00;
}
.drag-handle:hover {
    color: #FF6B00 !important;
}
</style>
<?php require_once '../includes/footer.php'; ?>
