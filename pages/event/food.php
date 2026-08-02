<?php
session_start();
require_once '../../config/db.php';

$event_id = $_SESSION['event_id'] ?? 1;
$is_admin_or_coord = isset($_SESSION['event_role']) && in_array($_SESSION['event_role'], ['admin', 'coordinator']);
$message = '';
$message_type = '';

// Create em_meals table if it doesn't exist
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS em_meals (
        id INT AUTO_INCREMENT PRIMARY KEY,
        event_id INT NOT NULL DEFAULT 1,
        meal_date DATE,
        meal_time VARCHAR(50),
        meal_name VARCHAR(100),
        expected_upcoming INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
} catch (PDOException $e) {
    // Ignore
}

// Handle Form Submissions (Add / Update)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_admin_or_coord) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_meal') {
        $meal_date = $_POST['meal_date'] ?? '';
        $meal_time = $_POST['meal_time'] ?? '';
        $meal_name = trim($_POST['meal_name'] ?? '');
        $expected_upcoming = (int)($_POST['expected_upcoming'] ?? 0);
        
        if ($meal_date && $meal_time && $meal_name) {
            try {
                $stmt = $pdo->prepare("INSERT INTO em_meals (event_id, meal_date, meal_time, meal_name, expected_upcoming) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$event_id, $meal_date, $meal_time, $meal_name, $expected_upcoming]);
                $message = 'भोजन सत्र सफलतापूर्वक जोड़ा गया! (Meal added successfully!)';
                $message_type = 'success';
            } catch (Exception $e) {
                $message = 'त्रुटि (Error): ' . $e->getMessage();
                $message_type = 'error';
            }
        } else {
            $message = 'कृपया सभी अनिवार्य विवरण भरें (Please fill all required fields).';
            $message_type = 'error';
        }
    } elseif ($action === 'update_upcoming') {
        $meal_id = (int)($_POST['meal_id'] ?? 0);
        $expected_upcoming = (int)($_POST['expected_upcoming'] ?? 0);
        
        if ($meal_id > 0) {
            try {
                $stmt = $pdo->prepare("UPDATE em_meals SET expected_upcoming = ? WHERE id = ? AND event_id = ?");
                $stmt->execute([$expected_upcoming, $meal_id, $event_id]);
                $message = 'अतिरिक्त संख्या अपडेट की गई (Expected upcoming updated!)';
                $message_type = 'success';
            } catch (Exception $e) {
                $message = 'त्रुटि (Error): ' . $e->getMessage();
                $message_type = 'error';
            }
        }
    }
}

// Get Base Counts
$total_participants = 0;
$total_organizers = 0;
try {
    $total_participants = $pdo->query("SELECT COUNT(*) FROM em_participants WHERE event_id = $event_id")->fetchColumn() ?: 0;
    $total_organizers = $pdo->query("SELECT COUNT(*) FROM em_organizers WHERE event_id = $event_id")->fetchColumn() ?: 0;
} catch (Exception $e) {
    $total_participants = 150;
    $total_organizers = 30;
}
$base_count = $total_participants + $total_organizers;

// Fetch Meals
$meals = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM em_meals WHERE event_id = ? ORDER BY meal_date ASC, meal_time ASC");
    $stmt->execute([$event_id]);
    $meals = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Ignore
}

include 'includes/header.php';
?>

<h2>भोजन व्यवस्था (Food Management)</h2>
<p>अनुमानित उपस्थित जन (Base Present Count): <strong><?= $base_count ?></strong> (<?= $total_participants ?> प्रतिभागी + <?= $total_organizers ?> प्रबंधक)</p>

<?php if ($message): ?>
    <div style="padding: 1rem; margin-bottom: 1rem; border-radius: 4px; background-color: <?= $message_type === 'success' ? '#1b5e20' : '#b71c1c' ?>; color: white;">
        <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>

<?php if ($is_admin_or_coord): ?>
<div class="card" style="margin-bottom: 2rem;">
    <h3 style="margin-top: 0;">नया भोजन सत्र जोड़ें (Add New Meal)</h3>
    <form method="POST" action="">
        <input type="hidden" name="action" value="add_meal">
        <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
            <div class="form-group">
                <label>दिनांक (Date) *</label>
                <input type="date" name="meal_date" class="form-control" required>
            </div>
            <div class="form-group">
                <label>समय (Time) *</label>
                <input type="time" name="meal_time" class="form-control" required>
            </div>
            <div class="form-group">
                <label>भोजन का नाम (Meal Name) *</label>
                <input type="text" name="meal_name" class="form-control" placeholder="उदा. अल्पाहार, दोपहर का भोजन" required>
            </div>
            <div class="form-group">
                <label>आगामी/अतिरिक्त (Expected Additional) *</label>
                <input type="number" name="expected_upcoming" class="form-control" min="0" value="0" required>
            </div>
        </div>
        <button type="submit" class="btn" style="margin-top: 1rem;">जोड़ें (Add Meal)</button>
    </form>
</div>
<?php endif; ?>

<div class="grid">
    <?php if (count($meals) > 0): ?>
        <?php foreach($meals as $meal): 
            $total_plates = $base_count + $meal['expected_upcoming'];
        ?>
        <div class="card" style="text-align: center;">
            <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">🍽️</div>
            <h3 style="color: var(--saffron); margin-bottom: 0.2rem;"><?= htmlspecialchars($meal['meal_name']) ?></h3>
            <p style="color: #999; font-size: 0.9em; margin-bottom: 1rem;">
                <?= date('d M Y', strtotime($meal['meal_date'])) ?> | <?= date('h:i A', strtotime($meal['meal_time'])) ?>
            </p>
            
            <div style="display: flex; justify-content: space-around; background: var(--container-bg); padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                <div>
                    <div style="font-size: 0.9em; color: #999;">मूल (Base)</div>
                    <div style="font-weight: bold; font-size: 1.2em;"><?= $base_count ?></div>
                </div>
                <div>
                    <div style="font-size: 0.9em; color: #999;">अतिरिक्त (Extra)</div>
                    <div style="font-weight: bold; font-size: 1.2em; color: var(--amber);">+<?= $meal['expected_upcoming'] ?></div>
                </div>
                <div>
                    <div style="font-size: 0.9em; color: #999;">कुल (Total)</div>
                    <div style="font-weight: bold; font-size: 1.5em; color: #4caf50;"><?= $total_plates ?></div>
                </div>
            </div>

            <?php if ($is_admin_or_coord): ?>
            <form method="POST" action="" style="display: flex; align-items: center; justify-content: center; gap: 0.5rem; margin-top: 1rem;">
                <input type="hidden" name="action" value="update_upcoming">
                <input type="hidden" name="meal_id" value="<?= $meal['id'] ?>">
                <input type="number" name="expected_upcoming" class="form-control" style="width: 80px; text-align: center;" value="<?= $meal['expected_upcoming'] ?>" min="0">
                <button type="submit" class="btn" style="padding: 0.5rem;">अपडेट (Update)</button>
            </form>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p style="grid-column: 1 / -1; text-align: center; color: #999;">अभी तक कोई भोजन सत्र नहीं जोड़ा गया है। (No meals added yet.)</p>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
