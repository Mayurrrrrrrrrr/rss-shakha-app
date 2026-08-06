<?php
session_start();
require_once '../../config/db.php';

// Check admin role
if (!isset($_SESSION['event_user_id']) || !isset($_SESSION['event_role']) || $_SESSION['event_role'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

$event_id = $_SESSION['event_id'] ?? 1;
$message = '';
$message_type = '';

// Create table if not exists
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS em_rooms (
        id INT AUTO_INCREMENT PRIMARY KEY,
        event_id INT NOT NULL DEFAULT 1,
        room_name VARCHAR(100) NOT NULL,
        building VARCHAR(100),
        capacity INT DEFAULT 0,
        occupancy INT DEFAULT 0,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
} catch (PDOException $e) {
    // Ignore error
}

// Handle Form Submission
// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_room') {
        $room_name = trim($_POST['room_name'] ?? '');
        $building = trim($_POST['building'] ?? '');
        $capacity = (int)($_POST['capacity'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');

        if ($room_name && $capacity > 0) {
            try {
                $stmt = $pdo->prepare("INSERT INTO em_rooms (event_id, room_name, building, capacity, occupancy, notes) VALUES (?, ?, ?, ?, 0, ?)");
                $stmt->execute([$event_id, $room_name, $building, $capacity, $notes]);
                $message = 'कमरा सफलतापूर्वक जोड़ा गया! (Room added successfully!)';
                $message_type = 'success';
            } catch (Exception $e) {
                $message = 'त्रुटि: ' . $e->getMessage();
                $message_type = 'error';
            }
        } else {
            $message = 'कृपया सभी अनिवार्य विवरण भरें (Please fill required details).';
            $message_type = 'error';
        }
    } elseif ($_POST['action'] === 'edit_room') {
        $id = (int)($_POST['id'] ?? 0);
        $room_name = trim($_POST['room_name'] ?? '');
        $building = trim($_POST['building'] ?? '');
        $capacity = (int)($_POST['capacity'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');

        if ($id && $room_name && $capacity > 0) {
            try {
                $stmt = $pdo->prepare("UPDATE em_rooms SET room_name = ?, building = ?, capacity = ?, notes = ? WHERE id = ? AND event_id = ?");
                $stmt->execute([$room_name, $building, $capacity, $notes, $id, $event_id]);
                $message = 'कमरा सफलतापूर्वक अद्यतन किया गया! (Room updated successfully!)';
                $message_type = 'success';
            } catch (Exception $e) {
                $message = 'त्रुटि: ' . $e->getMessage();
                $message_type = 'error';
            }
        } else {
            $message = 'कृपया सभी अनिवार्य विवरण भरें (Please fill required details).';
            $message_type = 'error';
        }
    } elseif ($_POST['action'] === 'delete_room') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            try {
                // Check occupancy
                $stmt = $pdo->prepare("SELECT occupancy FROM em_rooms WHERE id = ? AND event_id = ?");
                $stmt->execute([$id, $event_id]);
                $room = $stmt->fetch();
                if ($room && $room['occupancy'] > 0) {
                    $message = 'कमरा नहीं हटाया जा सकता, यह अभी भरा हुआ है (Cannot delete room, it is currently occupied).';
                    $message_type = 'error';
                } else {
                    $stmt = $pdo->prepare("DELETE FROM em_rooms WHERE id = ? AND event_id = ?");
                    $stmt->execute([$id, $event_id]);
                    $message = 'कमरा सफलतापूर्वक हटाया गया! (Room deleted successfully!)';
                    $message_type = 'success';
                }
            } catch (Exception $e) {
                $message = 'त्रुटि: ' . $e->getMessage();
                $message_type = 'error';
            }
        }
    }
}

// Fetch all rooms
$rooms = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM em_rooms WHERE event_id = ? ORDER BY building, room_name");
    $stmt->execute([$event_id]);
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Ignore
}

include 'includes/header.php';
?>

<div class="card">
    <h2>कक्ष सूची और प्रबंधन (Room Inventory)</h2>

    <?php if ($message): ?>
        <div style="padding: 1rem; margin-bottom: 1rem; border-radius: 4px; background-color: <?= $message_type === 'success' ? '#1b5e20' : '#b71c1c' ?>; color: white;">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <div style="background: var(--container-bg); padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem;">
        <h3 style="margin-top: 0;">नया कमरा जोड़ें (Add New Room)</h3>
        <form method="POST" action="">
            <input type="hidden" name="action" value="add_room">
            <div class="grid" style="display: flex; flex-wrap: wrap; gap: 1rem;">
                <div class="form-group" style="flex: 1 1 200px;">
                    <label>कमरे का नाम / नंबर (Room Name/Number) *</label>
                    <input type="text" name="room_name" class="form-control" required>
                </div>
                <div class="form-group" style="flex: 1 1 200px;">
                    <label>भवन (Building) *</label>
                    <input type="text" name="building" class="form-control" required>
                </div>
                <div class="form-group" style="flex: 1 1 200px;">
                    <label>क्षमता (Capacity) *</label>
                    <input type="number" name="capacity" class="form-control" min="1" required>
                </div>
                <div class="form-group" style="flex: 1 1 100%;">
                    <label>विशेष टिप्पणी (Notes)</label>
                    <textarea name="notes" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <button type="submit" class="btn" style="margin-top: 1rem;">जोड़ें (Add Room)</button>
        </form>
    </div>

    <h3>उपलब्ध कमरे (Available Rooms)</h3>
    <div class="table-responsive" style="overflow-x: auto;">
        <table style="min-width: 800px;">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>भवन (Building)</th>
                    <th>कमरा (Room)</th>
                    <th>क्षमता (Capacity)</th>
                    <th>भरा हुआ (Occupied)</th>
                    <th>उपलब्ध (Available)</th>
                    <th>टिप्पणी (Notes)</th>
                    <th>कार्रवाई (Actions)</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($rooms) > 0): ?>
                    <?php foreach ($rooms as $room): ?>
                        <tr>
                            <td><?= $room['id'] ?></td>
                            <td><?= htmlspecialchars($room['building'] ?? '') ?></td>
                            <td>
                                <strong><?= htmlspecialchars($room['room_name'] ?? $room['room_number'] ?? '') ?></strong>
                            </td>
                            <td><?= $room['capacity'] ?></td>
                            <td><?= $room['occupancy'] ?></td>
                            <td>
                                <?php 
                                $available = max(0, $room['capacity'] - $room['occupancy']);
                                ?>
                                <span style="font-weight: bold; color: <?= $available > 0 ? '#4caf50' : '#f44336' ?>;">
                                    <?= $available ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($room['notes'] ?? '') ?></td>
                            <td>
                                <button class="btn" style="padding: 0.25rem 0.5rem; font-size: 0.85rem; margin-right: 0.5rem;" onclick="editRoom(<?= $room['id'] ?>, '<?= htmlspecialchars(addslashes($room['room_name'] ?? $room['room_number'] ?? '')) ?>', '<?= htmlspecialchars(addslashes($room['building'] ?? '')) ?>', <?= $room['capacity'] ?>, '<?= htmlspecialchars(addslashes($room['notes'] ?? '')) ?>')">संपादित करें (Edit)</button>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('क्या आप वाकई इस कमरे को हटाना चाहते हैं? (Are you sure you want to delete this room?)');">
                                    <input type="hidden" name="action" value="delete_room">
                                    <input type="hidden" name="id" value="<?= $room['id'] ?>">
                                    <button type="submit" class="btn" style="padding: 0.25rem 0.5rem; font-size: 0.85rem; background-color: #f44336;">हटाएं (Delete)</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" style="text-align: center;">कोई कमरा नहीं मिला (No rooms found).</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<!-- Edit Modal -->
<div id="editModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 1000;">
    <div class="card" style="width: 90%; max-width: 500px; margin: 4rem auto; position: relative;">
        <span onclick="document.getElementById('editModal').style.display='none'" style="position: absolute; right: 1rem; top: 1rem; cursor: pointer; font-size: 1.5rem;">&times;</span>
        <h3 style="margin-top: 0; color: var(--saffron);">कमरा संपादित करें (Edit Room)</h3>
        <form method="POST">
            <input type="hidden" name="action" value="edit_room">
            <input type="hidden" name="id" id="edit_room_id">
            
            <div class="form-group">
                <label>कमरे का नाम / नंबर (Room Name/Number) *</label>
                <input type="text" name="room_name" id="edit_room_name" class="form-control" required>
            </div>
            <div class="form-group">
                <label>भवन (Building) *</label>
                <input type="text" name="building" id="edit_building" class="form-control" required>
            </div>
            <div class="form-group">
                <label>क्षमता (Capacity) *</label>
                <input type="number" name="capacity" id="edit_capacity" class="form-control" min="1" required>
            </div>
            <div class="form-group">
                <label>विशेष टिप्पणी (Notes)</label>
                <textarea name="notes" id="edit_notes" class="form-control" rows="2"></textarea>
            </div>
            
            <button type="submit" class="btn" style="width: 100%; margin-top: 1rem;">सुरक्षित करें (Save)</button>
        </form>
    </div>
</div>

<script>
function editRoom(id, name, building, capacity, notes) {
    document.getElementById('edit_room_id').value = id;
    document.getElementById('edit_room_name').value = name;
    document.getElementById('edit_building').value = building;
    document.getElementById('edit_capacity').value = capacity;
    document.getElementById('edit_notes').value = notes;
    document.getElementById('editModal').style.display = 'block';
}
</script>
