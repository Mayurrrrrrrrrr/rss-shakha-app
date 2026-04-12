<?php
/**
 * Login Page - संघस्थान लॉगिन
 */
require_once 'includes/auth.php';
require_once 'config/db.php';

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

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'कृपया उपयोगकर्ता नाम और पासवर्ड दोनों भरें।';
    } else {
        // First check admin_users
        $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['admin_id'] = $user['id']; // For backward compatibility if needed
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['admin_name'] = $user['name']; // For backward compatibility
            $_SESSION['user_type'] = $user['role'] ?? 'mukhyashikshak';
            $_SESSION['shakha_id'] = $user['shakha_id'];

            if (isAdmin()) {
                header('Location: pages/admin_dashboard.php');
            } else {
                header('Location: pages/dashboard.php');
            }
            exit;
        } else {
            // Check swayamsevaks
            $stmt = $pdo->prepare("SELECT * FROM swayamsevaks WHERE username = ? AND is_active = 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && $user['password'] && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_type'] = 'swayamsevak';
                $_SESSION['shakha_id'] = $user['shakha_id'];

                header('Location: pages/swayamsevak_dashboard.php');
                exit;
            } else {
                $error = 'गलत उपयोगकर्ता नाम या पासवर्ड!';
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
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo filemtime('assets/css/style.css'); ?>">
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