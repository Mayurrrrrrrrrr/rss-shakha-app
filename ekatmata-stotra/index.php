<?php
$pageTitle = 'एकात्मता स्तोत्र | Ekatmata Stotra - भारत की एकता का मंत्र';
$pageDesc = 'भारत की सांस्कृतिक और आध्यात्मिक एकता को दर्शाने वाला "एकात्मता स्तोत्र" - ३३ श्लोकों का संवादात्मक अनुभव।';
$pageCanonical = 'https://sanghasthan.yuktaa.com/ekatmata-stotra/';
require_once __DIR__ . '/../includes/public_header.php';
?>

<link rel="stylesheet" href="style.css?v=<?php echo filemtime(__DIR__ . '/style.css'); ?>">

<style>
/* Override public-main to allow full-width desk surface */
.public-main {
    max-width: 100% !important;
    padding: 0 !important;
    margin: 0 !important;
}
.desk-surface {
    background-image: url('assets/wood.png');
    background-size: cover;
    background-attachment: fixed;
    background-position: center;
    min-height: 100vh;
    padding-top: 100px; /* Adjust for nav */
    padding-bottom: 60px;
}
body { background: #1A0E05; }
</style>

<div class="desk-surface">
    <!-- Manuscript -->
    <main class="manuscript-scroll">
        <div class="manuscript-page">
            <div class="page-rule-top"></div>
            
            <header class="stotra-header">
                <h1>एकात्मता स्तोत्र</h1>
                <p class="subtitle">भारत की सांस्कृतिक और आध्यात्मिक एकता का अनुपम सूत्र</p>
                <div class="header-ornament">
                    <span class="line"></span>
                    <span class="om">ॐ</span>
                    <span class="line"></span>
                </div>
            </header>

            <div id="stotra-content" class="verses-container">
                <!-- Loading skeleton -->
                <div style="padding: 2rem 0;">
                    <div class="skeleton-line" style="width: 80%;"></div>
                    <div class="skeleton-line" style="width: 60%;"></div>
                    <div class="skeleton-line" style="width: 70%;"></div>
                    <div class="skeleton-line" style="width: 50%;"></div>
                </div>
            </div>

            <div class="page-rule-bottom"></div>
            <div class="page-colophon">॥ इति एकात्मता स्तोत्रम् ॥</div>
        </div>
    </main>
</div>

<!-- Global Side Panel is provided by public_footer.php -->

<script src="script.js?v=<?php echo filemtime(__DIR__ . '/script.js'); ?>" charset="UTF-8"></script>

<?php require_once __DIR__ . '/../includes/public_footer.php'; ?>
