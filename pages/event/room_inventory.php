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
        room_number VARCHAR(50) DEFAULT NULL,
        building VARCHAR(100),
        capacity INT DEFAULT 0,
        occupancy INT DEFAULT 0,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    
    // Attempt to add room_name column if only room_number existed
    try {
        $pdo->exec("ALTER TABLE em_rooms ADD COLUMN room_name VARCHAR(100) AFTER event_id");
    } catch (PDOException $e) {}
    try {
        $pdo->exec("ALTER TABLE em_rooms ADD COLUMN notes TEXT AFTER occupancy");
    } catch (PDOException $e) {}

} catch (PDOException $e) {
    // Ignore error
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_room') {
    $room_name = trim($_POST['room_name'] ?? '');
    $building = trim($_POST['building'] ?? '');
    $capacity = (int)($_POST['capacity'] ?? 0);
    $notes = trim($_POST['notes'] ?? '');

    if ($room_name && $capacity > 0) {
        try {
            $stmt = $pdo->prepare("INSERT INTO em_rooms (event_id, room_name, room_number, building, capacity, occupancy, notes) VALUES (?, ?, ?, ?, ?, 0, ?)");
            // Use room_name for room_number as well for backward compatibility
            $stmt->execute([$event_id, $room_name, $room_name, $building, $capacity, $notes]);
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
            <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
                <div class="form-group">
                    <label>कमरे का नाम / नंबर (Room Name/Number) *</label>
                    <input type="text" name="room_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>भवन (Building) *</label>
                    <input type="text" name="building" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>क्षमता (Capacity) *</label>
                    <input type="number" name="capacity" class="form-control" min="1" required>
                </div>
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label>विशेष टिप्पणी (Notes)</label>
                    <textarea name="notes" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <button type="submit" class="btn" style="margin-top: 1rem;">जोड़ें (Add Room)</button>
        </form>
    </div>

    <h3>उपलब्ध कमरे (Available Rooms)</h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>भवन (Building)</th>
                    <th>कमरा (Room)</th>
                    <th>क्षमता (Capacity)</th>
                    <th>भरा हुआ (Occupied)</th>
                    <th>उपलब्ध (Available)</th>
                    <th>टिप्पणी (Notes)</th>
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
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align: center;">कोई कमरा नहीं मिला (No rooms found).</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
