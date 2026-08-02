<?php
session_start();
require_once '../../config/db.php';

if (!isset($_SESSION['event_user_id'])) {
    header('Location: login.php');
    exit;
}

$event_id = $_SESSION['event_id'] ?? null;
$message = '';
$error = '';

// Ensure default categories exist
$categories = [];
if ($event_id) {
    try {
        $stmt = $pdo->prepare("SELECT id, name FROM em_work_categories WHERE event_id = ? ORDER BY name");
        $stmt->execute([$event_id]);
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($categories)) {
            $default_cats = ['भोजन व्यवस्था', 'हाजिरी', 'आवास व्यवस्था', 'स्वागत कक्ष'];
            $insert_stmt = $pdo->prepare("INSERT INTO em_work_categories (event_id, name) VALUES (?, ?)");
            foreach ($default_cats as $cat) {
                $insert_stmt->execute([$event_id, $cat]);
            }
            $stmt = $pdo->prepare("SELECT id, name FROM em_work_categories WHERE event_id = ? ORDER BY name");
            $stmt->execute([$event_id]);
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        $error = "श्रेणी प्राप्त करने में त्रुटि (Error fetching categories: " . $e->getMessage() . ")";
    }
}

// Handle Task Assignment (Admin & Coordinator only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'assign_task') {
    if ($_SESSION['event_role'] === 'admin' || $_SESSION['event_role'] === 'coordinator') {
        $organizer_id = $_POST['organizer_id'] ?? null;
        $category_id = $_POST['category_id'] ?? null;
        $description = trim($_POST['description'] ?? '');
        $assignment_date = $_POST['assignment_date'] ?? null;
        $time_slot = trim($_POST['time_slot'] ?? '');

        if ($organizer_id && $category_id && $assignment_date) {
            try {
                $stmt = $pdo->prepare("INSERT INTO em_work_assignments (event_id, organizer_id, work_category_id, description, assignment_date, time_slot, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
                $stmt->execute([$event_id, $organizer_id, $category_id, $description, $assignment_date, $time_slot]);
                $message = "कार्य सफलतापूर्वक सौंपा गया (Task assigned successfully)";
            } catch (PDOException $e) {
                $error = "त्रुटि: (Error: " . $e->getMessage() . ")";
            }
        } else {
            $error = "सभी अनिवार्य फ़ील्ड भरें (Fill all mandatory fields)";
        }
    } else {
        $error = "आपको कार्य सौंपने की अनुमति नहीं है। (You do not have permission to assign tasks.)";
    }
}

// Handle Task Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $task_id = $_POST['task_id'] ?? null;
    $new_status = $_POST['status'] ?? null;
    
    if ($task_id && in_array($new_status, ['pending', 'in_progress', 'completed'])) {
        try {
            $stmt = $pdo->prepare("UPDATE em_work_assignments SET status = ? WHERE id = ? AND event_id = ?");
            $stmt->execute([$new_status, $task_id, $event_id]);
            $message = "कार्य की स्थिति अपडेट की गई (Task status updated)";
        } catch (PDOException $e) {
            $error = "त्रुटि: (Error: " . $e->getMessage() . ")";
        }
    }
}

// Fetch all organizers for dropdown
$organizers = [];
if ($event_id) {
    $stmt = $pdo->prepare("SELECT id, name, role FROM em_organizers WHERE event_id = ? ORDER BY name");
    $stmt->execute([$event_id]);
    $organizers = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch tasks
$tasks = [];
if ($event_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT a.*, o.name as organizer_name, c.name as category_name
            FROM em_work_assignments a
            LEFT JOIN em_organizers o ON a.organizer_id = o.id
            LEFT JOIN em_work_categories c ON a.work_category_id = c.id
            WHERE a.event_id = ?
            ORDER BY a.assignment_date DESC, a.id DESC
        ");
        $stmt->execute([$event_id]);
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error = "कार्य प्राप्त करने में त्रुटि (Error fetching tasks: " . $e->getMessage() . ")";
    }
}
?>
<?php include 'includes/header.php'; ?>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <h2 style="margin: 0; color: var(--saffron);">कार्य प्रबंधन (Task Management)</h2>
        <?php if ($_SESSION['event_role'] === 'admin' || $_SESSION['event_role'] === 'coordinator'): ?>
        <button class="btn" onclick="document.getElementById('assignModal').style.display='block'">+ कार्य सौंपें (Assign Task)</button>
        <?php endif; ?>
    </div>

    <?php if ($message): ?><div style="color: #4caf50; margin-bottom: 1rem;"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div style="color: #f44336; margin-bottom: 1rem;"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <table>
        <thead>
            <tr>
                <th>तारीख़ (Date)</th>
                <th>कार्य श्रेणी (Category)</th>
                <th>विवरण (Description)</th>
                <th>समय (Time)</th>
                <th>किसे सौंपा (Assigned To)</th>
                <th>स्थिति (Status)</th>
                <th>क्रिया (Action)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($tasks as $task): ?>
            <tr>
                <td><?= htmlspecialchars($task['assignment_date']) ?></td>
                <td><?= htmlspecialchars($task['category_name']) ?></td>
                <td><?= htmlspecialchars($task['description']) ?></td>
                <td><?= htmlspecialchars($task['time_slot']) ?></td>
                <td><?= htmlspecialchars($task['organizer_name']) ?></td>
                <td>
                    <?php
                        if ($task['status'] === 'completed') echo '<span style="color:#4caf50;">पूर्ण (Completed)</span>';
                        elseif ($task['status'] === 'in_progress') echo '<span style="color:#ff9800;">प्रगति पर (In Progress)</span>';
                        else echo '<span style="color:#f44336;">लंबित (Pending)</span>';
                    ?>
                </td>
                <td>
                    <form method="POST" style="display: inline-flex; gap: 0.5rem;">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                        <select name="status" class="form-control" style="width: auto; padding: 0.2rem;" onchange="this.form.submit()">
                            <option value="pending" <?= $task['status'] === 'pending' ? 'selected' : '' ?>>लंबित</option>
                            <option value="in_progress" <?= $task['status'] === 'in_progress' ? 'selected' : '' ?>>प्रगति पर</option>
                            <option value="completed" <?= $task['status'] === 'completed' ? 'selected' : '' ?>>पूर्ण</option>
                        </select>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($tasks)): ?>
            <tr><td colspan="7">कोई कार्य नहीं मिला। (No tasks found.)</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal -->
<?php if ($_SESSION['event_role'] === 'admin' || $_SESSION['event_role'] === 'coordinator'): ?>
<div id="assignModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 1000;">
    <div class="card" style="width: 500px; margin: 4rem auto; position: relative; max-height: 90vh; overflow-y: auto;">
        <span onclick="document.getElementById('assignModal').style.display='none'" style="position: absolute; right: 1rem; top: 1rem; cursor: pointer; font-size: 1.5rem;">&times;</span>
        <h3 style="margin-top: 0; color: var(--saffron);">कार्य सौंपें (Assign Task)</h3>
        <form method="POST">
            <input type="hidden" name="action" value="assign_task">
            
            <div class="form-group">
                <label>आयोजक (Organizer) *</label>
                <select name="organizer_id" class="form-control" required>
                    <option value="">-- चुनें (Select) --</option>
                    <?php foreach ($organizers as $org): ?>
                    <option value="<?= $org['id'] ?>"><?= htmlspecialchars($org['name']) ?> (<?= htmlspecialchars($org['role']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>कार्य श्रेणी (Work Category) *</label>
                <select name="category_id" class="form-control" required>
                    <option value="">-- चुनें (Select) --</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>विवरण (Description)</label>
                <textarea name="description" class="form-control" rows="3"></textarea>
            </div>
            
            <div class="form-group">
                <label>तारीख़ (Assignment Date) *</label>
                <input type="date" name="assignment_date" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label>समय (Time Slot)</label>
                <input type="text" name="time_slot" class="form-control" placeholder="e.g. 10:00 AM - 12:00 PM">
            </div>
            
            <button type="submit" class="btn" style="width: 100%; margin-top: 1rem;">सुरक्षित करें (Save)</button>
        </form>
    </div>
</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
