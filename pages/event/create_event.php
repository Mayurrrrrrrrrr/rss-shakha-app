<?php
session_start();
require_once '../../config/db.php';
include 'includes/header.php';

if ($_SESSION['event_role'] !== 'admin') {
    echo "<div class='container'><div class='card'><h2 style='color:var(--danger)'>Permission Denied</h2><p>You don't have permission to create events.</p></div></div>";
    include 'includes/footer.php';
    exit;
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'set_active') {
        $evt_id = (int)($_POST['event_id'] ?? 0);
        if ($evt_id > 0) {
            $pdo->exec("UPDATE em_events SET status = 'inactive'");
            $stmt = $pdo->prepare("UPDATE em_events SET status = 'active' WHERE id = ?");
            $stmt->execute([$evt_id]);
            
            // Update session
            $evt = $pdo->query("SELECT id, name FROM em_events WHERE id = $evt_id")->fetch();
            $_SESSION['event_id'] = $evt['id'];
            $_SESSION['event_name'] = $evt['name'];
            $message = "Event '{$evt['name']}' is now active!";
        }
    } else {
        $name = trim($_POST['name'] ?? '');
        $start_date = $_POST['start_date'] ?? '';
        $end_date = $_POST['end_date'] ?? '';
        $venue = trim($_POST['venue'] ?? '');

        if (empty($name) || empty($start_date)) {
            $error = "Name and Start Date are required.";
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO em_events (name, start_date, end_date, venue, status) VALUES (?, ?, ?, ?, 'inactive')");
                $stmt->execute([$name, $start_date, $end_date, $venue]);
                $message = "Event created successfully!";
            } catch (Exception $e) {
                $error = "Database Error: " . $e->getMessage();
            }
        }
    }
}
?>

<div class="container">
    <div class="page-header" style="display:flex; justify-content: space-between; align-items:center; margin-bottom: 2rem;">
        <h2 style="margin:0;">नया आयोजन (Create Event)</h2>
        <a href="dashboard.php" class="btn btn-outline">वापस जाएं (Back)</a>
    </div>

    <div class="card" style="max-width: 600px; margin: 0 auto;">
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

        <form method="POST">
            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label>आयोजन का नाम (Event Name) *</label>
                <input type="text" name="name" class="form-control" required placeholder="उदा. प्राथमिक शिक्षा वर्ग">
            </div>
            
            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label>प्रारंभ तिथि (Start Date) *</label>
                <input type="date" name="start_date" class="form-control" required>
            </div>
            
            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label>समापन तिथि (End Date)</label>
                <input type="date" name="end_date" class="form-control">
            </div>
            
            <div class="form-group" style="margin-bottom: 2rem;">
                <label>स्थान (Venue)</label>
                <input type="text" name="venue" class="form-control" placeholder="उदा. सरस्वती शिशु मंदिर">
            </div>
            
            <button type="submit" class="btn" style="width: 100%; font-size: 1.1rem; padding: 0.8rem;">आयोजन बनाएं (Create Event)</button>
        </form>
    </div>

    <!-- Event List -->
    <div class="card" style="margin-top: 2rem;">
        <h3>आयोजनों की सूची (List of Events)</h3>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; margin-top: 1rem;">
                <thead>
                    <tr style="border-bottom: 2px solid var(--border-color); text-align: left;">
                        <th style="padding: 1rem;">ID</th>
                        <th style="padding: 1rem;">आयोजन (Event)</th>
                        <th style="padding: 1rem;">तिथि (Dates)</th>
                        <th style="padding: 1rem;">स्थान (Venue)</th>
                        <th style="padding: 1rem;">स्थिति (Status)</th>
                        <th style="padding: 1rem;">कार्रवाई (Action)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $events = $pdo->query("SELECT * FROM em_events ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($events as $evt):
                        $isActive = ($evt['status'] === 'active');
                    ?>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 1rem;"><?= $evt['id'] ?></td>
                        <td style="padding: 1rem; font-weight: bold;"><?= htmlspecialchars($evt['name']) ?></td>
                        <td style="padding: 1rem; color: var(--text-muted);">
                            <?= $evt['start_date'] ?> <br> 
                            <small>से <?= $evt['end_date'] ?: 'N/A' ?></small>
                        </td>
                        <td style="padding: 1rem;"><?= htmlspecialchars($evt['venue']) ?></td>
                        <td style="padding: 1rem;">
                            <?php if ($isActive): ?>
                                <span style="background: rgba(16,185,129,0.2); color: var(--success); padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.8rem;">सक्रिय (Active)</span>
                            <?php else: ?>
                                <span style="background: rgba(255,255,255,0.1); color: var(--text-muted); padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.8rem;">निजी (Inactive)</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 1rem;">
                            <?php if (!$isActive): ?>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('क्या आप इस आयोजन को सक्रिय करना चाहते हैं? (Set this as active?)')">
                                <input type="hidden" name="action" value="set_active">
                                <input type="hidden" name="event_id" value="<?= $evt['id'] ?>">
                                <button type="submit" class="btn" style="padding: 0.4rem 0.8rem; font-size: 0.8rem;">सक्रिय करें (Set Active)</button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
