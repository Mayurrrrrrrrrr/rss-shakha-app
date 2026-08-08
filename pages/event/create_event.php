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
    $name = trim($_POST['name'] ?? '');
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $venue = trim($_POST['venue'] ?? '');

    if (empty($name) || empty($start_date)) {
        $error = "Name and Start Date are required.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO em_events (name, start_date, end_date, venue, status) VALUES (?, ?, ?, ?, 'active')");
            $stmt->execute([$name, $start_date, $end_date, $venue]);
            $message = "Event created successfully!";
        } catch (Exception $e) {
            $error = "Database Error: " . $e->getMessage();
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
</div>

<?php include 'includes/footer.php'; ?>
