<?php
/**
 * Login Page - संघस्थान लॉगिन
 */
require_once 'includes/auth.php';
require_once 'config/db.php';

if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Already logged in? Redirect to dashboard
if (isLoggedIn()) {
    if (isAdmin()) {
        header('Location: pages/admin_dashboard.php');
    } elseif (isSwayamsevak()) {
        header('Location: pages/swayamsevak_dashboard.php');
    } else {
        header('Location: pages/dashboard.php');
    }
    exit;
}

// Ensure login_attempts table exists
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS login_attempts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ip VARCHAR(45),
        attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
} catch (PDOException $e) {}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $ip = $_SERVER['REMOTE_ADDR'];

    if (empty($username) || empty($password)) {
        $error = 'कृपया उपयोगकर्ता नाम और पासवर्ड दोनों भरें।';
    } else {
        if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
            $error = 'Invalid request. Please try again.';
        } else {
            // Rate Limiting Check
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND attempted_at > (NOW() - INTERVAL 15 MINUTE)");
            $stmt->execute([$ip]);
            $attempts = $stmt->fetchColumn();

            if ($attempts >= 5) {
                $error = 'बहुत सारे असफल प्रयास। 15 मिनट बाद पुनः प्रयास करें।';
            } else {
                $login_success = false;
                
                // First check admin_users
                $stmt = $pdo->prepare("SELECT id, name, role, shakha_id, password FROM admin_users WHERE username = ?");
                $stmt->execute([$username]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password'])) {
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['admin_id'] = $user['id']; // For backward compatibility if needed
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['admin_name'] = $user['name']; // For backward compatibility
                    $_SESSION['user_type'] = $user['role'] ?? 'mukhyashikshak';
                    $_SESSION['shakha_id'] = $user['shakha_id'];
                    $login_success = true;

                    // Clear attempts on success
                    $stmt = $pdo->prepare("DELETE FROM login_attempts WHERE ip = ?");
                    $stmt->execute([$ip]);

                    if (isAdmin()) {
                        header('Location: pages/admin_dashboard.php');
                    } else {
                        header('Location: pages/dashboard.php');
                    }
                    exit;
                } else {
                    // Check swayamsevaks
                    $stmt = $pdo->prepare("SELECT id, name, shakha_id, password FROM swayamsevaks WHERE username = ? AND is_active = 1");
                    $stmt->execute([$username]);
                    $user = $stmt->fetch();

                    if ($user && $user['password'] && password_verify($password, $user['password'])) {
                        session_regenerate_id(true);
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_name'] = $user['name'];
                        $_SESSION['user_type'] = 'swayamsevak';
                        $_SESSION['shakha_id'] = $user['shakha_id'];
                        $login_success = true;

                        // Clear attempts on success
                        $stmt = $pdo->prepare("DELETE FROM login_attempts WHERE ip = ?");
                        $stmt->execute([$ip]);

                        header('Location: pages/swayamsevak_dashboard.php');
                        exit;
                    }
                }
                
                if (!$login_success) {
                    // Failed login attempt
                    $stmt = $pdo->prepare("INSERT INTO login_attempts (ip) VALUES (?)");
                    $stmt->execute([$ip]);
                    sleep(1);
                    $error = 'गलत उपयोगकर्ता नाम या पासवर्ड!';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="hi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>संघस्थान - लॉगिन</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Devanagari:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo substr(md5_file('assets/css/style.css'), 0, 8); ?>">
</head>

<body>
    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-logo">
                <img src="assets/images/flag_icon.png" class="brand-icon" style="height: 1.5em; margin-bottom: 8px;" alt="🚩">
                <h1>संघस्थान</h1>
                <p>दैनिक गतिविधि एवं उपस्थिति प्रबंधन</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger">⚠️
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="index.php">
                <input type="hidden" name="csrf_token" 
                       value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <div class="form-group">
                    <label for="username">उपयोगकर्ता नाम</label>
                    <input type="text" id="username" name="username" class="form-control"
                        placeholder="उपयोगकर्ता नाम दर्ज करें" required
                        value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="password">पासवर्ड</label>
                    <input type="password" id="password" name="password" class="form-control"
                        placeholder="पासवर्ड दर्ज करें" required>
                </div>
                <button type="submit" class="btn btn-primary">🔑 लॉगिन करें</button>
            </form>
        </div>
    </div>
</body>

</html>