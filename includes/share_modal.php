<?php
if (!isAdmin()) return;

// Fetch all shakhas
$allShakhas = $pdo->query("SELECT id, name FROM shakhas ORDER BY name")->fetchAll();
?>
<style>
.modal-overlay {
    position: fixed; top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.5); z-index: 9999;
    display: none; justify-content: center; align-items: center;
}
.modal-overlay.active { display: flex; }
.share-modal {
    background: white; width: 90%; max-width: 500px;
    border-radius: 12px; padding: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    animation: slideUp 0.3s ease-out;
}
@keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
.share-modal h2 { margin: 0 0 15px 0; color: #333; font-size: 20px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
.shakha-list {
    max-height: 250px; overflow-y: auto; border: 1px solid #ddd; border-radius: 8px; padding: 10px; margin-bottom: 15px;
}
.shakha-item {
    display: flex; align-items: center; padding: 8px 0; border-bottom: 1px solid #f5f5f5;
}
.shakha-item:last-child { border-bottom: none; }
.shakha-item input { margin-right: 10px; transform: scale(1.2); }
</style>

<div class="modal-overlay" id="shareModalOverlay">
    <div class="share-modal">
        <h2>🔗 अन्य शाखाओं में शेयर करें</h2>
        <div style="margin-bottom: 10px;">
            <label style="font-weight: bold; cursor: pointer;">
                <input type="checkbox" id="selectAllShakhas" onclick="toggleAllShakhas(this)"> सभी चुनें
            </label>
        </div>
        <div class="shakha-list" id="shakhaCheckboxList">
            <?php foreach ($allShakhas as $s): ?>
                <label class="shakha-item">
                    <input type="checkbox" class="shakha-checkbox" value="<?php echo $s['id']; ?>">
                    <?php echo htmlspecialchars($s['name']); ?>
                </label>
            <?php endforeach; ?>
        </div>
        
        <div style="display: flex; justify-content: flex-end; gap: 10px;">
            <button class="btn btn-outline" onclick="closeShareModal()">रद्द करें</button>
            <button class="btn btn-primary" onclick="executeShare()" id="btnExecuteShare">🚀 शेयर करें</button>
        </div>
    </div>
</div>

<script>
let currentShareType = '';
let currentShareId = 0;

function openShareModal(type, id) {
    currentShareType = type;
    currentShareId = id;
    document.getElementById('shareModalOverlay').classList.add('active');
    
    // reset checks
    document.getElementById('selectAllShakhas').checked = false;
    document.querySelectorAll('.shakha-checkbox').forEach(cb => cb.checked = false);
}

function closeShareModal() {
    document.getElementById('shareModalOverlay').classList.remove('active');
}

function toggleAllShakhas(masterCheckbox) {
    document.querySelectorAll('.shakha-checkbox').forEach(cb => {
        cb.checked = masterCheckbox.checked;
    });
}

function executeShare() {
    const selected = [];
    document.querySelectorAll('.shakha-checkbox:checked').forEach(cb => {
        selected.push(cb.value);
    });
    
    if (selected.length === 0) {
        alert("कृपया कम से कम एक शाखा चुनें।");
        return;
    }
    
    const btn = document.getElementById('btnExecuteShare');
    btn.innerText = "⏳ शेयर हो रहा है...";
    btn.disabled = true;
    
    const formData = new FormData();
    formData.append('content_type', currentShareType);
    formData.append('source_id', currentShareId);
    formData.append('target_shakhas', JSON.stringify(selected));
    
    fetch('../api/share_content.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            closeShareModal();
        } else {
            alert("Error: " + data.error);
        }
    })
    .catch(err => {
        alert("Failed to share content.");
        console.error(err);
    })
    .finally(() => {
        btn.innerText = "🚀 शेयर करें";
        btn.disabled = false;
    });
}
</script>
