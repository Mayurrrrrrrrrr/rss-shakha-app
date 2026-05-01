<?php
$pageTitle = 'राष्ट्रीय स्वयंसेवक संघ प्रार्थना | संघस्थान';
$pageDesc = 'नमस्ते सदा वत्सले मातृभूमे — आरएसएस प्रार्थना, संस्कृत बोल, हिंदी अर्थ एवं भावार्थ के साथ।';
$pageCanonical = 'https://sanghasthan.yuktaa.com/prarthna/';
require_once __DIR__ . '/../includes/public_header.php';
?>

<link rel="stylesheet" href="style.css?v=20260501">

<div class="prarthna-container" style="max-width: 800px; margin: 0 auto; padding: 40px 20px;">
    <header class="prarthna-header" style="text-align: center; margin-bottom: 40px;">
        <h1 style="color: var(--saffron); font-size: 2.5rem; margin-bottom: 10px;">आर. एस. एस. प्रार्थना</h1>
        <p style="color: var(--ink-muted); font-style: italic;">नमस्ते सदा वत्सले मातृभूमे</p>
    </header>

    <div class="prarthna-content" style="background: white; padding: 40px; border-radius: var(--radius); border: 1px solid var(--border-warm); box-shadow: var(--shadow-warm);">
        <div class="prarthna-sanskrit" style="font-family: 'Tiro Devanagari Sanskrit', serif; font-size: 1.4rem; line-height: 2.2; color: var(--green-deep); text-align: center; margin-bottom: 40px;">
            नमस्ते सदा वत्सले मातृभूमे<br>
            त्वया हिन्दुभूमे सुखं वर्धितोऽहम् ।<br>
            महामङ्गले पुण्यभूमे त्वदर्थे<br>
            पतत्वेष कायो नमस्ते नमस्ते ॥ १ ॥<br><br>
            
            प्रभो शक्तिमन् हिन्दुराष्ट्राङ्गभूता<br>
            इमे सादरं त्वाम् नमन्तो वयम् ।<br>
            त्वदीयाय कार्याय बद्धा कटीयम्<br>
            शुभामाशिषं देहि तत्पूर्तये ॥ २ ॥
            <!-- More content could be added here or fetched -->
        </div>

        <div class="share-options" style="text-align: center; margin-top: 30px;">
            <?php
            $waLink = "https://api.whatsapp.com/send?text=" . urlencode("🚩 *आर.एस.एस. प्रार्थना*\n\nनमस्ते सदा वत्सले मातृभूमे...\n\nपूर्ण प्रार्थना यहाँ देखें: https://sanghasthan.yuktaa.com/prarthna/");
            ?>
            <a href="<?php echo $waLink; ?>" target="_blank" class="share-btn-wa" style="background: #25D366; color: white; text-decoration: none; padding: 10px 24px; border-radius: 30px; display: inline-flex; align-items: center; gap: 8px; font-weight: 600;">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M12.012 2c-5.508 0-9.988 4.48-9.988 9.988 0 1.76.459 3.413 1.26 4.852l-1.34 4.896 5.01-1.316c1.408.766 3.013 1.204 4.718 1.204 5.508 0 9.988-4.48 9.988-9.988s-4.48-9.988-9.988-9.988zm6.596 14.152c-.273.766-1.353 1.433-2.222 1.543-.591.074-1.363.132-3.961-.933-3.322-1.363-5.464-4.738-5.63-4.956-.165-.219-1.339-1.782-1.339-3.411 0-1.63.847-2.43 1.15-2.76.303-.33.666-.412.889-.412.222 0 .444.004.639.013.199.008.468-.076.734.568.273.659.932 2.274 1.012 2.438.079.164.133.356.024.573-.109.219-.164.356-.328.547-.164.192-.345.426-.492.573-.165.164-.338.344-.145.679.193.336.858 1.413 1.838 2.285.98 0.872 1.808 1.144 2.138 1.309.33.164.52.14.714-.079.192-.219.824-.961 1.042-1.285.219-.328.437-.273.738-.164.301.109 1.913.902 2.24 1.066.328.164.547.245.628.383.082.138.082.802-.191 1.568z"/></svg> व्हाट्सएप पर शेयर करें
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/public_footer.php'; ?>
