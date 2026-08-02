<?php
session_start();
require_once '../../config/db.php';

// Handle Allotment POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'allot_room') {
    $room_id = $_POST['room_id'] ?? 0;
    $participant_id = $_POST['participant_id'] ?? 0;
    $allotted_by = $_SESSION['event_user_id'] ?? 0;
    
    if ($room_id && $participant_id) {
        $stmt = $pdo->prepare("INSERT INTO em_room_allotments (room_id, participant_id, allotted_by) VALUES (?, ?, ?)");
        $stmt->execute([$room_id, $participant_id, $allotted_by]);
        
        // Update occupancy
        $pdo->prepare("UPDATE em_rooms SET occupancy = occupancy + 1 WHERE id = ?")->execute([$room_id]);
    }
    header("Location: rooms.php");
    exit;
}

include 'includes/header.php';

try {
    $rooms = $pdo->query("SELECT * FROM em_rooms")->fetchAll();
    
    // Fetch participants for the modal dropdown
    $participants = $pdo->query("SELECT id, name FROM em_participants WHERE id NOT IN (SELECT participant_id FROM em_room_allotments) ORDER BY name ASC")->fetchAll();
} catch (Exception $e) {
    // Mock data if table doesn't exist
    $rooms = [
        ['id' => 1, 'room_number' => 'A-101', 'capacity' => 10, 'occupancy' => 8, 'building' => 'मुख्य भवन'],
        ['id' => 2, 'room_number' => 'A-102', 'capacity' => 10, 'occupancy' => 10, 'building' => 'मुख्य भवन'],
        ['id' => 3, 'room_number' => 'B-201', 'capacity' => 20, 'occupancy' => 5, 'building' => 'छात्रावास'],
    ];
    $participants = [];
}
?>

<h2>आवास व्यवस्था (Room Allotment)</h2>

<div class="grid">
    <?php foreach($rooms as $room): 
        $percent = ($room['capacity'] > 0) ? ($room['occupancy'] / $room['capacity']) * 100 : 0;
        $statusClass = 'status-green';
        if ($percent >= 100) $statusClass = 'status-red';
        elseif ($percent >= 70) $statusClass = 'status-yellow';
    ?>
    <div class="card">
        <div style="display: flex; justify-content: space-between;">
            <h3 style="margin: 0; color: var(--saffron);"><?= htmlspecialchars($room['room_number']) ?></h3>
            <span style="font-size: 0.9em; color: #666;"><?= htmlspecialchars($room['building'] ?? '') ?></span>
        </div>
        <div style="margin-top: 1rem;">
            <div style="display: flex; justify-content: space-between; font-size: 0.9em;">
                <span>उपलब्धता (Occupancy)</span>
                <strong><?= $room['occupancy'] ?> / <?= $room['capacity'] ?></strong>
            </div>
            <div class="status-bar">
                <div class="status-fill <?= $statusClass ?>" style="width: <?= min($percent, 100) ?>%"></div>
            </div>
        </div>
        <div style="margin-top: 1rem; text-align: center;">
            <button class="btn btn-outline" style="width: 100%;" <?= $percent >= 100 ? 'disabled' : '' ?> onclick="openAllotModal(<?= $room['id'] ?>, '<?= htmlspecialchars($room['room_number']) ?>')">
                <?= $percent >= 100 ? 'पूर्ण (Full)' : 'आवंटित करें (Allot)' ?>
            </button>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div id="allotModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000;">
    <div class="card" style="max-width:500px; margin: 10% auto; position:relative;">
        <h3>आवास आवंटन (Room Allotment) - <span id="modalRoomName"></span></h3>
        <span onclick="document.getElementById('allotModal').style.display='none'" style="position:absolute; right:1.5rem; top:1.5rem; cursor:pointer; font-size:1.5rem;">&times;</span>
        <form method="POST" action="rooms.php">
            <input type="hidden" name="action" value="allot_room">
            <input type="hidden" name="room_id" id="modalRoomId">
            <div class="form-group">
                <label>प्रतिभागी चुनें (Select Participant)</label>
                <select name="participant_id" class="form-control" required>
                    <option value="">-- चुनें --</option>
                    <?php foreach($participants as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?> (ID: <?= $p['id'] ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn">आवंटित करें (Allot)</button>
        </form>
    </div>
</div>

<script>
function openAllotModal(roomId, roomName) {
    document.getElementById('modalRoomId').value = roomId;
    document.getElementById('modalRoomName').innerText = roomName;
    document.getElementById('allotModal').style.display = 'block';
}
</script>

<?php include 'includes/footer.php'; ?>
