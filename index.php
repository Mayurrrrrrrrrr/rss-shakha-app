<?php
/**
 * Login Page - संघस्थान लॉगिन
 */
require_once 'includes/auth.php';
require_once 'config/db.php';

// Security headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// CSRF token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Already logged in? Redirect
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

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CSRF check
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $error = 'अमान्य अनुरोध। पृष्ठ पुनः लोड करें।';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $ip = $_SERVER['REMOTE_ADDR'];

        if (empty($username) || empty($password)) {
            $error = 'कृपया उपयोगकर्ता नाम और पासवर्ड दोनों भरें।';
        } else {
            // Rate limiting - check attempts
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
            $stmt->execute([$ip]);
            if ($stmt->fetchColumn() >= 5) {
                $error = 'बहुत अधिक प्रयास। 15 मिनट बाद पुनः प्रयास करें।';
            } else {
                // Check admin_users
                $stmt = $pdo->prepare("SELECT id, name, role, shakha_id, password FROM admin_users WHERE username = ?");
                $stmt->execute([$username]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password'])) {
                    // Clear attempts, regenerate session, login
                    $pdo->prepare("DELETE FROM login_attempts WHERE ip = ?")->execute([$ip]);
                    session_regenerate_id(true);
                    $_SESSION['user_id']    = $user['id'];
                    $_SESSION['admin_id']   = $user['id'];
                    $_SESSION['user_name']  = $user['name'];
                    $_SESSION['admin_name'] = $user['name'];
                    $_SESSION['user_type']  = $user['role'] ?? 'mukhyashikshak';
                    $_SESSION['shakha_id']  = $user['shakha_id'];
                    $_SESSION['last_active'] = time();
                    header('Location: ' . (isAdmin() ? 'pages/admin_dashboard.php' : 'pages/dashboard.php'));
                    exit;
                } else {
                    // Check swayamsevaks
                    $stmt = $pdo->prepare("SELECT id, name, shakha_id, password FROM swayamsevaks WHERE username = ? AND is_active = 1");
                    $stmt->execute([$username]);
                    $user = $stmt->fetch();

                    if ($user && !empty($user['password']) && password_verify($password, $user['password'])) {
                        $pdo->prepare("DELETE FROM login_attempts WHERE ip = ?")->execute([$ip]);
                        session_regenerate_id(true);
                        $_SESSION['user_id']     = $user['id'];
                        $_SESSION['user_name']   = $user['name'];
                        $_SESSION['user_type']   = 'swayamsevak';
                        $_SESSION['shakha_id']   = $user['shakha_id'];
                        $_SESSION['last_active'] = time();
                        header('Location: pages/swayamsevak_dashboard.php');
                        exit;
                    } else {
                        // Log failed attempt
                        $pdo->prepare("INSERT INTO login_attempts (ip) VALUES (?)")->execute([$ip]);
                        sleep(1);
                        $error = 'गलत उपयोगकर्ता नाम या पासवर्ड!';
                    }
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
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Devanagari:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo substr(md5_file('assets/css/style.css'), 0, 8); ?>">
</head>
<body>
<div class="login-wrapper">
    <div class="login-card">
        <div class="login-logo">
            <img src="assets/images/flag_icon.png" class="brand-icon" style="height:1.5em;margin-bottom:8px;" alt="RSS Flag">
            <h1>संघस्थान</h1>
            <p>दैनिक गतिविधि एवं उपस्थिति प्रबंधन</p>
        </div>

        <?php if (isset($_GET['timeout'])): ?>
            <div class="alert alert-warning">
                ⏱️ सत्र समाप्त हो गया। कृपया पुनः लॉगिन करें।
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger">⚠️ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="index.php">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
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