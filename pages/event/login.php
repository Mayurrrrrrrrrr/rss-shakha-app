<?php
session_start();
require_once '../../config/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM em_organizers WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['event_user_id'] = $user['id'];
                $_SESSION['event_user_name'] = $user['name'];
                $_SESSION['event_role'] = $user['role'];
                
                // Find active event from em_events
                $stmt = $pdo->query("SELECT id, name FROM em_events WHERE status = 'active' LIMIT 1");
                $event = $stmt->fetch();
                if ($event) {
                    $_SESSION['event_id'] = $event['id'];
                    $_SESSION['event_name'] = $event['name'];
                }
                
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'अमान्य क्रेडेंशियल (Invalid credentials)';
            }
        } catch (Exception $e) {
            $error = 'डेटाबेस त्रुटि (Database error)';
        }
    }
}
?>
<?php include 'includes/header.php'; ?>

<div style="max-width: 400px; margin: 4rem auto;">
    <div class="card">
        <h2 style="text-align: center; color: var(--saffron);">लॉग इन (Login)</h2>
        <?php if ($error): ?>
            <div style="color: red; margin-bottom: 1rem; text-align: center;"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="form-group">
                <label>उपयोगकर्ता नाम (Username)</label>
                <input type="text" name="username" class="form-control" required>
            </div>
            <div class="form-group">
                <label>पासवर्ड (Password)</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn" style="width: 100%;">लॉग इन करें</button>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
