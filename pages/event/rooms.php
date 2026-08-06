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
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'bulk_allot_rooms') {
    $participant_ids = $_POST['participant_ids'] ?? [];
    $room_id = $_POST['room_id'] ?? 0;
    $allotted_by = $_SESSION['event_user_id'] ?? 0;
    $event_id = $_SESSION['event_id'] ?? 1;

    if (!empty($participant_ids) && $room_id > 0) {
        $pdo->beginTransaction();
        try {
            foreach ($participant_ids as $pid) {
                $checkStmt = $pdo->prepare("SELECT room_id FROM em_room_allotments WHERE event_id = ? AND allottee_type = 'participant' AND allottee_id = ?");
                $checkStmt->execute([$event_id, $pid]);
                $old_room = $checkStmt->fetchColumn();

                if ($old_room) {
                    if ($old_room != $room_id) {
                        $pdo->prepare("UPDATE em_room_allotments SET room_id = ?, allotted_by = ? WHERE event_id = ? AND allottee_type = 'participant' AND allottee_id = ?")->execute([$room_id, $allotted_by, $event_id, $pid]);
                        $pdo->prepare("UPDATE em_rooms SET occupancy = GREATEST(0, occupancy - 1) WHERE id = ?")->execute([$old_room]);
                        $pdo->prepare("UPDATE em_rooms SET occupancy = occupancy + 1 WHERE id = ?")->execute([$room_id]);
                    }
                } else {
                    $stmt = $pdo->prepare("INSERT INTO em_room_allotments (event_id, room_id, allottee_type, allottee_id, allotted_by) VALUES (?, ?, 'participant', ?, ?)");
                    $stmt->execute([$event_id, $room_id, $pid, $allotted_by]);
                    $pdo->prepare("UPDATE em_rooms SET occupancy = occupancy + 1 WHERE id = ?")->execute([$room_id]);
                }
            }
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
        }
    }
    header("Location: rooms.php");
    exit;
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'csv_import') {
    $allotted_by = $_SESSION['event_user_id'] ?? 0;
    $event_id = $_SESSION['event_id'] ?? 1;
    
    if (isset($_FILES['csv_file']['tmp_name']) && is_uploaded_file($_FILES['csv_file']['tmp_name'])) {
        $file = fopen($_FILES['csv_file']['tmp_name'], 'r');
        $pdo->beginTransaction();
        try {
            while (($data = fgetcsv($file, 1000, ",")) !== FALSE) {
                if (count($data) >= 2) {
                    $phone = trim($data[0]);
                    $room_name = trim($data[1]);
                    
                    if ($phone && $room_name) {
                        $pStmt = $pdo->prepare("SELECT id FROM em_participants WHERE event_id = ? AND phone = ? LIMIT 1");
                        $pStmt->execute([$event_id, $phone]);
                        $pid = $pStmt->fetchColumn();

                        $rStmt = $pdo->prepare("SELECT id FROM em_rooms WHERE event_id = ? AND (room_name = ? OR room_number = ?) LIMIT 1");
                        $rStmt->execute([$event_id, $room_name, $room_name]);
                        $rid = $rStmt->fetchColumn();

                        if ($pid && $rid) {
                            $checkStmt = $pdo->prepare("SELECT room_id FROM em_room_allotments WHERE event_id = ? AND allottee_type = 'participant' AND allottee_id = ?");
                            $checkStmt->execute([$event_id, $pid]);
                            $old_room = $checkStmt->fetchColumn();

                            if ($old_room) {
                                if ($old_room != $rid) {
                                    $pdo->prepare("UPDATE em_room_allotments SET room_id = ?, allotted_by = ? WHERE event_id = ? AND allottee_type = 'participant' AND allottee_id = ?")->execute([$rid, $allotted_by, $event_id, $pid]);
                                    $pdo->prepare("UPDATE em_rooms SET occupancy = GREATEST(0, occupancy - 1) WHERE id = ?")->execute([$old_room]);
                                    $pdo->prepare("UPDATE em_rooms SET occupancy = occupancy + 1 WHERE id = ?")->execute([$rid]);
                                }
                            } else {
                                $stmt = $pdo->prepare("INSERT INTO em_room_allotments (event_id, room_id, allottee_type, allottee_id, allotted_by) VALUES (?, ?, 'participant', ?, ?)");
                                $stmt->execute([$event_id, $rid, $pid, $allotted_by]);
                                $pdo->prepare("UPDATE em_rooms SET occupancy = occupancy + 1 WHERE id = ?")->execute([$rid]);
                            }
                        }
                    }
                }
            }
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
        }
        fclose($file);
    }
    header("Location: rooms.php");
    exit;
}

$event_id = $_SESSION['event_id'] ?? 1;

// Fetch rooms
try {
    $roomsStmt = $pdo->prepare("SELECT id, room_name, building, capacity, occupancy FROM em_rooms WHERE event_id = ? ORDER BY building, room_name");
    $roomsStmt->execute([$event_id]);
    $rooms = $roomsStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // If column event_id doesn't exist on em_rooms or table doesn't exist, fallback
    try {
        $rooms = $pdo->query("SELECT id, room_name, building, capacity, occupancy FROM em_rooms ORDER BY building, room_name")->fetchAll(PDO::FETCH_ASSOC);
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
        <button type="button" class="btn btn-outline" onclick="document.getElementById('csvModal').style.display='block'" style="margin-left:auto;">CSV से आवंटन (Assign via CSV)</button>
    </form>

    <div style="overflow-x: auto; padding-bottom: 80px;">
        <table id="roomsTable">
            <thead>
                <tr>
                    <th><input type="checkbox" id="selectAll" onclick="toggleAll(this)" style="transform: scale(1.2);"></th>
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
                            <td><input type="checkbox" class="row-checkbox" value="<?= $p['id'] ?>" onchange="updateBulkBar()" style="transform: scale(1.2);"></td>
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
                        <td colspan="7" style="text-align: center;">कोई प्रतिभागी नहीं मिला (No participants found).</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Bulk Action Bar -->
<div id="bulkActionBar" style="display: none; position: fixed; bottom: 0; left: 0; right: 0; background: var(--card-bg, #fff); padding: 15px; box-shadow: 0 -2px 10px rgba(0,0,0,0.2); z-index: 1000; justify-content: center; align-items: center; gap: 15px;">
    <span id="selectedCount" style="font-weight: bold; margin-right: 15px;">0 selected</span>
    <form method="POST" action="rooms.php" id="bulkForm" style="display: flex; gap: 10px; align-items: center;">
        <input type="hidden" name="action" value="bulk_allot_rooms">
        <div id="bulkHiddenInputs"></div>
        <select name="room_id" class="form-control" style="width: 250px;" required>
            <option value="">-- कमरा चुनें (Select Room) --</option>
            <?php foreach ($rooms as $r): ?>
                <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['room_number']) ?> (<?= $r['capacity'] - $r['occupancy'] ?> left)</option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn">बल्क आवंटन (Bulk Assign)</button>
    </form>
</div>

<!-- CSV Import Modal -->
<div id="csvModal" style="display:none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 2000;">
    <div class="card" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 90%; max-width: 400px;">
        <h3 style="margin-top:0;">CSV से आवंटन (Assign via CSV)</h3>
        <p style="font-size: 0.9em; color: #666;">CSV format: Phone,Room (e.g. 9876543210,101)</p>
        <form method="POST" enctype="multipart/form-data" action="rooms.php">
            <input type="hidden" name="action" value="csv_import">
            <div class="form-group">
                <input type="file" name="csv_file" class="form-control" accept=".csv" required>
            </div>
            <div style="display:flex; gap:10px; justify-content: flex-end; margin-top: 15px;">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('csvModal').style.display='none'">रद्द करें (Cancel)</button>
                <button type="submit" class="btn">अपलोड करें (Upload)</button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleAll(source) {
    checkboxes = document.getElementsByClassName('row-checkbox');
    for(var i=0, n=checkboxes.length;i<n;i++) {
        checkboxes[i].checked = source.checked;
    }
    updateBulkBar();
}

function updateBulkBar() {
    let checkboxes = document.getElementsByClassName('row-checkbox');
    let selectedIds = [];
    for(var i=0, n=checkboxes.length;i<n;i++) {
        if (checkboxes[i].checked) {
            selectedIds.push(checkboxes[i].value);
        }
    }
    
    let bulkBar = document.getElementById('bulkActionBar');
    let countSpan = document.getElementById('selectedCount');
    let hiddenContainer = document.getElementById('bulkHiddenInputs');
    
    if (selectedIds.length > 0) {
        bulkBar.style.display = 'flex';
        countSpan.innerText = selectedIds.length + ' चयनित (Selected)';
        
        hiddenContainer.innerHTML = '';
        selectedIds.forEach(id => {
            let input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'participant_ids[]';
            input.value = id;
            hiddenContainer.appendChild(input);
        });
    } else {
        bulkBar.style.display = 'none';
        document.getElementById('selectAll').checked = false;
    }
}
</script>

<?php include 'includes/footer.php'; ?>
