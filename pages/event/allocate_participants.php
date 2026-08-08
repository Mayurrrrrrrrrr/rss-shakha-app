<?php
session_start();
require_once '../../config/db.php';
include 'includes/header.php';

if ($_SESSION['event_role'] !== 'admin') {
    echo "<div class='container'><div class='card'><h2 style='color:var(--danger)'>Permission Denied</h2><p>You don't have permission to allocate participants.</p></div></div>";
    include 'includes/footer.php';
    exit;
}

$event_id = (int)($_GET['event_id'] ?? 0);
if (!$event_id) {
    echo "<div class='container'><div class='card'><h2 style='color:var(--danger)'>Invalid Event ID</h2></div></div>";
    include 'includes/footer.php';
    exit;
}

// Fetch event details
$stmt = $pdo->prepare("SELECT * FROM em_events WHERE id = ?");
$stmt->execute([$event_id]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    echo "<div class='container'><div class='card'><h2 style='color:var(--danger)'>Event Not Found</h2></div></div>";
    include 'includes/footer.php';
    exit;
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['allocate'])) {
    $selected_ids = $_POST['swayamsevak_ids'] ?? [];
    if (empty($selected_ids)) {
        $error = "No swayamsevaks selected.";
    } else {
        try {
            $pdo->beginTransaction();
            
            // Get selected swayamsevaks details
            $placeholders = implode(',', array_fill(0, count($selected_ids), '?'));
            $stmt = $pdo->prepare("SELECT * FROM swayamsevaks WHERE id IN ($placeholders)");
            $stmt->execute($selected_ids);
            $selected_swayamsevaks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Insert into em_participants
            $insertStmt = $pdo->prepare("
                INSERT INTO em_participants (event_id, name, phone, city, age, category, entry_type) 
                VALUES (?, ?, ?, ?, ?, ?, 'allocated')
            ");
            
            $count = 0;
            foreach ($selected_swayamsevaks as $sw) {
                // Check if already a participant
                $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM em_participants WHERE event_id = ? AND phone = ?");
                $checkStmt->execute([$event_id, $sw['phone']]);
                if ($checkStmt->fetchColumn() == 0) {
                    $insertStmt->execute([
                        $event_id, 
                        $sw['name'], 
                        $sw['phone'], 
                        $sw['address'] ?? '', // map address to city
                        $sw['age'] ?? null,
                        $sw['category'] ?? ''
                    ]);
                    $count++;
                }
            }
            
            $pdo->commit();
            $message = "$count participants successfully allocated to the event!";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// Search
$search = $_GET['search'] ?? '';
$query = "SELECT * FROM swayamsevaks WHERE is_active = 1 AND is_deleted = 0";
$params = [];
if ($search) {
    $query .= " AND (name LIKE ? OR phone LIKE ? OR address LIKE ?)";
    $params = ["%$search%", "%$search%", "%$search%"];
}
$query .= " ORDER BY name ASC LIMIT 200";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$swayamsevaks = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="container">
    <div class="page-header" style="display:flex; justify-content: space-between; align-items:center; margin-bottom: 2rem;">
        <h2 style="margin:0; color:var(--saffron);">प्रतिभागी आवंटित करें (Allocate Participants)</h2>
        <a href="create_event.php" class="btn btn-outline">वापस जाएं (Back)</a>
    </div>

    <div class="card" style="margin-bottom: 1rem;">
        <h3 style="margin-top:0;">आयोजन: <?= htmlspecialchars($event['name']) ?></h3>
        <p style="color: var(--text-muted); margin:0;">यहाँ आप मुख्य डेटाबेस से स्वयंसेवकों का चयन कर उन्हें इस आयोजन में प्रतिभागी के रूप में जोड़ सकते हैं। (Allocate Swayamsevaks from the main database to this event.)</p>
    </div>

    <?php if ($error): ?>
        <div style="background: rgba(239, 68, 68, 0.1); color: var(--danger); padding: 1rem; border-radius: 8px; margin-bottom: 1rem; border: 1px solid var(--danger);">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    
    <?php if ($message): ?>
        <div style="background: rgba(16, 185, 129, 0.1); color: var(--success); padding: 1rem; border-radius: 8px; margin-bottom: 1rem; border: 1px solid var(--success);">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <form method="GET" action="" style="display: flex; gap: 1rem; margin-bottom: 1rem;">
            <input type="hidden" name="event_id" value="<?= $event_id ?>">
            <input type="text" name="search" class="form-control" placeholder="नाम, फोन, या नगर से खोजें..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit" class="btn">खोजें (Search)</button>
            <?php if($search): ?>
                <a href="allocate_participants.php?event_id=<?= $event_id ?>" class="btn btn-outline">रीसेट</a>
            <?php endif; ?>
        </form>

        <form method="POST">
            <div style="overflow-x: auto; max-height: 60vh; overflow-y: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead style="position: sticky; top: 0; background: var(--card-bg); z-index: 1;">
                        <tr style="border-bottom: 2px solid var(--border-color); text-align: left;">
                            <th style="padding: 1rem;"><input type="checkbox" id="selectAll" onclick="toggleAll(this)"></th>
                            <th style="padding: 1rem;">नाम (Name)</th>
                            <th style="padding: 1rem;">फोन (Phone)</th>
                            <th style="padding: 1rem;">नगर/पता (City/Address)</th>
                            <th style="padding: 1rem;">आयु (Age)</th>
                            <th style="padding: 1rem;">श्रेणी (Category)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($swayamsevaks as $sw): ?>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 1rem;"><input type="checkbox" name="swayamsevak_ids[]" value="<?= $sw['id'] ?>" class="row-checkbox"></td>
                            <td style="padding: 1rem; font-weight: bold;"><?= htmlspecialchars($sw['name']) ?></td>
                            <td style="padding: 1rem;"><?= htmlspecialchars($sw['phone'] ?? '-') ?></td>
                            <td style="padding: 1rem;"><?= htmlspecialchars($sw['address'] ?? '-') ?></td>
                            <td style="padding: 1rem;"><?= htmlspecialchars($sw['age'] ?? '-') ?></td>
                            <td style="padding: 1rem;"><?= htmlspecialchars($sw['category'] ?? '-') ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($swayamsevaks)): ?>
                        <tr><td colspan="6" style="padding: 1rem; text-align: center;">कोई स्वयंसेवक नहीं मिला (No swayamsevaks found)</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div style="margin-top: 1.5rem; display: flex; justify-content: flex-end;">
                <button type="submit" name="allocate" class="btn" style="padding: 0.8rem 1.5rem; font-size: 1.1rem;">चुने हुए प्रतिभागियों को आवंटित करें (Allocate Selected)</button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleAll(source) {
    checkboxes = document.querySelectorAll('.row-checkbox');
    for (var i = 0, n = checkboxes.length; i < n; i++) {
        checkboxes[i].checked = source.checked;
    }
}
</script>

<?php include 'includes/footer.php'; ?>
