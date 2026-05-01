<?php
/**
 * Public page header include
 * Usage: include __DIR__ . '/../includes/public_header.php';
 */
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
$userType = $_SESSION['user_type'] ?? null;
$dashUrl = '/pages/dashboard.php';
if ($userType === 'admin') $dashUrl = '/pages/admin_dashboard.php';
elseif ($userType === 'swayamsevak') $dashUrl = '/pages/swayamsevak_dashboard.php';

// DB connection
require_once __DIR__ . '/../config/db.php';
?>
<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'संघस्थान'; ?></title>
    <meta name="description" content="<?php echo $pageDesc ?? 'संघस्थान — सनातन ज्ञान का अमृत कोश'; ?>">
    <meta name="robots" content="index, follow">
    <?php if (isset($pageCanonical)): ?>
    <link rel="canonical" href="<?php echo $pageCanonical; ?>">
    <?php endif; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Devanagari:wght@300;400;500;600;700;800;900&family=Tiro+Devanagari+Sanskrit:ital@0;1&display=swap" rel="stylesheet">
    <link rel="icon" href="/assets/images/favicon.png" type="image/png">
    <link rel="stylesheet" href="/assets/css/home.css?v=20260501b">
    <link rel="stylesheet" href="/assets/css/public-content.css?v=20260501">
</head>
<body>
    <nav class="home-nav scrolled">
        <div class="nav-inner">
            <a href="/home.php" class="nav-logo-link">
                <img src="/assets/images/flag_icon.png" alt="ध्वज" class="nav-flag">
                <span class="nav-title">संघस्थान</span>
            </a>
            <div class="nav-links" id="nav-links">
                <a href="/home.php#sanskriti" class="nav-link">संस्कृति</a>
                <a href="/ekatmata-stotra/" class="nav-link">एकात्मता स्तोत्र</a>
                <a href="/prarthna/" class="nav-link">प्रार्थना</a>
                <?php if ($isLoggedIn): ?>
                    <a href="<?php echo $dashUrl; ?>" class="nav-link nav-link-cta">🚩 डैशबोर्ड</a>
                <?php else: ?>
                    <a href="/index.php" class="nav-link nav-link-cta">🔑 लॉगिन</a>
                <?php endif; ?>
            </div>
            <button class="nav-hamburger" id="nav-hamburger" aria-label="मेनू"><span></span><span></span><span></span></button>
        </div>
    </nav>
    <main class="public-main">
