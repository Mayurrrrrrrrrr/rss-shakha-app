<?php
$pageTitle = 'एकात्मता स्तोत्र | Ekatmata Stotra - भारत की एकता का मंत्र';
$pageDesc = 'भारत की सांस्कृतिक और आध्यात्मिक एकता को दर्शाने वाला "एकात्मता स्तोत्र" - ३३ श्लोकों का संवादात्मक अनुभव।';
$pageCanonical = 'https://sanghasthan.yuktaa.com/ekatmata-stotra/';
require_once __DIR__ . '/../includes/public_header.php';
?>

<link rel="stylesheet" href="style.css?v=20260501c">

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

<!-- Side Panel (Desktop ≥1024px) -->
<aside class="side-panel" id="side-panel">
    <div class="side-panel-header">
        <button id="close-side-panel" class="close-btn" aria-label="बंद करें">✕</button>
        <div id="side-panel-title" class="side-panel-title"></div>
    </div>
    <div class="side-panel-inner" id="side-panel-content">
    </div>
</aside>

<!-- Bottom Sheet (Mobile/Tablet) -->
<div class="bottom-sheet" id="bottom-sheet">
    <div class="sheet-handle"></div>
    <div class="bottom-sheet-inner" id="bottom-sheet-content">
    </div>
</div>

<!-- Backdrop -->
<div class="backdrop" id="backdrop"></div>

<script src="script.js?v=20260501c" charset="UTF-8"></script>

<?php require_once __DIR__ . '/../includes/public_footer.php'; ?>
