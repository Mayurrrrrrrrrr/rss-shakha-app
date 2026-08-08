<?php
require_once '../includes/auth.php';
$pageTitle = 'आयोजन (Aayojan Management)';
require_once '../includes/header.php';
require_once '../config/db.php';
requireLogin();

if (!isAdmin()) {
    header("Location: ../login.php");
    exit;
}

// Ensure the tables exist
try {
    $pdo->exec(file_get_contents('../database/migrations/005_event_management.sql'));
} catch (PDOException $e) {
    // Ignore if already exists or fails
}

// Fetch Aayojan Events
$stmt = $pdo->query("SELECT * FROM em_events WHERE is_deleted = 0 ORDER BY start_date DESC");
$events = $stmt->fetchAll();

// Fetch Organizers for these events
$orgStmt = $pdo->query("SELECT o.*, e.name as event_name FROM em_organizers o JOIN em_events e ON o.event_id = e.id WHERE o.is_deleted = 0");
$organizers = $orgStmt->fetchAll();
$organizersByEvent = [];
foreach ($organizers as $org) {
    $organizersByEvent[$org['event_id']][] = $org;
}
?>

<div class="page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h1>📋 आयोजन प्रबंधन (Aayojan Management)</h1>
    <button class="btn btn-primary" onclick="showAddEventModal()">+ नया आयोजन</button>
</div>

<div class="glass-card" style="margin-bottom: 24px; padding: 20px; background: white; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
    <h3 style="margin-top: 0; color: #1565C0;">आयोजनों की सूची</h3>
    <?php if (empty($events)): ?>
        <p>कोई आयोजन नहीं मिला। नया आयोजन जोड़ें।</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f5f5f5; text-align: left;">
                        <th style="padding: 10px; border-bottom: 2px solid #ddd;">नाम</th>
                        <th style="padding: 10px; border-bottom: 2px solid #ddd;">दिनांक</th>
                        <th style="padding: 10px; border-bottom: 2px solid #ddd;">स्थान</th>
                        <th style="padding: 10px; border-bottom: 2px solid #ddd;">स्थिति</th>
                        <th style="padding: 10px; border-bottom: 2px solid #ddd;">आयोजक/प्रबंधक</th>
                        <th style="padding: 10px; border-bottom: 2px solid #ddd;">एक्शन</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($events as $event): ?>
                        <tr style="border-bottom: 1px solid #eee;">
                            <td style="padding: 10px;"><strong><?php echo htmlspecialchars($event['name']); ?></strong></td>
                            <td style="padding: 10px;">
                                <?php echo date('d-m-Y', strtotime($event['start_date'])); ?> 
                                <?php if($event['end_date'] && $event['end_date'] != $event['start_date']) echo " to " . date('d-m-Y', strtotime($event['end_date'])); ?>
                            </td>
                            <td style="padding: 10px;"><?php echo htmlspecialchars($event['venue']); ?></td>
                            <td style="padding: 10px;">
                                <span style="background: #E3F2FD; color: #1976D2; padding: 4px 8px; border-radius: 4px; font-size: 12px;">
                                    <?php echo htmlspecialchars($event['status']); ?>
                                </span>
                            </td>
                            <td style="padding: 10px;">
                                <?php if (!empty($organizersByEvent[$event['id']])): ?>
                                    <ul style="margin: 0; padding-left: 15px; font-size: 13px;">
                                        <?php foreach ($organizersByEvent[$event['id']] as $org): ?>
                                            <li>
                                                <?php echo htmlspecialchars($org['name']); ?> 
                                                <span style="color: #666;">(<?php echo htmlspecialchars($org['username']); ?>)</span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <span style="color: #999; font-size: 13px;">कोई आयोजक नहीं</span>
                                <?php endif; ?>
                                <button onclick="showAddOrganizerModal(<?php echo $event['id']; ?>, '<?php echo htmlspecialchars(addslashes($event['name'])); ?>')" style="margin-top: 5px; font-size: 11px; padding: 2px 6px; background: #4CAF50; color: white; border: none; border-radius: 3px; cursor: pointer;">+ आयोजक जोड़ें</button>
                            </td>
                            <td style="padding: 10px;">
                                <button onclick="deleteEvent(<?php echo $event['id']; ?>)" style="background: #f44336; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer;">हटाएं</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Add Event Modal -->
<div id="addEventModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); align-items: center; justify-content: center;">
    <div class="modal-content" style="background: white; padding: 20px; border-radius: 8px; width: 90%; max-width: 500px;">
        <h2 style="margin-top: 0;">नया आयोजन जोड़ें</h2>
        <form id="addEventForm" onsubmit="submitEvent(event)">
            <input type="hidden" name="action" value="add_event">
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px;">आयोजन का नाम *</label>
                <input type="text" name="name" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
            </div>
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px;">स्थान (Venue)</label>
                <input type="text" name="venue" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
            </div>
            <div style="display: flex; gap: 15px; margin-bottom: 15px;">
                <div style="flex: 1;">
                    <label style="display: block; margin-bottom: 5px;">प्रारंभ दिनांक *</label>
                    <input type="date" name="start_date" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
                </div>
                <div style="flex: 1;">
                    <label style="display: block; margin-bottom: 5px;">समाप्ति दिनांक</label>
                    <input type="date" name="end_date" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
                </div>
            </div>
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px;">स्थिति</label>
                <select name="status" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
                    <option value="draft">Draft</option>
                    <option value="active" selected>Active</option>
                    <option value="completed">Completed</option>
                </select>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" onclick="closeModal('addEventModal')" style="padding: 8px 15px; border: 1px solid #ccc; background: white; border-radius: 4px; cursor: pointer;">रद्द करें</button>
                <button type="submit" style="padding: 8px 15px; background: #1976D2; color: white; border: none; border-radius: 4px; cursor: pointer;">सेव करें</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Organizer Modal -->
<div id="addOrganizerModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); align-items: center; justify-content: center;">
    <div class="modal-content" style="background: white; padding: 20px; border-radius: 8px; width: 90%; max-width: 500px;">
        <h2 style="margin-top: 0;">आयोजक जोड़ें - <span id="orgEventName"></span></h2>
        <form id="addOrganizerForm" onsubmit="submitOrganizer(event)">
            <input type="hidden" name="action" value="add_organizer">
            <input type="hidden" name="event_id" id="orgEventId">
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px;">नाम *</label>
                <input type="text" name="name" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
            </div>
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px;">फ़ोन</label>
                <input type="text" name="phone" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
            </div>
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px;">यूज़रनेम (लॉगिन के लिए) *</label>
                <input type="text" name="username" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
            </div>
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px;">पासवर्ड *</label>
                <input type="text" name="password" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
            </div>
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px;">भूमिका (Role)</label>
                <select name="role" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
                    <option value="admin">Admin</option>
                    <option value="coordinator">Coordinator</option>
                    <option value="volunteer" selected>Volunteer</option>
                </select>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" onclick="closeModal('addOrganizerModal')" style="padding: 8px 15px; border: 1px solid #ccc; background: white; border-radius: 4px; cursor: pointer;">रद्द करें</button>
                <button type="submit" style="padding: 8px 15px; background: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer;">आयोजक बनाएं</button>
            </div>
        </form>
    </div>
</div>

<script>
function showAddEventModal() {
    document.getElementById('addEventModal').style.display = 'flex';
}

function showAddOrganizerModal(eventId, eventName) {
    document.getElementById('orgEventId').value = eventId;
    document.getElementById('orgEventName').textContent = eventName;
    document.getElementById('addOrganizerModal').style.display = 'flex';
}

function closeModal(id) {
    document.getElementById(id).style.display = 'none';
}

async function submitEvent(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    try {
        const res = await fetch('../api/actions/manage_aayojan.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Error adding event');
        }
    } catch (err) {
        alert('Connection error');
    }
}

async function submitOrganizer(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    try {
        const res = await fetch('../api/actions/manage_aayojan.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Error adding organizer');
        }
    } catch (err) {
        alert('Connection error');
    }
}

async function deleteEvent(id) {
    if (!confirm('क्या आप वाकई इस आयोजन को हटाना चाहते हैं? आयोजक और प्रतिभागी भी हटा दिए जाएंगे।')) return;
    const formData = new FormData();
    formData.append('action', 'delete_event');
    formData.append('id', id);
    try {
        const res = await fetch('../api/actions/manage_aayojan.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Error deleting event');
        }
    } catch (err) {
        alert('Connection error');
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>
