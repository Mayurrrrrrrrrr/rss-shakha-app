<?php
session_start();
require_once '../../config/db.php';
include 'includes/header.php';

if ($_SESSION['event_role'] !== 'admin') {
    echo "<div class='container'><div class='card'><h2 style='color:var(--danger)'>Permission Denied</h2><p>You don't have permission to create events.</p></div></div>";
    include 'includes/footer.php';
    exit;
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'set_active') {
        $evt_id = (int)($_POST['event_id'] ?? 0);
        if ($evt_id > 0) {
            $pdo->exec("UPDATE em_events SET status = 'inactive'");
            $stmt = $pdo->prepare("UPDATE em_events SET status = 'active' WHERE id = ?");
            $stmt->execute([$evt_id]);
            
            // Update session
            $evt = $pdo->query("SELECT id, name FROM em_events WHERE id = $evt_id")->fetch();
            $_SESSION['event_id'] = $evt['id'];
            $_SESSION['event_name'] = $evt['name'];
            $message = "आयोजन '{$evt['name']}' अब सक्रिय है! (Event is now active!)";
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'delete_event') {
        $evt_id = (int)($_POST['event_id'] ?? 0);
        if ($evt_id > 0) {
            $stmt = $pdo->prepare("UPDATE em_events SET status = 'deleted' WHERE id = ?");
            $stmt->execute([$evt_id]);
            $message = "आयोजन सफलतापूर्वक हटा दिया गया (Event deleted successfully).";
            if (isset($_SESSION['event_id']) && $_SESSION['event_id'] == $evt_id) {
                unset($_SESSION['event_id']);
                unset($_SESSION['event_name']);
            }
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'create_event') {
        $name = trim($_POST['name'] ?? '');
        $start_date = $_POST['start_date'] ?? '';
        $end_date = $_POST['end_date'] ?? '';
        $venue = trim($_POST['venue'] ?? '');

        if (empty($name) || empty($start_date)) {
            $error = "Name and Start Date are required.";
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO em_events (name, start_date, end_date, venue, status) VALUES (?, ?, ?, ?, 'inactive')");
                $stmt->execute([$name, $start_date, $end_date, $venue]);
                $message = "नया आयोजन सफलतापूर्वक बनाया गया! (Event created successfully!)";
            } catch (Exception $e) {
                $error = "Database Error: " . $e->getMessage();
            }
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'edit_event') {
        $evt_id = (int)($_POST['edit_event_id'] ?? 0);
        $name = trim($_POST['edit_name'] ?? '');
        $start_date = $_POST['edit_start_date'] ?? '';
        $end_date = $_POST['edit_end_date'] ?? '';
        $venue = trim($_POST['edit_venue'] ?? '');
        
        if ($evt_id > 0 && !empty($name) && !empty($start_date)) {
            try {
                $stmt = $pdo->prepare("UPDATE em_events SET name = ?, start_date = ?, end_date = ?, venue = ? WHERE id = ?");
                $stmt->execute([$name, $start_date, $end_date, $venue, $evt_id]);
                
                // Update session if editing the current active event
                if (isset($_SESSION['event_id']) && $_SESSION['event_id'] == $evt_id) {
                    $_SESSION['event_name'] = $name;
                }
                
                $message = "आयोजन की जानकारी अपडेट की गई! (Event updated successfully!)";
            } catch (Exception $e) {
                $error = "Database Error: " . $e->getMessage();
            }
        } else {
            $error = "Name and Start Date are required.";
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'add_session') {
        $evt_id = (int)($_POST['session_event_id'] ?? 0);
        $s_name = trim($_POST['session_name'] ?? '');
        $s_date = $_POST['session_date'] ?? '';
        
        if ($evt_id > 0 && !empty($s_name) && !empty($s_date)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO em_attendance_sessions (event_id, session_name, session_date) VALUES (?, ?, ?)");
                $stmt->execute([$evt_id, $s_name, $s_date]);
                $message = "सत्र सफलतापूर्वक जोड़ा गया! (Session added successfully!)";
            } catch (Exception $e) {
                $error = "Database Error: " . $e->getMessage();
            }
        } else {
            $error = "Session Name and Date are required.";
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'delete_session') {
        $s_id = (int)($_POST['session_id'] ?? 0);
        if ($s_id > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM em_attendance_sessions WHERE id = ?");
                $stmt->execute([$s_id]);
                $message = "सत्र हटा दिया गया। (Session deleted.)";
            } catch (Exception $e) {
                $error = "Database Error: " . $e->getMessage();
            }
        }
    }
}
?>

<style>
.event-cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 1.5rem;
    margin-top: 1.5rem;
}
.event-card {
    background: var(--card-bg);
    border: 1px solid rgba(255, 255, 255, 0.05);
    border-radius: var(--radius-lg);
    padding: 1.5rem;
    position: relative;
    display: flex;
    flex-direction: column;
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.3s ease;
}
.event-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 40px -12px rgba(0,0,0,0.5);
    border-color: rgba(255, 255, 255, 0.1);
}
.event-card.active-event {
    border: 1px solid rgba(16, 185, 129, 0.3);
    box-shadow: 0 4px 20px rgba(16, 185, 129, 0.1);
}
.event-card.active-event::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0; height: 4px;
    background: linear-gradient(90deg, var(--success), #34D399);
    border-radius: var(--radius-lg) var(--radius-lg) 0 0;
}
.event-status {
    position: absolute;
    top: 1rem; right: 1rem;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.status-active { background: rgba(16, 185, 129, 0.15); color: var(--success); }
.status-inactive { background: rgba(255, 255, 255, 0.1); color: var(--text-muted); }

.event-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--text-color);
    margin-top: 0.5rem;
    margin-bottom: 1rem;
    padding-right: 4rem; /* space for badge */
}
.event-meta {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--text-muted);
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
}
.event-meta svg {
    color: var(--saffron);
    opacity: 0.8;
}
.event-stats {
    display: flex;
    justify-content: space-between;
    margin-top: 1.5rem;
    padding-top: 1rem;
    border-top: 1px dashed rgba(255, 255, 255, 0.1);
}
.stat-box {
    text-align: center;
}
.stat-num {
    font-size: 1.25rem;
    font-weight: 800;
    color: var(--text-color);
    line-height: 1.2;
}
.stat-label {
    font-size: 0.75rem;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.event-actions {
    display: flex;
    gap: 0.5rem;
    margin-top: 1.5rem;
    flex-wrap: wrap;
}
.btn-sm {
    padding: 0.4rem 0.8rem;
    font-size: 0.85rem;
    flex: 1;
    text-align: center;
}
.btn-icon {
    padding: 0.4rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    background: rgba(255,255,255,0.05);
    color: var(--text-muted);
    border: 1px solid rgba(255,255,255,0.1);
    cursor: pointer;
    transition: all 0.2s;
}
.btn-icon:hover {
    background: rgba(255,255,255,0.1);
    color: var(--text-color);
}
.session-list {
    margin-top: 1rem;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    overflow: hidden;
}
.session-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 1rem;
    border-bottom: 1px solid var(--border-color);
    background: rgba(255,255,255,0.02);
}
.session-item:last-child {
    border-bottom: none;
}
</style>

<div class="container">
    <div class="page-header" style="display:flex; justify-content: space-between; align-items:center; margin-bottom: 2rem;">
        <h2 style="margin:0;">आयोजन प्रबंधन (Event Management)</h2>
        <button class="btn" onclick="document.getElementById('createEventModal').classList.add('active')">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.5rem;"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            नया आयोजन (Create New)
        </button>
    </div>

    <?php if ($error): ?>
        <div style="background: rgba(239, 68, 68, 0.1); color: var(--danger); padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; border: 1px solid var(--danger);">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    
    <?php if ($message): ?>
        <div style="background: rgba(16, 185, 129, 0.1); color: var(--success); padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; border: 1px solid var(--success);">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <!-- Event Cards Grid -->
    <div class="event-cards-grid">
        <?php
        // Fetch events with counts
        $events = $pdo->query("
            SELECT e.*, 
                   (SELECT COUNT(*) FROM em_participants p WHERE p.event_id = e.id AND p.is_deleted = 0) as participants_count,
                   (SELECT COUNT(*) FROM em_organizers o WHERE o.event_id = e.id) as organizers_count
            FROM em_events e 
            WHERE e.status != 'deleted' 
            ORDER BY e.id DESC
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Fetch all sessions grouped by event_id
        $all_sessions = $pdo->query("SELECT * FROM em_attendance_sessions ORDER BY session_date ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
        $sessions_by_event = [];
        foreach ($all_sessions as $sess) {
            $sessions_by_event[$sess['event_id']][] = $sess;
        }

        if (empty($events)): ?>
            <div style="grid-column: 1 / -1; text-align: center; padding: 4rem; background: var(--card-bg); border-radius: var(--radius-lg); border: 1px dashed var(--border-color);">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="color: var(--text-muted); margin-bottom: 1rem;"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                <h3 style="margin-top: 0; color: var(--text-color);">कोई आयोजन नहीं (No Events Found)</h3>
                <p style="color: var(--text-muted);">अभी तक कोई आयोजन नहीं बनाया गया है। नया आयोजन बनाने के लिए ऊपर दिए गए बटन पर क्लिक करें।</p>
            </div>
        <?php endif; ?>

        <?php foreach ($events as $evt): 
            $isActive = ($evt['status'] === 'active');
            $evt_sessions = $sessions_by_event[$evt['id']] ?? [];
        ?>
        <div class="event-card <?= $isActive ? 'active-event' : '' ?>">
            <div class="event-status <?= $isActive ? 'status-active' : 'status-inactive' ?>">
                <?= $isActive ? 'सक्रिय (Active)' : 'निजी (Inactive)' ?>
            </div>
            
            <div class="event-title"><?= htmlspecialchars($evt['name']) ?></div>
            
            <div class="event-meta">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                <?= date('d M Y', strtotime($evt['start_date'])) ?> <?= $evt['end_date'] ? '- ' . date('d M Y', strtotime($evt['end_date'])) : '' ?>
            </div>
            
            <?php if (!empty($evt['venue'])): ?>
            <div class="event-meta">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                <?= htmlspecialchars($evt['venue']) ?>
            </div>
            <?php endif; ?>
            
            <div style="flex-grow: 1;"></div>
            
            <div class="event-stats">
                <div class="stat-box">
                    <div class="stat-num"><?= number_format($evt['participants_count']) ?></div>
                    <div class="stat-label">प्रतिभागी</div>
                </div>
                <div class="stat-box">
                    <div class="stat-num"><?= number_format($evt['organizers_count']) ?></div>
                    <div class="stat-label">आयोजक</div>
                </div>
                <div class="stat-box">
                    <div class="stat-num"><?= count($evt_sessions) ?></div>
                    <div class="stat-label">सत्र (Sessions)</div>
                </div>
            </div>
            
            <div class="event-actions">
                <a href="allocate_participants.php?event_id=<?= $evt['id'] ?>" class="btn btn-outline btn-sm">+ प्रतिभागी</a>
                <a href="allocate_organizers.php?event_id=<?= $evt['id'] ?>" class="btn btn-outline btn-sm">+ आयोजक</a>
            </div>
            
            <div style="display: flex; gap: 0.5rem; margin-top: 0.5rem;">
                <button type="button" class="btn btn-outline btn-sm" style="flex: 2; padding: 0.4rem;" onclick='openSessionModal(<?= htmlspecialchars(json_encode([
                    "event_id" => $evt["id"],
                    "event_name" => $evt["name"],
                    "sessions" => $evt_sessions
                ]), ENT_QUOTES) ?>)'>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:4px; vertical-align:text-bottom;"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                    Manage Sessions
                </button>
            </div>
            
            <div style="display: flex; gap: 0.5rem; margin-top: 0.5rem;">
                <?php if (!$isActive): ?>
                <form method="POST" style="flex: 1;" onsubmit="return confirm('क्या आप इस आयोजन को सक्रिय करना चाहते हैं? (Set this as active?)')">
                    <input type="hidden" name="action" value="set_active">
                    <input type="hidden" name="event_id" value="<?= $evt['id'] ?>">
                    <button type="submit" class="btn btn-sm" style="width: 100%;">सक्रिय करें (Activate)</button>
                </form>
                <?php endif; ?>
                
                <button type="button" class="btn-icon" title="Edit Event" onclick='openEditModal(<?= htmlspecialchars(json_encode([
                    "id" => $evt["id"],
                    "name" => $evt["name"],
                    "start_date" => $evt["start_date"],
                    "end_date" => $evt["end_date"],
                    "venue" => $evt["venue"]
                ]), ENT_QUOTES) ?>)'>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                </button>
                
                <form method="POST" style="display: inline;" onsubmit="return confirm('क्या आप वाकई इस आयोजन को हटाना चाहते हैं? (Are you sure you want to delete this event?)')">
                    <input type="hidden" name="action" value="delete_event">
                    <input type="hidden" name="event_id" value="<?= $evt['id'] ?>">
                    <button type="submit" class="btn-icon" style="color: var(--danger); border-color: rgba(239, 68, 68, 0.3);" title="Delete Event">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                    </button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Create Event Modal -->
<div id="createEventModal" class="modal-overlay" style="display: none;">
    <div class="modal-container">
        <div class="modal-header">
            <h3 class="modal-title">नया आयोजन (Create Event)</h3>
            <button class="modal-close" onclick="document.getElementById('createEventModal').classList.remove('active'); setTimeout(() => document.getElementById('createEventModal').style.display='none', 300);">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="create_event">
            <div class="modal-body">
                <div class="form-group">
                    <label>आयोजन का नाम (Event Name) *</label>
                    <input type="text" name="name" class="form-control" required placeholder="उदा. प्राथमिक शिक्षा वर्ग">
                </div>
                <div class="form-group">
                    <label>प्रारंभ तिथि (Start Date) *</label>
                    <input type="date" name="start_date" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>समापन तिथि (End Date)</label>
                    <input type="date" name="end_date" class="form-control">
                </div>
                <div class="form-group">
                    <label>स्थान (Venue)</label>
                    <input type="text" name="venue" class="form-control" placeholder="उदा. सरस्वती शिशु मंदिर">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('createEventModal').classList.remove('active'); setTimeout(() => document.getElementById('createEventModal').style.display='none', 300);">रद्द करें (Cancel)</button>
                <button type="submit" class="btn">बनाएं (Create)</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Event Modal -->
<div id="editEventModal" class="modal-overlay" style="display: none;">
    <div class="modal-container">
        <div class="modal-header">
            <h3 class="modal-title">आयोजन संपादित करें (Edit Event)</h3>
            <button class="modal-close" onclick="document.getElementById('editEventModal').classList.remove('active'); setTimeout(() => document.getElementById('editEventModal').style.display='none', 300);">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="edit_event">
            <input type="hidden" name="edit_event_id" id="edit_event_id">
            <div class="modal-body">
                <div class="form-group">
                    <label>आयोजन का नाम (Event Name) *</label>
                    <input type="text" name="edit_name" id="edit_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>प्रारंभ तिथि (Start Date) *</label>
                    <input type="date" name="edit_start_date" id="edit_start_date" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>समापन तिथि (End Date)</label>
                    <input type="date" name="edit_end_date" id="edit_end_date" class="form-control">
                </div>
                <div class="form-group">
                    <label>स्थान (Venue)</label>
                    <input type="text" name="edit_venue" id="edit_venue" class="form-control">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('editEventModal').classList.remove('active'); setTimeout(() => document.getElementById('editEventModal').style.display='none', 300);">रद्द करें (Cancel)</button>
                <button type="submit" class="btn">अपडेट करें (Update)</button>
            </div>
        </form>
    </div>
</div>

<!-- Manage Sessions Modal -->
<div id="sessionModal" class="modal-overlay" style="display: none;">
    <div class="modal-container">
        <div class="modal-header">
            <h3 class="modal-title">सत्र प्रबंधन (Manage Sessions)</h3>
            <button class="modal-close" onclick="document.getElementById('sessionModal').classList.remove('active'); setTimeout(() => document.getElementById('sessionModal').style.display='none', 300);">&times;</button>
        </div>
        <div class="modal-body">
            <h4 id="session_event_title" style="margin-top: 0; color: var(--saffron);"></h4>
            
            <form method="POST" style="margin-bottom: 2rem; background: rgba(255,255,255,0.02); padding: 1rem; border-radius: 8px; border: 1px solid rgba(255,255,255,0.05);">
                <input type="hidden" name="action" value="add_session">
                <input type="hidden" name="session_event_id" id="session_event_id">
                
                <h5 style="margin-top: 0; margin-bottom: 1rem; font-size: 0.9rem; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.5px;">नया सत्र जोड़ें (Add New Session)</h5>
                
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label>सत्र का नाम (Session Name) *</label>
                    <input type="text" name="session_name" class="form-control" required placeholder="e.g. Day 1 - Morning">
                </div>
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label>सत्र की तिथि (Session Date) *</label>
                    <input type="date" name="session_date" class="form-control" required>
                </div>
                
                <button type="submit" class="btn" style="width: 100%;">जोड़ें (Add Session)</button>
            </form>
            
            <h5 style="margin-top: 0; margin-bottom: 0.5rem; font-size: 0.9rem; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.5px;">मौजूदा सत्र (Existing Sessions)</h5>
            <div id="session_list_container" class="session-list">
                <!-- Sessions populated via JS -->
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" onclick="document.getElementById('sessionModal').classList.remove('active'); setTimeout(() => document.getElementById('sessionModal').style.display='none', 300);">बंद करें (Close)</button>
        </div>
    </div>
</div>

<script>
// Show Modals on init (display: flex but opacity 0 is handled by active class)
document.getElementById('createEventModal').style.display = 'flex';
document.getElementById('editEventModal').style.display = 'flex';
document.getElementById('sessionModal').style.display = 'flex';

function openEditModal(eventData) {
    document.getElementById('edit_event_id').value = eventData.id;
    document.getElementById('edit_name').value = eventData.name;
    document.getElementById('edit_start_date').value = eventData.start_date;
    document.getElementById('edit_end_date').value = eventData.end_date || '';
    document.getElementById('edit_venue').value = eventData.venue || '';
    
    document.getElementById('editEventModal').style.display = 'flex';
    void document.getElementById('editEventModal').offsetWidth;
    document.getElementById('editEventModal').classList.add('active');
}

function openSessionModal(data) {
    document.getElementById('session_event_id').value = data.event_id;
    document.getElementById('session_event_title').textContent = data.event_name;
    
    const container = document.getElementById('session_list_container');
    container.innerHTML = '';
    
    if (data.sessions && data.sessions.length > 0) {
        data.sessions.forEach(sess => {
            container.innerHTML += `
                <div class="session-item">
                    <div>
                        <div style="font-weight: 600;">${sess.session_name}</div>
                        <div style="font-size: 0.8rem; color: var(--text-muted);">${sess.session_date}</div>
                    </div>
                    <form method="POST" onsubmit="return confirm('क्या आप इस सत्र को हटाना चाहते हैं? (Delete this session?)')" style="margin:0;">
                        <input type="hidden" name="action" value="delete_session">
                        <input type="hidden" name="session_id" value="${sess.id}">
                        <button type="submit" class="btn-icon" style="color: var(--danger); border-color: transparent;" title="Delete">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                        </button>
                    </form>
                </div>
            `;
        });
    } else {
        container.innerHTML = '<div style="padding: 1rem; text-align: center; color: var(--text-muted);">कोई सत्र नहीं मिला (No sessions found)</div>';
    }
    
    document.getElementById('sessionModal').style.display = 'flex';
    void document.getElementById('sessionModal').offsetWidth;
    document.getElementById('sessionModal').classList.add('active');
}
</script>

<?php include 'includes/footer.php'; ?>
