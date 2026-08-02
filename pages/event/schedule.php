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
        <?php foreach($schedules as $item): ?>
            <div style="margin-bottom: 1.5rem; position: relative;">
                <div style="position: absolute; left: -26px; top: 0; width: 10px; height: 10px; border-radius: 50%; background: var(--saffron); border: 2px solid var(--container-bg);"></div>
                <div style="background: var(--container-bg); padding: 1rem; border-radius: 8px; border: 1px solid var(--border-color);">
                    <h3 style="margin: 0 0 0.5rem 0; color: var(--saffron);"><?= htmlspecialchars($item['title']) ?></h3>
                    <div style="font-size: 0.9em; color: var(--text-color); margin-bottom: 0.5rem;">
                        🕒 <?= date('h:i A', strtotime($item['start_time'])) ?> - <?= $item['end_time'] ? date('h:i A', strtotime($item['end_time'])) : '' ?> 
                        | 📅 <?= date('d M Y', strtotime($item['start_time'])) ?>
                    </div>
                    <?php if(!empty($item['description'])): ?>
                        <p style="margin: 0; font-size: 0.95em; opacity: 0.8;"><?= htmlspecialchars($item['description']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if(empty($schedules)): ?>
            <p>कोई सत्र उपलब्ध नहीं है (No sessions available)</p>
        <?php endif; ?>
    </div>
</div>

<div id="addModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000;">
    <div class="card" style="max-width:500px; margin: 10% auto; position:relative;">
        <h3>नया सत्र जोड़ें (Add Session)</h3>
        <span onclick="document.getElementById('addModal').style.display='none'" style="position:absolute; right:1.5rem; top:1.5rem; cursor:pointer; font-size:1.5rem;">&times;</span>
        <form method="POST" action="schedule.php">
            <input type="hidden" name="action" value="add_schedule">
            <div class="form-group">
                <label>शीर्षक (Title)</label>
                <input type="text" name="title" class="form-control" required>
            </div>
            <div class="form-group">
                <label>प्रारंभ समय (Start Time)</label>
                <input type="datetime-local" name="start_time" class="form-control" required>
            </div>
            <div class="form-group">
                <label>समाप्ति समय (End Time)</label>
                <input type="datetime-local" name="end_time" class="form-control">
            </div>
            <div class="form-group">
                <label>विवरण (Description)</label>
                <textarea name="description" class="form-control" rows="3"></textarea>
            </div>
            <button type="submit" class="btn">सुरक्षित करें (Save)</button>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
