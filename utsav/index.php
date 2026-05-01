<?php
$pageTitle = 'प्रमुख भारतीय उत्सव एवं जयंतियाँ | संघस्थान';
$pageDesc = 'भारतीय संस्कृति के प्रमुख पर्वों, उत्सवों और महान विभूतियों की जयंतियों का संग्रह।';
$pageCanonical = 'https://sanghasthan.yuktaa.com/utsav/';
require_once __DIR__ . '/../includes/public_header.php';

$utsavs = [
    ['id' => 'varsh-pratipada', 'name' => 'वर्ष प्रतिपदा', 'tithi' => 'चैत्र शुक्ल प्रतिपदा', 'desc' => 'हिंदू नव वर्ष और डॉ. हेडगेवार जयंती।', 'icon' => '🚩'],
    ['id' => 'ram-navmi', 'name' => 'राम नवमी', 'tithi' => 'चैत्र शुक्ल नवमी', 'desc' => 'मर्यादा पुरुषोत्तम भगवान श्री राम का जन्मोत्सव।', 'icon' => '🏹'],
    ['id' => 'hanuman-jayanti', 'name' => 'हनुमान जयंती', 'tithi' => 'चैत्र पूर्णिमा', 'desc' => 'भक्त शिरोमणि हनुमान जी का जन्मोत्सव।', 'icon' => '🐒'],
    ['id' => 'akshaya-tritiya', 'name' => 'अक्षय तृतीया', 'tithi' => 'वैशाख शुक्ल तृतीया', 'desc' => 'परशुराम जयंती एवं मांगलिक कार्यों हेतु अबूझ मुहूर्त।', 'icon' => '🪓'],
    ['id' => 'hindu-samrajya-diwas', 'name' => 'हिंदू साम्राज्य दिवस', 'tithi' => 'ज्येष्ठ शुक्ल त्रयोदशी', 'desc' => 'छत्रपति शिवाजी महाराज का राज्याभिषेक दिवस।', 'icon' => '⚔️'],
    ['id' => 'guru-purnima', 'name' => 'गुरु पूर्णिमा', 'tithi' => 'आषाढ़ पूर्णिमा', 'desc' => 'व्यास जयंती एवं गुरु पूजन का पर्व।', 'icon' => '🧘'],
    ['id' => 'raksha-bandhan', 'name' => 'रक्षाबंधन', 'tithi' => 'श्रावण पूर्णिमा', 'desc' => 'भ्रातृ-भगिनी प्रेम एवं श्रावणी पर्व।', 'icon' => '🎗️'],
    ['id' => 'janmashtami', 'name' => 'जन्माष्टमी', 'tithi' => 'भाद्रपद कृष्ण अष्टमी', 'desc' => 'भगवान श्री कृष्ण का जन्मोत्सव।', 'icon' => '🍶'],
    ['id' => 'vijayadashami', 'name' => 'विजयदशमी', 'tithi' => 'आश्विन शुक्ल दशमी', 'desc' => 'अधर्म पर धर्म की विजय एवं संघ स्थापना दिवस।', 'icon' => '🏹'],
    ['id' => 'diwali', 'name' => 'दीपावली', 'tithi' => 'कार्तिक अमावस्या', 'desc' => 'प्रकाश का महापर्व।', 'icon' => '🪔'],
    ['id' => 'makar-sankranti', 'name' => 'मकर संक्रांति', 'tithi' => 'पौष/माघ', 'desc' => 'सूर्य का उत्तरायण प्रवेश एवं तिल-गुड़ का उत्सव।', 'icon' => '🪁'],
];
?>

<div class="public-header">
    <h1>🚩 प्रमुख उत्सव</h1>
    <p>भारतीय काल-गणना एवं संस्कृति के गौरवशाली पर्व</p>
</div>

<div class="content-grid">
    <?php foreach ($utsavs as $u): ?>
        <a href="/utsav/details.php?id=<?php echo $u['id']; ?>" class="content-card">
            <div class="card-glow"></div>
            <div class="card-icon"><?php echo $u['icon']; ?></div>
            <h3 class="card-title"><?php echo $u['name']; ?></h3>
            <p class="card-desc"><?php echo $u['desc']; ?></p>
            <div class="card-meta" style="margin-top:12px; font-size:0.8rem; color:var(--saffron); font-weight:600;">
                🌙 <?php echo $u['tithi']; ?>
            </div>
            <span class="card-arrow">→</span>
        </a>
    <?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/../includes/public_footer.php'; ?>
