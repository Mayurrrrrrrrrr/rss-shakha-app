<?php
session_start();
require_once '../../config/db.php';

// Handle Allotment POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'allot_room_matrix') {
    $participant_id = $_POST['participant_id'] ?? 0;
    $room_id = $_POST['room_id'] ?? 0;
    $allotted_by = $_SESSION['event_user_id'] ?? 0;
    $event_id = $_SESSION['event_id'] ?? 1;
    
    if ($participant_id) {
        $pdo->beginTransaction();
        try {
            // Check if participant already has a room
            $checkStmt = $pdo->prepare("SELECT room_id FROM em_room_allotments WHERE event_id = ? AND allottee_type = 'participant' AND allottee_id = ?");
            $checkStmt->execute([$event_id, $participant_id]);
            $old_room = $checkStmt->fetchColumn();

            if ($old_room) {
                if ($old_room == $room_id) {
                    // No change
                    $pdo->commit();
                    header("Location: rooms.php");
                    exit;
                }
                if ($room_id == 0) {
                    // Unassign room
                    $pdo->prepare("DELETE FROM em_room_allotments WHERE event_id = ? AND allottee_type = 'participant' AND allottee_id = ?")->execute([$event_id, $participant_id]);
                    $pdo->prepare("UPDATE em_rooms SET occupancy = GREATEST(0, occupancy - 1) WHERE id = ?")->execute([$old_room]);
                } else {
                    // Update room
                    $pdo->prepare("UPDATE em_room_allotments SET room_id = ?, allotted_by = ? WHERE event_id = ? AND allottee_type = 'participant' AND allottee_id = ?")->execute([$room_id, $allotted_by, $event_id, $participant_id]);
                    $pdo->prepare("UPDATE em_rooms SET occupancy = GREATEST(0, occupancy - 1) WHERE id = ?")->execute([$old_room]);
                    $pdo->prepare("UPDATE em_rooms SET occupancy = occupancy + 1 WHERE id = ?")->execute([$room_id]);
                }
            } else if ($room_id > 0) {
                // New assignment
                $stmt = $pdo->prepare("INSERT INTO em_room_allotments (event_id, room_id, allottee_type, allottee_id, allotted_by) VALUES (?, ?, 'participant', ?, ?)");
                $stmt->execute([$event_id, $room_id, $participant_id, $allotted_by]);
                $pdo->prepare("UPDATE em_rooms SET occupancy = occupancy + 1 WHERE id = ?")->execute([$room_id]);
            }
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            // Handle error (silently for now or add flash message)
        }
    }
    
    // Preserve filters in redirect
    $qParams = [];
    if (!empty($_POST['search_name'])) $qParams['search_name'] = $_POST['search_name'];
    if (!empty($_POST['search_city'])) $qParams['search_city'] = $_POST['search_city'];
    $qs = http_build_query($qParams);
    header("Location: rooms.php" . ($qs ? "?$qs" : ""));
    exit;
}

$event_id = $_SESSION['event_id'] ?? 1;

// Fetch rooms
try {
    $roomsStmt = $pdo->prepare("SELECT id, room_number, building, capacity, occupancy FROM em_rooms WHERE event_id = ? ORDER BY building, room_number");
    $roomsStmt->execute([$event_id]);
    $rooms = $roomsStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // If column event_id doesn't exist on em_rooms or table doesn't exist, fallback
    try {
        $rooms = $pdo->query("SELECT id, room_number, building, capacity, occupancy FROM em_rooms ORDER BY building, room_number")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e2) {
        $rooms = [];
    }
}

// Top 3 rooms logic (rooms that have max capacity available)
$topRooms = $rooms;
usort($topRooms, function($a, $b) {
    $a_avail = $a['capacity'] - $a['occupancy'];
    $b_avail = $b['capacity'] - $b['occupancy'];
    return $b_avail <=> $a_avail;
});
$topRooms = array_slice($topRooms, 0, 3);


// Fetch participants with filtering
$search_name = $_GET['search_name'] ?? '';
$search_city = $_GET['search_city'] ?? '';

$sql = "
    SELECT p.id, p.name, p.city, r.id as room_id, r.room_name, r.building
    FROM em_participants p
    LEFT JOIN em_room_allotments ra ON p.id = ra.allottee_id AND ra.allottee_type = 'participant' AND ra.event_id = :event_id1
    LEFT JOIN em_rooms r ON ra.room_id = r.id
    WHERE p.event_id = :event_id2
";
$params = [':event_id1' => $event_id, ':event_id2' => $event_id];

if ($search_name !== '') {
    $sql .= " AND p.name LIKE :name ";
    $params[':name'] = '%' . $search_name . '%';
}
if ($search_city !== '') {
    $sql .= " AND p.city LIKE :city ";
    $params[':city'] = '%' . $search_city . '%';
}

$sql .= " ORDER BY p.name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$participants = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<div class="card">
    <h2>आवास व्यवस्था (Room Allotment)</h2>

    <form method="GET" action="" style="display: flex; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap;">
        <div class="form-group" style="margin-bottom: 0;">
            <input type="text" name="search_name" class="form-control" placeholder="नाम से खोजें (Search Name)" value="<?= htmlspecialchars($search_name) ?>">
        </div>
        <div class="form-group" style="margin-bottom: 0;">
            <input type="text" name="search_city" class="form-control" placeholder="शहर से खोजें (Search City)" value="<?= htmlspecialchars($search_city) ?>">
        </div>
        <button type="submit" class="btn">खोजें (Search)</button>
        <a href="rooms.php" class="btn btn-outline" style="text-decoration:none; padding: 0.5rem 1rem;">रीसेट (Reset)</a>
    </form>

    <div style="overflow-x: auto;">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>नाम (Name)</th>
                    <th>शहर (City)</th>
                    <th>वर्तमान कमरा (Current Room)</th>
                    <th>कमरा आवंटित करें (Allot Room)</th>
                    <th>कार्य (Action)</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($participants) > 0): ?>
                    <?php foreach ($participants as $p): ?>
                        <tr>
                            <td><?= $p['id'] ?></td>
                            <td><?= htmlspecialchars($p['name']) ?></td>
                            <td><?= htmlspecialchars($p['city']) ?></td>
                            <td>
                                <?php if ($p['room_id']): ?>
                                    <span style="color: var(--amber); font-weight: bold;"><?= htmlspecialchars($p['room_name']) ?></span> 
                                    <br><small><?= htmlspecialchars($p['building']) ?></small>
                                <?php else: ?>
                                    <span style="color: #999;">आवंटित नहीं (Not Allotted)</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="POST" action="rooms.php" style="display: flex; align-items: center; gap: 0.5rem;">
                                    <input type="hidden" name="action" value="allot_room_matrix">
                                    <input type="hidden" name="participant_id" value="<?= $p['id'] ?>">
                                    <input type="hidden" name="search_name" value="<?= htmlspecialchars($search_name) ?>">
                                    <input type="hidden" name="search_city" value="<?= htmlspecialchars($search_city) ?>">
                                    
                                    <select name="room_id" class="form-control" style="width: 200px;">
                                        <option value="0">-- कमरा निकालें (Unassign) --</option>
                                        <?php if (count($topRooms) > 0): ?>
                                            <optgroup label="Top Available Rooms">
                                                <?php foreach ($topRooms as $tr): ?>
                                                    <option value="<?= $tr['id'] ?>" <?= $p['room_id'] == $tr['id'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($tr['room_number']) ?> (<?= $tr['capacity'] - $tr['occupancy'] ?> left)
                                                    </option>
                                                <?php endforeach; ?>
                                            </optgroup>
                                        <?php endif; ?>
                                        <optgroup label="All Rooms">
                                            <?php foreach ($rooms as $r): ?>
                                                <option value="<?= $r['id'] ?>" <?= $p['room_id'] == $r['id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($r['room_number']) ?> (<?= $r['capacity'] - $r['occupancy'] ?> left)
                                                </option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    </select>
                            </td>
                            <td>
                                    <button type="submit" class="btn" style="padding: 0.3rem 0.6rem; font-size: 0.9em;">सेट करें (Set)</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center;">कोई प्रतिभागी नहीं मिला (No participants found).</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
