<?php
session_start();
require_once '../../config/db.php';

// Handle Add Schedule POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_schedule') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $start_datetime = $_POST['start_time'] ?? '';
    $end_datetime = $_POST['end_time'] ?? '';
    $event_id = $_SESSION['event_id'] ?? 1;
    
    if ($title && $start_datetime) {
        $activity_date = date('Y-m-d', strtotime($start_datetime));
        $start_time = date('H:i:s', strtotime($start_datetime));
        $end_time = $end_datetime ? date('H:i:s', strtotime($end_datetime)) : null;

        $stmt = $pdo->prepare("INSERT INTO em_schedule (event_id, activity_name, activity_date, start_time, end_time, description) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$event_id, $title, $activity_date, $start_time, $end_time, $description]);
    }
    header("Location: schedule.php");
    exit;
}

// Handle Edit Schedule POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_schedule') {
    $id = (int)($_POST['id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $start_datetime = $_POST['start_time'] ?? '';
    $end_datetime = $_POST['end_time'] ?? '';
    $event_id = $_SESSION['event_id'] ?? 1;
    
    if ($id && $title && $start_datetime) {
        $activity_date = date('Y-m-d', strtotime($start_datetime));
        $start_time = date('H:i:s', strtotime($start_datetime));
        $end_time = $end_datetime ? date('H:i:s', strtotime($end_datetime)) : null;

        $stmt = $pdo->prepare("UPDATE em_schedule SET activity_name = ?, activity_date = ?, start_time = ?, end_time = ?, description = ? WHERE id = ? AND event_id = ?");
        $stmt->execute([$title, $activity_date, $start_time, $end_time, $description, $id, $event_id]);
    }
    header("Location: schedule.php");
    exit;
}

// Handle Delete Schedule POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_schedule') {
    $id = (int)($_POST['id'] ?? 0);
    $event_id = $_SESSION['event_id'] ?? 1;
    
    if ($id) {
        $stmt = $pdo->prepare("DELETE FROM em_schedule WHERE id = ? AND event_id = ?");
        $stmt->execute([$id, $event_id]);
    }
    header("Location: schedule.php");
    exit;
}

include 'includes/header.php';

try {
    $schedules = $pdo->query("SELECT id, activity_name AS title, description, activity_date, start_time, end_time FROM em_schedule ORDER BY activity_date ASC, start_time ASC")->fetchAll();
} catch (Exception $e) {
    // Fallback if table doesn't exist
    $schedules = [];
}
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
    <h2>कार्यक्रम अनुसूची (Event Schedule)</h2>
    <button class="btn" onclick="document.getElementById('addModal').style.display='block'">नया सत्र जोड़ें (Add Session)</button>
</div>

<div class="card">
    <div class="timeline" style="position: relative; padding-left: 20px; border-left: 2px solid var(--saffron);">
        <?php foreach($schedules as $item): 
            $start_dt = $item['activity_date'] . 'T' . substr($item['start_time'], 0, 5);
            $end_dt = $item['end_time'] ? ($item['activity_date'] . 'T' . substr($item['end_time'], 0, 5)) : '';
        ?>
            <div style="margin-bottom: 1.5rem; position: relative;">
                <div style="position: absolute; left: -26px; top: 0; width: 10px; height: 10px; border-radius: 50%; background: var(--saffron); border: 2px solid var(--container-bg);"></div>
                <div style="background: var(--container-bg); padding: 1rem; border-radius: 8px; border: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem;">
                    <div>
                        <h3 style="margin: 0 0 0.5rem 0; color: var(--saffron);"><?= htmlspecialchars($item['title']) ?></h3>
                        <div style="font-size: 0.9em; color: var(--text-color); margin-bottom: 0.5rem;">
                            🕒 <?= date('h:i A', strtotime($item['start_time'])) ?> - <?= $item['end_time'] ? date('h:i A', strtotime($item['end_time'])) : '' ?> 
                            | 📅 <?= date('d M Y', strtotime($item['activity_date'])) ?>
                        </div>
                        <?php if(!empty($item['description'])): ?>
                            <p style="margin: 0; font-size: 0.95em; opacity: 0.8;"><?= htmlspecialchars($item['description']) ?></p>
                        <?php endif; ?>
                    </div>
                    <div style="display: flex; gap: 0.5rem;">
                        <button class="btn btn-outline" style="padding: 0.3rem 0.6rem; font-size: 0.8rem;" 
                                onclick="openEditModal(<?= $item['id'] ?>, '<?= htmlspecialchars($item['title'], ENT_QUOTES) ?>', '<?= htmlspecialchars($item['description'], ENT_QUOTES) ?>', '<?= $start_dt ?>', '<?= $end_dt ?>')">
                            संपादित करें (Edit)
                        </button>
                        <form method="POST" action="schedule.php" style="display: inline;" onsubmit="return confirm('क्या आप इस सत्र को हटाना चाहते हैं? (Delete this session?)')">
                            <input type="hidden" name="action" value="delete_schedule">
                            <input type="hidden" name="id" value="<?= $item['id'] ?>">
                            <button type="submit" class="btn btn-outline" style="padding: 0.3rem 0.6rem; font-size: 0.8rem; border-color: var(--danger); color: var(--danger);">
                                हटाएं (Delete)
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if(empty($schedules)): ?>
            <p>कोई सत्र उपलब्ध नहीं है (No sessions available)</p>
        <?php endif; ?>
    </div>
</div>

<!-- Add Modal -->
<div id="addModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000;">
    <div class="card" style="max-width:500px; margin: 10% auto; position:relative;">
        <h3>नया सत्र जोड़ें (Add Session)</h3>
        <span onclick="document.getElementById('addModal').style.display='none'" style="position:absolute; right:1.5rem; top:1.5rem; cursor:pointer; font-size:1.5rem;">&times;</span>
        <form method="POST" action="schedule.php">
            <input type="hidden" name="action" value="add_schedule">
            <div class="form-group" style="margin-bottom: 1rem;">
                <label>शीर्षक (Title)</label>
                <input type="text" name="title" class="form-control" required>
            </div>
            <div class="form-group" style="margin-bottom: 1rem;">
                <label>प्रारंभ समय (Start Time)</label>
                <input type="datetime-local" name="start_time" class="form-control" required>
            </div>
            <div class="form-group" style="margin-bottom: 1rem;">
                <label>समाप्ति समय (End Time)</label>
                <input type="datetime-local" name="end_time" class="form-control">
            </div>
            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label>विवरण (Description)</label>
                <textarea name="description" class="form-control" rows="3"></textarea>
            </div>
            <button type="submit" class="btn">सुरक्षित करें (Save)</button>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000;">
    <div class="card" style="max-width:500px; margin: 10% auto; position:relative;">
        <h3>सत्र संपादित करें (Edit Session)</h3>
        <span onclick="document.getElementById('editModal').style.display='none'" style="position:absolute; right:1.5rem; top:1.5rem; cursor:pointer; font-size:1.5rem;">&times;</span>
        <form method="POST" action="schedule.php">
            <input type="hidden" name="action" value="edit_schedule">
            <input type="hidden" name="id" id="edit_id">
            <div class="form-group" style="margin-bottom: 1rem;">
                <label>शीर्षक (Title)</label>
                <input type="text" name="title" id="edit_title" class="form-control" required>
            </div>
            <div class="form-group" style="margin-bottom: 1rem;">
                <label>प्रारंभ समय (Start Time)</label>
                <input type="datetime-local" name="start_time" id="edit_start_time" class="form-control" required>
            </div>
            <div class="form-group" style="margin-bottom: 1rem;">
                <label>समाप्ति समय (End Time)</label>
                <input type="datetime-local" name="end_time" id="edit_end_time" class="form-control">
            </div>
            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label>विवरण (Description)</label>
                <textarea name="description" id="edit_description" class="form-control" rows="3"></textarea>
            </div>
            <button type="submit" class="btn">अपडेट करें (Update)</button>
        </form>
    </div>
</div>

<script>
function openEditModal(id, title, desc, start, end) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_title').value = title;
    document.getElementById('edit_description').value = desc;
    document.getElementById('edit_start_time').value = start;
    document.getElementById('edit_end_time').value = end;
    document.getElementById('edit_modal_btn'); // Just reference
    document.getElementById('editModal').style.display = 'block';
}
</script>

<?php include 'includes/footer.php'; ?>
