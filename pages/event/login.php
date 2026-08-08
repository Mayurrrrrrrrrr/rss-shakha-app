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
                $_SESSION['event_vyavastha'] = $user['vyavastha'] ?? 'all';
                $_SESSION['event_assigned_bhag'] = $user['assigned_bhag'] ?? '';
                
                // Find active event from em_events
                $stmt = $pdo->query("SELECT id, name FROM em_events WHERE status = 'active' LIMIT 1");
                $event = $stmt->fetch();
                if ($event) {
                    $_SESSION['event_id'] = $event['id'];
                    $_SESSION['event_name'] = $event['name'];
                }
                
                if ($_SESSION['event_role'] === 'admin') {
                    header('Location: dashboard.php');
                } elseif ($_SESSION['event_vyavastha'] === 'hajiri') {
                    header('Location: attendance.php');
                } elseif ($_SESSION['event_vyavastha'] === 'bhojan') {
                    header('Location: food.php');
                } elseif ($_SESSION['event_vyavastha'] === 'nivas') {
                    header('Location: rooms.php');
                } else {
                    header('Location: dashboard.php');
                }
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
        
        <div style="margin-top: 2rem; text-align: center; padding-top: 1.5rem; border-top: 1px solid rgba(0,0,0,0.1);">
            <h3 style="margin-bottom: 1rem; color: var(--text-color); font-family: 'Noto Sans Devanagari', sans-serif;">📱 मोबाइल ऐप डाउनलोड करें</h3>
            <a href="/assets/downloads/sanghasthan-event.apk" download class="btn" style="width: 100%; background: linear-gradient(135deg, #4CAF50, #2E7D32); color: white; display: inline-flex; align-items: center; justify-content: center; gap: 8px;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                Android App (APK) डाउनलोड करें
            </a>
            <p style="margin-top: 0.8rem; font-size: 0.85rem; color: #666;">(Version 1.0.0)</p>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
