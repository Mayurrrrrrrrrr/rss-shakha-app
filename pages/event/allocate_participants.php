<?php
session_start();
require_once '../../config/db.php';

$event_id = (int)($_GET['event_id'] ?? 0);

if ($event_id <= 0) {
    include 'includes/header.php';
    echo "<div class='container'><div class='card'><h2 style='color:var(--danger)'>Invalid Event ID</h2></div></div>";
    include 'includes/footer.php';
    exit;
}

// Fetch event details
$stmt = $pdo->prepare("SELECT * FROM em_events WHERE id = ?");
$stmt->execute([$event_id]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    include 'includes/header.php';
    echo "<div class='container'><div class='card'><h2 style='color:var(--danger)'>Event Not Found</h2></div></div>";
    include 'includes/footer.php';
    exit;
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['allocate'])) {
    $selected_data = $_POST['participants_data'] ?? [];
    if (empty($selected_data)) {
        $error = "कोई प्रतिभागी नहीं चुना गया। (No participants selected.)";
    } else {
        try {
            $pdo->beginTransaction();
            
            // Insert into em_participants
            $insertStmt = $pdo->prepare("
                INSERT INTO em_participants (event_id, name, phone, city, age, category, entry_type) 
                VALUES (?, ?, ?, ?, ?, ?, 'allocated')
            ");
            
            $count = 0;
            foreach ($selected_data as $dataStr) {
                // $dataStr is base64 encoded JSON to easily pass all required fields from the frontend
                $p = json_decode(base64_decode($dataStr), true);
                if (!$p) continue;
                
                // Check if already a participant in THIS event
                $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM em_participants WHERE event_id = ? AND name = ? AND (phone = ? OR phone IS NULL)");
                $checkStmt->execute([$event_id, $p['name'], $p['phone']]);
                if ($checkStmt->fetchColumn() == 0) {
                    $insertStmt->execute([
                        $event_id, 
                        $p['name'], 
                        $p['phone'], 
                        $p['city'] ?? '',
                        $p['age'] ?? null,
                        $p['category'] ?? ''
                    ]);
                    $count++;
                }
            }
            
            $pdo->commit();
            $message = "$count प्रतिभागियों को सफलतापूर्वक इस आयोजन में आवंटित किया गया! ($count participants successfully allocated!)";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// Search past participants
$search = $_GET['search'] ?? '';
$query = "
    SELECT name, MAX(phone) as phone, MAX(city) as city, MAX(age) as age, MAX(category) as category 
    FROM em_participants p1
    WHERE is_deleted = 0 
      AND NOT EXISTS (
          SELECT 1 FROM em_participants p2 
          WHERE p2.event_id = :event_id 
            AND p2.name = p1.name 
            AND (p2.phone = p1.phone OR (p2.phone IS NULL AND p1.phone IS NULL))
      )
";
$params = [':event_id' => $event_id];

if ($search) {
    $query .= " AND (p1.name LIKE :search1 OR p1.phone LIKE :search2 OR p1.city LIKE :search3)";
    $params[':search1'] = "%$search%";
    $params[':search2'] = "%$search%";
    $params[':search3'] = "%$search%";
}

$query .= " GROUP BY name, phone ORDER BY name ASC LIMIT 200";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$past_participants = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<div class="container">
    <div class="page-header" style="display:flex; justify-content: space-between; align-items:center; margin-bottom: 2rem;">
        <h2 style="margin:0; color:var(--saffron);">प्रतिभागी आवंटित करें (Allocate Participants)</h2>
        <a href="create_event.php" class="btn btn-outline">वापस जाएं (Back)</a>
    </div>

    <div class="card" style="margin-bottom: 1rem;">
        <h3 style="margin-top:0;">आयोजन: <?= htmlspecialchars($event['name']) ?></h3>
        <p style="color: var(--text-muted); margin:0;">यहाँ आप पिछले आयोजनों के प्रतिभागियों को खोजकर वर्तमान आयोजन में आवंटित कर सकते हैं। (Allocate past event participants to this event.)</p>
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
                            <th style="padding: 1rem;">नगर (City)</th>
                            <th style="padding: 1rem;">आयु (Age)</th>
                            <th style="padding: 1rem;">श्रेणी (Category)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($past_participants as $p): ?>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 1rem;">
                                <?php $dataStr = base64_encode(json_encode($p)); ?>
                                <input type="checkbox" name="participants_data[]" value="<?= $dataStr ?>" class="row-checkbox">
                            </td>
                            <td style="padding: 1rem; font-weight: bold;"><?= htmlspecialchars($p['name']) ?></td>
                            <td style="padding: 1rem;"><?= htmlspecialchars($p['phone'] ?? '-') ?></td>
                            <td style="padding: 1rem;"><?= htmlspecialchars($p['city'] ?? '-') ?></td>
                            <td style="padding: 1rem;"><?= htmlspecialchars($p['age'] ?? '-') ?></td>
                            <td style="padding: 1rem;"><?= htmlspecialchars($p['category'] ?? '-') ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($past_participants)): ?>
                        <tr><td colspan="6" style="padding: 1rem; text-align: center;">पिछले आयोजनों का कोई ऐसा प्रतिभागी नहीं मिला जो इस आयोजन में न हो। (No unallocated past participants found.)</td></tr>
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
    var checkboxes = document.querySelectorAll('.row-checkbox');
    for (var i = 0, n = checkboxes.length; i < n; i++) {
        checkboxes[i].checked = source.checked;
    }
}
</script>

<?php include 'includes/footer.php'; ?>
