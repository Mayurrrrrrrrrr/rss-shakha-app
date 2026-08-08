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
                VALUES (?, ?, ?, ?, ?, ?, 'pre-registered')
            ");
            
            $count = 0;
            foreach ($selected_data as $dataStr) {
                $p = json_decode(base64_decode($dataStr), true);
                if (!$p) continue;
                
                // Check if already a participant in THIS event
                $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM em_participants WHERE event_id = ? AND name = ? AND (phone = ? OR (phone IS NULL AND ? IS NULL))");
                $checkStmt->execute([$event_id, $p['name'], $p['phone'], $p['phone']]);
                
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

// Pagination setup
$page = (int)($_GET['page'] ?? 1);
$per_page = 100;
$offset = ($page - 1) * $per_page;

// Filters
$search = trim($_GET['search'] ?? '');
$city_filter = trim($_GET['city'] ?? '');

// Base query for past participants (excluding current event id)
$base_query = "
    FROM em_participants p1
    WHERE is_deleted = 0 AND event_id != :event_id
";
$params = [':event_id' => $event_id];

if ($search) {
    $base_query .= " AND (p1.name LIKE :search1 OR p1.phone LIKE :search2)";
    $params[':search1'] = "%$search%";
    $params[':search2'] = "%$search%";
}

if ($city_filter) {
    $base_query .= " AND p1.city = :city";
    $params[':city'] = $city_filter;
}

// Count total distinct participants for pagination
$count_query = "SELECT COUNT(DISTINCT name, phone) " . $base_query;
$countStmt = $pdo->prepare($count_query);
$countStmt->execute($params);
$total_rows = $countStmt->fetchColumn();
$total_pages = ceil($total_rows / $per_page);

// Fetch data query
$query = "
    SELECT 
        name, 
        phone, 
        MAX(city) as city, 
        MAX(age) as age, 
        MAX(category) as category,
        (SELECT COUNT(*) FROM em_participants p2 WHERE p2.event_id = :event_id AND p2.name = p1.name AND (p2.phone = p1.phone OR (p2.phone IS NULL AND p1.phone IS NULL))) as is_duplicate
    " . $base_query . " 
    GROUP BY name, phone 
    ORDER BY name ASC 
    LIMIT $per_page OFFSET $offset
";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$past_participants = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all distinct cities from past events for filter dropdown
$citiesStmt = $pdo->query("SELECT DISTINCT city FROM em_participants WHERE is_deleted = 0 AND city != '' ORDER BY city ASC");
$citiesList = $citiesStmt->fetchAll(PDO::FETCH_COLUMN);

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
        <form method="GET" action="" style="display: flex; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap;">
            <input type="hidden" name="event_id" value="<?= $event_id ?>">
            
            <input type="text" name="search" class="form-control" style="flex: 2; min-width: 200px;" placeholder="नाम या फोन से खोजें..." value="<?= htmlspecialchars($search) ?>">
            
            <select name="city" class="form-control" style="flex: 1; min-width: 150px;">
                <option value="">सभी नगर (All Cities)</option>
                <?php foreach ($citiesList as $city): ?>
                    <option value="<?= htmlspecialchars($city) ?>" <?= $city === $city_filter ? 'selected' : '' ?>><?= htmlspecialchars($city) ?></option>
                <?php endforeach; ?>
            </select>
            
            <button type="submit" class="btn">फ़िल्टर (Filter)</button>
            <?php if($search || $city_filter): ?>
                <a href="allocate_participants.php?event_id=<?= $event_id ?>" class="btn btn-outline">रीसेट</a>
            <?php endif; ?>
        </form>

        <form method="POST">
            <div style="overflow-x: auto; max-height: 60vh; overflow-y: auto; border: 1px solid var(--border-color); border-radius: 8px;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead style="position: sticky; top: 0; background: var(--card-bg); z-index: 1;">
                        <tr style="border-bottom: 2px solid var(--border-color); text-align: left;">
                            <th style="padding: 1rem; width: 50px;">
                                <input type="checkbox" id="selectAll" onclick="toggleAll(this)" title="सभी चुनें (Select All visible)">
                            </th>
                            <th style="padding: 1rem;">नाम (Name)</th>
                            <th style="padding: 1rem;">फोन (Phone)</th>
                            <th style="padding: 1rem;">नगर (City)</th>
                            <th style="padding: 1rem;">स्थिति (Status)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($past_participants as $p): ?>
                        <?php $is_duplicate = $p['is_duplicate'] > 0; ?>
                        <tr style="border-bottom: 1px solid var(--border-color); <?= $is_duplicate ? 'opacity: 0.6; background: rgba(0,0,0,0.02);' : '' ?>">
                            <td style="padding: 1rem;">
                                <?php if (!$is_duplicate): ?>
                                    <?php $dataStr = base64_encode(json_encode(['name' => $p['name'], 'phone' => $p['phone'], 'city' => $p['city'], 'age' => $p['age'], 'category' => $p['category']])); ?>
                                    <input type="checkbox" name="participants_data[]" value="<?= $dataStr ?>" class="row-checkbox">
                                <?php else: ?>
                                    <input type="checkbox" disabled checked style="opacity: 0.5;">
                                <?php endif; ?>
                            </td>
                            <td style="padding: 1rem; font-weight: bold;"><?= htmlspecialchars($p['name']) ?></td>
                            <td style="padding: 1rem;"><?= htmlspecialchars($p['phone'] ?? '-') ?></td>
                            <td style="padding: 1rem;"><?= htmlspecialchars($p['city'] ?? '-') ?></td>
                            <td style="padding: 1rem;">
                                <?php if ($is_duplicate): ?>
                                    <span style="background: rgba(16, 185, 129, 0.15); color: var(--success); padding: 0.25rem 0.5rem; border-radius: 12px; font-size: 0.8rem; font-weight: bold;">
                                        ✅ पहले से शामिल (Already Added)
                                    </span>
                                <?php else: ?>
                                    <span style="color: var(--text-muted); font-size: 0.8rem;">उपलब्ध (Available)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($past_participants)): ?>
                        <tr><td colspan="5" style="padding: 2rem; text-align: center; color: var(--text-muted);">कोई परिणाम नहीं मिला (No participants found).</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination Controls -->
            <?php if ($total_pages > 1): ?>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1.5rem;">
                <div style="color: var(--text-muted); font-size: 0.9rem;">
                    दिखा रहे हैं <?= $offset + 1 ?> से <?= min($offset + $per_page, $total_rows) ?> (कुल <?= $total_rows ?>)
                </div>
                <div style="display: flex; gap: 0.5rem;">
                    <?php
                        $qs = $_GET; 
                        unset($qs['page']);
                        $base_url = "allocate_participants.php?" . http_build_query($qs);
                    ?>
                    
                    <?php if ($page > 1): ?>
                        <a href="<?= $base_url ?>&page=<?= $page - 1 ?>" class="btn btn-outline" style="padding: 0.5rem 1rem;">&laquo; पिछला (Prev)</a>
                    <?php endif; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="<?= $base_url ?>&page=<?= $page + 1 ?>" class="btn btn-outline" style="padding: 0.5rem 1rem;">अगला (Next) &raquo;</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <div style="margin-top: 2rem; display: flex; justify-content: flex-end;">
                <button type="submit" name="allocate" class="btn" style="padding: 0.8rem 1.5rem; font-size: 1.1rem; box-shadow: 0 4px 14px rgba(249, 115, 22, 0.4);">
                    चुने हुए प्रतिभागियों को आवंटित करें (Allocate Selected)
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleAll(source) {
    var checkboxes = document.querySelectorAll('.row-checkbox');
    for (var i = 0, n = checkboxes.length; i < n; i++) {
        if (!checkboxes[i].disabled) {
            checkboxes[i].checked = source.checked;
        }
    }
}
</script>

<?php include 'includes/footer.php'; ?>
