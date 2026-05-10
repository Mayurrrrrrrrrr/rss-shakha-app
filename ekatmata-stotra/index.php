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

            <div class="stotra-share" style="text-align: center; margin-top: 40px; margin-bottom: 20px;">
                <?php
                    $shareText = "🚩 *एकात्मता स्तोत्र* 🚩\n\nभारत की सांस्कृतिक और आध्यात्मिक एकता का अनुपम सूत्र।\n\nसम्पूर्ण स्तोत्र एवं अर्थ यहाँ देखें: https://sanghasthan.yuktaa.com/ekatmata-stotra/";
                    $waLink = "https://api.whatsapp.com/send?text=" . urlencode($shareText);
                ?>
                <a href="<?php echo $waLink; ?>" target="_blank" class="share-btn-wa" style="background: #25D366; color: white; text-decoration: none; padding: 12px 28px; border-radius: 30px; display: inline-flex; align-items: center; gap: 10px; font-weight: 700; box-shadow: 0 4px 15px rgba(37,211,102,0.3);">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M12.012 2c-5.508 0-9.988 4.48-9.988 9.988 0 1.76.459 3.413 1.26 4.852l-1.34 4.896 5.01-1.316c1.408.766 3.013 1.204 4.718 1.204 5.508 0 9.988-4.48 9.988-9.988s-4.48-9.988-9.988-9.988zm6.596 14.152c-.273.766-1.353 1.433-2.222 1.543-.591.074-1.363.132-3.961-.933-3.322-1.363-5.464-4.738-5.63-4.956-.165-.219-1.339-1.782-1.339-3.411 0-1.63.847-2.43 1.15-2.76.303-.33.666-.412.889-.412.222 0 .444.004.639.013.199.008.468-.076.734.568.273.659.932 2.274 1.012 2.438.079.164.133.356.024.573-.109.219-.164.356-.328.547-.164.192-.345.426-.492.573-.165.164-.338.344-.145.679.193.336.858 1.413 1.838 2.285.98 0.872 1.808 1.144 2.138 1.309.33.164.52.14.714-.079.192-.219.824-.961 1.042-1.285.219-.328.437-.273.738-.164.301.109 1.913.902 2.24 1.066.328.164.547.245.628.383.082.138.082.802-.191 1.568z"/></svg> साझा करें
                </a>
            </div>
        </div>
    </main>
</div>

<!-- Global Side Panel is provided by public_footer.php -->

<script src="script.js?v=<?php echo filemtime(__DIR__ . '/script.js'); ?>" charset="UTF-8"></script>

<?php require_once __DIR__ . '/../includes/public_footer.php'; ?>
