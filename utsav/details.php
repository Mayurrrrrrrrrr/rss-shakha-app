<?php
$utsavId = $_GET['id'] ?? '';

$utsavData = [
    'varsh-pratipada' => [
        'title' => 'वर्ष प्रतिपदा (हिंदू नव वर्ष)',
        'tithi' => 'चैत्र शुक्ल प्रतिपदा',
        'content' => 'वर्ष प्रतिपदा हिंदू नव वर्ष का गौरवशाली प्रारंभ है। इसी दिन ब्रह्मा जी ने सृष्टि की रचना प्रारंभ की थी। यह दिन भारतीय काल-गणना (विक्रम संवत) का पहला दिन है। राष्ट्रीय स्वयंसेवक संघ के संस्थापक परम पूजनीय डॉ. केशव बलिराम हेडगेवार जी का जन्म भी इसी पावन तिथि को हुआ था। यह दिन शक्ति, उमंग और नई शुरुआत का प्रतीक है।',
        'importance' => 'सृष्टि रचना दिवस, युगाब्द का प्रारंभ, डॉ. हेडगेवार जयंती।',
        'color' => '#E65100'
    ],
    'hindu-samrajya-diwas' => [
        'title' => 'हिंदू साम्राज्य दिवस',
        'tithi' => 'ज्येष्ठ शुक्ल त्रयोदशी',
        'content' => 'हिंदू साम्राज्य दिवस छत्रपति शिवाजी महाराज के राज्याभिषेक का उत्सव है। १६७४ में इसी दिन शिवाजी महाराज ने रायगढ़ में स्वराज्य की स्थापना की थी। यह दिवस स्वाभिमान, शौर्य और धर्म-रक्षा के संकल्प का प्रतीक है। संघ में इसे उत्सव के रूप में मनाया जाता है।',
        'importance' => 'शिवाजी महाराज का राज्याभिषेक, हिंदवी स्वराज्य की स्थापना।',
        'color' => '#BF360C'
    ],
    'guru-purnima' => [
        'title' => 'गुरु पूर्णिमा (व्यास पूर्णिमा)',
        'tithi' => 'आषाढ़ पूर्णिमा',
        'content' => 'गुरु पूर्णिमा भारतीय संस्कृति में गुरु-शिष्य परंपरा का सबसे बड़ा उत्सव है। यह महर्षि वेदव्यास जी की जयंती का दिन है। संघ में व्यक्ति को गुरु न मानकर "भगवा ध्वज" को सर्वोच्च गुरु माना गया है, और इसी दिन ध्वज पूजन किया जाता है।',
        'importance' => 'गुरु पूजन, व्यास जयंती, त्याग और समर्पण का भाव।',
        'color' => '#FF9800'
    ],
    'raksha-bandhan' => [
        'title' => 'रक्षाबंधन',
        'tithi' => 'श्रावण पूर्णिमा',
        'content' => 'रक्षाबंधन केवल भाई-बहन का पर्व नहीं, बल्कि समाज की रक्षा का संकल्प है। संघ में इसे "सामाजिक समरसता" के पर्व के रूप में मनाया जाता है, जहाँ एक स्वयंसेवक दूसरे को राखी बाँधकर समाज और राष्ट्र की रक्षा का वचन देता है।',
        'importance' => 'रक्षा संकल्प, सामाजिक समरसता, श्रावणी पर्व।',
        'color' => '#F44336'
    ],
    'vijayadashami' => [
        'title' => 'विजयदशमी (दशहरा)',
        'tithi' => 'आश्विन शुक्ल दशमी',
        'content' => 'विजयदशमी अधर्म पर धर्म की विजय का पर्व है। भगवान श्री राम ने इसी दिन रावण का वध किया था। राष्ट्रीय स्वयंसेवक संघ की स्थापना भी १९२५ में विजयदशमी के दिन ही हुई थी। यह संघ का मुख्य उत्सव है जिसमें शस्त्र पूजन और पथ संचलन किया जाता है।',
        'importance' => 'संघ स्थापना दिवस, शस्त्र पूजन, बुराई पर अच्छाई की जीत।',
        'color' => '#D84315'
    ],
    'makar-sankranti' => [
        'title' => 'मकर संक्रांति',
        'tithi' => 'पौष/माघ (सूर्य का उत्तरायण)',
        'content' => 'मकर संक्रांति सूर्य के उत्तरायण होने का उत्सव है। यह प्रकृति के परिवर्तन और प्रेम का संदेश देता है। "तिल-गुड़ घ्या, गोड-गोड बोला" के मंत्र के साथ यह समाज में मिठास और मेल-जोल बढ़ाने का पर्व है।',
        'importance' => 'उत्तरायण प्रारंभ, सामाजिक मेल-जोल, दान-पुण्य का महत्व।',
        'color' => '#2E7D32'
    ],
    'ram-navmi' => [
        'title' => 'राम नवमी',
        'tithi' => 'चैत्र शुक्ल नवमी',
        'content' => 'राम नवमी भगवान श्री विष्णु के सातवें अवतार, मर्यादा पुरुषोत्तम श्री राम का जन्मोत्सव है। यह उत्सव चैत्र नवरात्रि के नौवें दिन मनाया जाता है। श्री राम का जीवन सत्य, धर्म और आदर्शों का जीवंत स्वरूप है।',
        'importance' => 'श्री राम जन्मोत्सव, सत्य और मर्यादा का विजय पर्व।',
        'color' => '#E65100'
    ],
    'hanuman-jayanti' => [
        'title' => 'हनुमान जयंती',
        'tithi' => 'चैत्र पूर्णिमा',
        'content' => 'हनुमान जयंती शक्ति, भक्ति और सेवा के प्रतीक श्री हनुमान जी का जन्मोत्सव है। पवनपुत्र हनुमान जी को अष्ट सिद्धि और नवनिधि के दाता माना जाता है। उनका जीवन निस्वार्थ सेवा और प्रभु भक्ति की पराकाष्ठा है।',
        'importance' => 'हनुमान जी का प्राकट्य, सेवा और भक्ति का आदर्श।',
        'color' => '#FF5722'
    ],
    'diwali' => [
        'title' => 'दीपावली',
        'tithi' => 'कार्तिक अमावस्या',
        'content' => 'दीपावली अंधकार पर प्रकाश, अज्ञान पर ज्ञान और बुराई पर अच्छाई की विजय का महापर्व है। इसी दिन भगवान श्री राम लंका विजय के पश्चात अयोध्या लौटे थे। यह उत्सव लक्ष्मी पूजन और खुशियों के दीप जलाने का दिन है।',
        'importance' => 'श्री राम का आगमन, लक्ष्मी पूजन, प्रकाश पर्व।',
        'color' => '#FFC107'
    ]
];

$data = $utsavData[$utsavId] ?? null;

if (!$data) {
    header("Location: /utsav/");
    exit;
}

$pageTitle = $data['title'] . ' | संघस्थान';
$pageDesc = $data['content'];
require_once __DIR__ . '/../includes/public_header.php';

$currentUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$shareText = "🚩 *" . $data['title'] . "*\n\n📅 तिथि: " . $data['tithi'] . "\n\n" . mb_substr($data['content'], 0, 150) . "...\n\nअधिक जानकारी के लिए यहाँ देखें: " . $currentUrl;
$waLink = "https://api.whatsapp.com/send?text=" . urlencode($shareText);
?>

<article class="utsav-details" style="max-width: 800px; margin: 0 auto; padding: 40px 24px;">
    <div class="utsav-header" style="text-align: center; margin-bottom: 40px;">
        <div class="utsav-tag" style="background: <?php echo $data['color']; ?>; color: white; display: inline-block; padding: 4px 16px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; margin-bottom: 16px;">प्रमुख उत्सव</div>
        <h1 style="color: var(--ink); font-size: 2.5rem; margin-bottom: 12px;"><?php echo $data['title']; ?></h1>
        <div class="utsav-tithi" style="font-size: 1.2rem; color: var(--saffron); font-weight: 600;">🌙 <?php echo $data['tithi']; ?></div>
    </div>

    <div class="utsav-main-content" style="background: white; padding: 40px; border-radius: var(--radius); border: 1px solid var(--border-warm); box-shadow: var(--shadow-warm); line-height: 1.8; font-size: 1.1rem; color: var(--ink-light);">
        <p><?php echo nl2br($data['content']); ?></p>
        
        <div style="margin-top: 32px; padding-top: 32px; border-top: 1px dashed var(--border-warm);">
            <h3 style="color: var(--ink); margin-bottom: 12px;">विशेष महत्व:</h3>
            <p style="color: var(--ink-muted);"><?php echo $data['importance']; ?></p>
        </div>

        <div class="share-options" style="margin-top: 40px; display: flex; align-items: center; gap: 16px;">
            <span style="font-weight: 600; color: var(--ink);">शेयर करें:</span>
            <a href="<?php echo $waLink; ?>" target="_blank" class="share-btn-wa" style="background: #25D366; color: white; text-decoration: none; padding: 10px 20px; border-radius: 30px; display: flex; align-items: center; gap: 8px; font-weight: 600; font-size: 0.95rem; transition: var(--transition);">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" style="margin-right:8px;"><path d="M12.012 2c-5.508 0-9.988 4.48-9.988 9.988 0 1.76.459 3.413 1.26 4.852l-1.34 4.896 5.01-1.316c1.408.766 3.013 1.204 4.718 1.204 5.508 0 9.988-4.48 9.988-9.988s-4.48-9.988-9.988-9.988zm6.596 14.152c-.273.766-1.353 1.433-2.222 1.543-.591.074-1.363.132-3.961-.933-3.322-1.363-5.464-4.738-5.63-4.956-.165-.219-1.339-1.782-1.339-3.411 0-1.63.847-2.43 1.15-2.76.303-.33.666-.412.889-.412.222 0 .444.004.639.013.199.008.468-.076.734.568.273.659.932 2.274 1.012 2.438.079.164.133.356.024.573-.109.219-.164.356-.328.547-.164.192-.345.426-.492.573-.165.164-.338.344-.145.679.193.336.858 1.413 1.838 2.285.98 0.872 1.808 1.144 2.138 1.309.33.164.52.14.714-.079.192-.219.824-.961 1.042-1.285.219-.328.437-.273.738-.164.301.109 1.913.902 2.24 1.066.328.164.547.245.628.383.082.138.082.802-.191 1.568z"/></svg> व्हाट्सएप पर शेयर करें
            </a>
        </div>
    </div>

    <div style="text-align: center; margin-top: 40px;">
        <a href="/utsav/" style="color: var(--saffron); text-decoration: none; font-weight: 600;">← सभी उत्सव देखें</a>
    </div>
</article>

<?php require_once __DIR__ . '/../includes/public_footer.php'; ?>
