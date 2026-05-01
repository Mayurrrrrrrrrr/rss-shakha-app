<?php
/**
 * Public Homepage - संघस्थान
 * Beautiful, Indian, minimalistic landing page
 * Public content hub + Login gateway
 */

// No auth required - this is the public homepage
session_start();

// Check if user is already logged in
$isLoggedIn = isset($_SESSION['user_id']);
$userType = $_SESSION['user_type'] ?? null;
?>
<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>संघस्थान — भारतीय संस्कृति एवं शाखा प्रबंधन</title>
    <meta name="description" content="संघस्थान - भारतीय संस्कृति, प्रार्थना, एकात्मता स्तोत्र, सुभाषित, गीत, घोषणाओं का डिजिटल संग्रह। RSS Shakha Management Portal.">
    <meta name="keywords" content="संघस्थान, RSS, शाखा, एकात्मता स्तोत्र, प्रार्थना, सुभाषित, गीत, घोषणाएं, Indian culture">
    <meta property="og:title" content="संघस्थान — भारतीय संस्कृति का डिजिटल संग्रह">
    <meta property="og:description" content="प्रार्थना, एकात्मता स्तोत्र, सुभाषित, गीत और घोषणाओं का सुंदर संग्रह।">
    <meta property="og:type" content="website">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Devanagari:wght@300;400;500;600;700;800;900&family=Tiro+Devanagari+Sanskrit:ital@0;1&display=swap" rel="stylesheet">
    <link rel="icon" href="assets/images/favicon.png" type="image/png">
    <link rel="stylesheet" href="assets/css/home.css?v=20260501">
</head>
<body>

    <!-- Navbar -->
    <nav class="home-nav" id="home-nav">
        <div class="nav-inner">
            <a href="/home.php" class="nav-logo-link">
                <img src="assets/images/flag_icon.png" alt="ध्वज" class="nav-flag">
                <span class="nav-title">संघस्थान</span>
            </a>
            <div class="nav-links" id="nav-links">
                <a href="#sanskriti" class="nav-link">संस्कृति</a>
                <a href="#panchang" class="nav-link">पंचांग</a>
                <a href="#search-section" class="nav-link">खोजें</a>
                <a href="/ekatmata-stotra/" class="nav-link">एकात्मता स्तोत्र</a>
                <a href="/prarthna/" class="nav-link">प्रार्थना</a>
                <?php if ($isLoggedIn): ?>
                    <a href="<?php 
                        if ($userType === 'admin') echo '/pages/admin_dashboard.php';
                        elseif ($userType === 'swayamsevak') echo '/pages/swayamsevak_dashboard.php';
                        else echo '/pages/dashboard.php';
                    ?>" class="nav-link nav-link-cta">🚩 डैशबोर्ड</a>
                <?php else: ?>
                    <a href="/index.php" class="nav-link nav-link-cta">🔑 स्वयंसेवक लॉगिन</a>
                <?php endif; ?>
            </div>
            <button class="nav-hamburger" id="nav-hamburger" aria-label="मेनू">
                <span></span><span></span><span></span>
            </button>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero" id="hero">
        <div class="hero-bg-pattern"></div>
        <div class="hero-content">
            <div class="hero-icon">
                <img src="assets/images/flag_icon.png" alt="भगवा ध्वज" class="hero-flag">
            </div>
            <h1 class="hero-title">
                <span class="hero-title-main">संघस्थान</span>
                <span class="hero-title-sub">भारतीय संस्कृति का डिजिटल संग्रह</span>
            </h1>
            <p class="hero-desc">प्रार्थना, एकात्मता स्तोत्र, सुभाषित, गीत और घोषणाओं का सुंदर एवं खोजने योग्य संग्रह</p>
            <div class="hero-ornament">
                <span class="ornament-line"></span>
                <span class="ornament-symbol">ॐ</span>
                <span class="ornament-line"></span>
            </div>
            <div class="hero-actions">
                <a href="#sanskriti" class="btn-hero btn-hero-primary">📖 सामग्री देखें</a>
                <?php if (!$isLoggedIn): ?>
                    <a href="/index.php" class="btn-hero btn-hero-outline">🔑 स्वयंसेवक लॉगिन</a>
                <?php else: ?>
                    <a href="<?php 
                        if ($userType === 'admin') echo '/pages/admin_dashboard.php';
                        elseif ($userType === 'swayamsevak') echo '/pages/swayamsevak_dashboard.php';
                        else echo '/pages/dashboard.php';
                    ?>" class="btn-hero btn-hero-outline">🚩 डैशबोर्ड जाएं</a>
                <?php endif; ?>
            </div>
        </div>
        <div class="scroll-indicator">
            <span class="scroll-arrow">↓</span>
        </div>
    </section>

    <!-- Public Content Grid -->
    <section class="sanskriti-section" id="sanskriti">
        <div class="section-inner">
            <div class="section-header">
                <div class="section-ornament-left"></div>
                <h2 class="section-title">सांस्कृतिक सामग्री</h2>
                <div class="section-ornament-right"></div>
            </div>
            <p class="section-subtitle">भारतीय संस्कृति के अमूल्य रत्नों का संग्रह — सभी के लिए सुलभ</p>

            <div class="content-grid">
                <!-- Prarthna Card -->
                <a href="/prarthna/" class="content-card" id="card-prarthna">
                    <div class="card-glow"></div>
                    <div class="card-icon">🙏</div>
                    <h3 class="card-title">प्रार्थना</h3>
                    <p class="card-desc">नमस्ते सदा वत्सले मातृभूमे — राष्ट्रीय स्वयंसेवक संघ की प्रार्थना</p>
                    <span class="card-arrow">→</span>
                </a>

                <!-- Ekatmata Stotra Card -->
                <a href="/ekatmata-stotra/" class="content-card" id="card-ekatmata">
                    <div class="card-glow"></div>
                    <div class="card-icon">📜</div>
                    <h3 class="card-title">एकात्मता स्तोत्र</h3>
                    <p class="card-desc">भारत की सांस्कृतिक और आध्यात्मिक एकता का अनुपम सूत्र — ३३ श्लोकों का संवादात्मक अनुभव</p>
                    <span class="card-arrow">→</span>
                </a>

                <!-- Subhashit Card -->
                <a href="/prarthna/#subhashit" class="content-card content-card-coming" id="card-subhashit">
                    <div class="card-glow"></div>
                    <div class="card-icon">✨</div>
                    <h3 class="card-title">सुभाषित</h3>
                    <p class="card-desc">संस्कृत के अमूल्य सूक्तियों का संग्रह — जीवन के हर पहलू पर मार्गदर्शन</p>
                    <span class="card-badge">शीघ्र</span>
                    <span class="card-arrow">→</span>
                </a>

                <!-- Geet Card -->
                <a href="/prarthna/#geet" class="content-card content-card-coming" id="card-geet">
                    <div class="card-glow"></div>
                    <div class="card-icon">🎵</div>
                    <h3 class="card-title">गीत</h3>
                    <p class="card-desc">राष्ट्रभक्ति और सांस्कृतिक गीतों का संग्रह — एकल एवं सांघिक गीत</p>
                    <span class="card-badge">शीघ्र</span>
                    <span class="card-arrow">→</span>
                </a>

                <!-- Ghoshnayein Card -->
                <a href="/prarthna/#ghoshna" class="content-card content-card-coming" id="card-ghoshna">
                    <div class="card-glow"></div>
                    <div class="card-icon">🗣️</div>
                    <h3 class="card-title">घोषणाएं</h3>
                    <p class="card-desc">संस्कृत और हिंदी की प्रेरणादायक घोषणाओं का संकलन</p>
                    <span class="card-badge">शीघ्र</span>
                    <span class="card-arrow">→</span>
                </a>
            </div>
        </div>
    </section>

    <!-- Search Section -->
    <section class="search-section" id="search-section">
        <div class="section-inner">
            <div class="section-header">
                <div class="section-ornament-left"></div>
                <h2 class="section-title">खोजें</h2>
                <div class="section-ornament-right"></div>
            </div>
            <p class="section-subtitle">सम्पूर्ण सांस्कृतिक सामग्री में खोजें</p>
            <div class="search-box">
                <input type="text" id="search-input" class="search-input" placeholder="यहाँ खोजें... (उदा. गंगा, प्रार्थना, राम)" autocomplete="off">
                <button class="search-btn" id="search-btn">🔍</button>
            </div>
            <div id="search-results" class="search-results"></div>
        </div>
    </section>

    <!-- Indian Tithi Calendar Section -->
    <section class="calendar-section" id="panchang">
        <div class="section-inner">
            <div class="section-header">
                <div class="section-ornament-left"></div>
                <h2 class="section-title">पंचांग — उत्सव दर्शिका</h2>
                <div class="section-ornament-right"></div>
            </div>
            <p class="section-subtitle">भारतीय तिथि पंचांग — एकात्मता स्तोत्र में वर्णित पर्व एवं जयंतियाँ</p>

            <div class="cal-controls">
                <button class="cal-nav-btn" id="cal-prev">← पिछला</button>
                <div class="cal-month-display">
                    <span class="cal-month-hindi" id="cal-month-hindi"></span>
                    <span class="cal-month-eng" id="cal-month-eng"></span>
                </div>
                <button class="cal-nav-btn" id="cal-next">अगला →</button>
            </div>

            <div class="cal-weekdays">
                <span>रवि</span><span>सोम</span><span>मंगल</span><span>बुध</span><span>गुरु</span><span>शुक्र</span><span>शनि</span>
            </div>
            <div class="cal-grid" id="cal-grid"></div>

            <!-- Festivals List -->
            <div class="cal-festivals" id="cal-festivals">
                <h3 class="cal-festivals-title">इस माह के प्रमुख पर्व एवं जयंतियाँ</h3>
                <div class="cal-festivals-list" id="cal-festivals-list"></div>
            </div>
        </div>
    </section>

    <!-- Login CTA Section -->
    <section class="login-cta-section" id="login-section">
        <div class="section-inner">
            <div class="cta-card">
                <div class="cta-icon">🚩</div>
                <h2 class="cta-title">स्वयंसेवक लॉगिन</h2>
                <p class="cta-desc">शाखा प्रबंधन, दैनिक रिकॉर्ड, उपस्थिति और सम्पूर्ण डिजिटल शाखा प्रणाली के लिए लॉगिन करें।<br>
                <small>सुपर एडमिन, मुख्यशिक्षक और स्वयंसेवक — सभी यहाँ से लॉगिन कर सकते हैं।</small></p>
                <?php if ($isLoggedIn): ?>
                    <a href="<?php 
                        if ($userType === 'admin') echo '/pages/admin_dashboard.php';
                        elseif ($userType === 'swayamsevak') echo '/pages/swayamsevak_dashboard.php';
                        else echo '/pages/dashboard.php';
                    ?>" class="btn-cta">🚩 डैशबोर्ड पर जाएं</a>
                <?php else: ?>
                    <a href="/index.php" class="btn-cta">🔑 लॉगिन करें</a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="home-footer">
        <div class="footer-inner">
            <div class="footer-ornament">
                <span class="ornament-line"></span>
                <span class="ornament-symbol">॥</span>
                <span class="ornament-line"></span>
            </div>
            <p class="footer-text">॥ भारत माता की जय ॥</p>
            <p class="footer-copy">© <?php echo date('Y'); ?> संघस्थान — सर्वाधिकार सुरक्षित</p>
        </div>
    </footer>

    <script>
    // Navbar scroll effect
    const nav = document.getElementById('home-nav');
    window.addEventListener('scroll', () => {
        nav.classList.toggle('scrolled', window.scrollY > 50);
    });

    // Mobile menu
    const hamburger = document.getElementById('nav-hamburger');
    const navLinks = document.getElementById('nav-links');
    hamburger.addEventListener('click', () => {
        navLinks.classList.toggle('open');
        hamburger.classList.toggle('active');
    });

    // Smooth scroll
    document.querySelectorAll('a[href^="#"]').forEach(a => {
        a.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                navLinks.classList.remove('open');
                hamburger.classList.remove('active');
            }
        });
    });

    // Search functionality
    const searchInput = document.getElementById('search-input');
    const searchResults = document.getElementById('search-results');
    let stotraData = null;
    let prarthnaData = null;

    // Load stotra data for search
    fetch('/ekatmata-stotra/data.json')
        .then(r => r.json())
        .then(d => stotraData = d)
        .catch(() => {});

    // Prarthna content for search
    prarthnaData = {
        "नमस्ते सदा वत्सले मातृभूमे": {
            title: "प्रार्थना — पहली पंक्ति",
            text: "नमस्ते सदा वत्सले मातृभूमे, त्वया हिन्दुभूमे सुखं वर्धितोऽहम्",
            link: "/prarthna/"
        },
        "प्रार्थना": {
            title: "प्रार्थना",
            text: "नमस्ते सदा वत्सले मातृभूमे — राष्ट्रीय स्वयंसेवक संघ की दैनिक प्रार्थना",
            link: "/prarthna/"
        },
        "एकात्मता स्तोत्र": {
            title: "एकात्मता स्तोत्र",
            text: "भारत की सांस्कृतिक और आध्यात्मिक एकता का अनुपम सूत्र — ३३ श्लोकों का संग्रह",
            link: "/ekatmata-stotra/"
        }
    };

    let searchTimeout;
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => performSearch(this.value.trim()), 300);
    });

    function performSearch(query) {
        if (query.length < 2) {
            searchResults.innerHTML = '';
            searchResults.classList.remove('has-results');
            return;
        }

        const results = [];
        const q = query.toLowerCase();

        // Search stotra data
        if (stotraData) {
            Object.keys(stotraData).forEach(key => {
                const entry = stotraData[key];
                const searchable = `${key} ${entry.name} ${entry.summary}`.toLowerCase();
                if (searchable.includes(q)) {
                    results.push({
                        title: entry.name,
                        desc: entry.summary,
                        link: `/ekatmata-stotra/#${encodeURIComponent(key)}`,
                        source: 'एकात्मता स्तोत्र'
                    });
                }
            });
        }

        // Search prarthna data
        Object.keys(prarthnaData).forEach(key => {
            const entry = prarthnaData[key];
            const searchable = `${key} ${entry.title} ${entry.text}`.toLowerCase();
            if (searchable.includes(q)) {
                results.push({
                    title: entry.title,
                    desc: entry.text,
                    link: entry.link,
                    source: 'प्रार्थना'
                });
            }
        });

        if (results.length === 0) {
            searchResults.innerHTML = '<div class="search-empty">कोई परिणाम नहीं मिला। कृपया अन्य शब्द से खोजें।</div>';
            searchResults.classList.add('has-results');
            return;
        }

        const maxShow = 8;
        let html = results.slice(0, maxShow).map(r => `
            <a href="${r.link}" class="search-result-item">
                <div class="result-source">${r.source}</div>
                <div class="result-title">${r.title}</div>
                <div class="result-desc">${r.desc.substring(0, 100)}${r.desc.length > 100 ? '...' : ''}</div>
            </a>
        `).join('');

        if (results.length > maxShow) {
            html += `<div class="search-more">${results.length - maxShow} और परिणाम...</div>`;
        }

        searchResults.innerHTML = html;
        searchResults.classList.add('has-results');
    }

    // Card animation on scroll
    const observerOptions = { threshold: 0.15, rootMargin: '0px 0px -50px 0px' };
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-in');
            }
        });
    }, observerOptions);

    document.querySelectorAll('.content-card, .cta-card, .section-header, .cal-controls').forEach(el => {
        observer.observe(el);
    });

    // ========== INDIAN TITHI CALENDAR ==========
    const hindiMonthNames = ['जनवरी', 'फ़रवरी', 'मार्च', 'अप्रैल', 'मई', 'जून', 'जुलाई', 'अगस्त', 'सितंबर', 'अक्टूबर', 'नवंबर', 'दिसंबर'];
    const engMonthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

    // Festival data: approximate Gregorian month mapping for Indian tithis
    // These are approximate – real dates vary each year with lunar calendar
    const festivalsByMonth = {
        1: [ // January
            { name: 'मकर संक्रांति', tithi: 'पौष/माघ', related: ['आरावलि', 'कपिल'] },
            { name: 'पराक्रम दिवस', tithi: '23 जनवरी', related: ['सुभाष'] },
            { name: 'गणतंत्र दिवस', tithi: '26 जनवरी', related: ['भारतमातरम्'] }
        ],
        2: [ // February
            { name: 'बसंत पंचमी', tithi: 'माघ शुक्ल पंचमी', related: ['सरस्वती'] },
            { name: 'रविदास जयंती', tithi: 'माघ पूर्णिमा', related: ['रविदास'] },
            { name: 'शिव जयंती', tithi: 'फाल्गुन कृष्ण तृतीया', related: ['शिवभूपति'] }
        ],
        3: [ // March
            { name: 'महाशिवरात्रि', tithi: 'फाल्गुन कृष्ण चतुर्दशी', related: ['हिमालय', 'सोमनाथ'] },
            { name: 'होलिका दहन / गौरांग पूर्णिमा', tithi: 'फाल्गुन पूर्णिमा', related: ['चैतन्य'] },
            { name: 'वर्ष प्रतिपदा (नव संवत्सर)', tithi: 'चैत्र शुक्ल प्रतिपदा', related: ['केशव'] },
            { name: 'चेटी चंड', tithi: 'चैत्र शुक्ल द्वितीया', related: ['झूलेलाल'] }
        ],
        4: [ // April
            { name: 'राम नवमी', tithi: 'चैत्र शुक्ल नवमी', related: ['श्रीरामो', 'अयोध्या'] },
            { name: 'महावीर जयंती', tithi: 'चैत्र शुक्ल त्रयोदशी', related: ['वैशाली'] },
            { name: 'बैसाखी / प्रकाश पर्व', tithi: '13/14 अप्रैल', related: ['अमृतसर', 'गुरुनानक'] },
            { name: 'अंबेडकर जयंती', tithi: '14 अप्रैल', related: ['भीमराव'] },
            { name: 'परशुराम जयंती', tithi: 'वैशाख शुक्ल तृतीया', related: ['महेन्द्र'] },
            { name: 'हनुमान जयंती', tithi: 'चैत्र पूर्णिमा', related: ['हनुमान्'] }
        ],
        5: [ // May
            { name: 'बुद्ध पूर्णिमा', tithi: 'वैशाख पूर्णिमा', related: ['बुद्ध'] },
            { name: 'शंकर जयंती', tithi: 'वैशाख शुक्ल पंचमी', related: ['शङ्कर'] },
            { name: 'रवीन्द्र जयंती', tithi: '7 मई', related: ['रवीन्द्र'] },
            { name: 'वट सावित्री', tithi: 'ज्येष्ठ पूर्णिमा', related: ['सावित्री'] }
        ],
        6: [ // June
            { name: 'गंगा दशहरा', tithi: 'ज्येष्ठ शुक्ल दशमी', related: ['गङ्गा', 'माया'] },
            { name: 'रानी लक्ष्मीबाई बलिदान दिवस', tithi: '18 जून', related: ['लक्ष्मी'] },
            { name: 'रानी दुर्गावती बलिदान दिवस', tithi: '24 जून', related: ['दुर्गावती'] },
            { name: 'रथ यात्रा', tithi: 'आषाढ़ शुक्ल द्वितीया', related: ['पुरी'] }
        ],
        7: [ // July
            { name: 'गुरु पूर्णिमा', tithi: 'आषाढ़ पूर्णिमा', related: ['व्यास', 'तक्षशिला', 'गोरक्ष'] },
            { name: 'तिलक जयंती', tithi: '23 जुलाई', related: ['तिलक'] }
        ],
        8: [ // August
            { name: 'स्वतंत्रता दिवस', tithi: '15 अगस्त', related: ['भारतमातरम्'] },
            { name: 'रक्षाबंधन', tithi: 'श्रावण पूर्णिमा', related: [] },
            { name: 'जन्माष्टमी', tithi: 'भाद्रपद कृष्ण अष्टमी', related: ['कृष्णो', 'मथुरा', 'द्वारिका'] },
            { name: 'वरलक्ष्मी व्रत', tithi: 'श्रावण शुक्ल शुक्रवार', related: ['मलय'] }
        ],
        9: [ // September
            { name: 'गणेश चतुर्थी', tithi: 'भाद्रपद शुक्ल चतुर्थी', related: ['सह्य'] },
            { name: 'पर्युषण पर्व', tithi: 'भाद्रपद', related: ['जैनागमा'] },
            { name: 'पितृ पक्ष', tithi: 'भाद्रपद कृष्ण पक्ष', related: ['गया'] }
        ],
        10: [ // October
            { name: 'नवरात्रि', tithi: 'आश्विन शुक्ल प्रतिपदा', related: ['विन्ध्य'] },
            { name: 'विजयदशमी', tithi: 'आश्विन शुक्ल दशमी', related: ['रामायणं'] },
            { name: 'दीपावली', tithi: 'कार्तिक अमावस्या', related: [] },
            { name: 'गाँधी जयंती', tithi: '2 अक्टूबर', related: ['गान्धि'] },
            { name: 'भगिनी निवेदिता', tithi: '28 अक्टूबर', related: ['निवेदिता'] },
            { name: 'शरद पूर्णिमा', tithi: 'आश्विन पूर्णिमा', related: ['मीरा'] }
        ],
        11: [ // November
            { name: 'कार्तिक पूर्णिमा / देव दीपावली', tithi: 'कार्तिक पूर्णिमा', related: ['काशी', 'रैवतक'] },
            { name: 'गुरु नानक जयंती', tithi: 'कार्तिक पूर्णिमा', related: ['गुरुनानक'] },
            { name: 'बिरसा मुंडा जयंती', tithi: '15 नवंबर', related: ['बिरसा'] },
            { name: 'बाली जात्रा', tithi: 'कार्तिक पूर्णिमा', related: ['महानदी'] },
            { name: 'ज्ञानेश्वर पुण्यतिथि', tithi: 'कार्तिक वद्य एकादशी', related: ['ज्ञानेश्वर'] }
        ],
        12: [ // December
            { name: 'गीता जयंती', tithi: 'मार्गशीर्ष शुक्ल एकादशी', related: ['गीता', 'भारतं'] },
            { name: 'राष्ट्रीय युवा दिवस (विवेकानंद जयंती)', tithi: '12 जनवरी (उत्सव दिसम्बर तैयारी)', related: ['विवेकानन्द'] },
            { name: 'प्रकाश पर्व (गुरु ग्रंथ साहिब)', tithi: 'मार्गशीर्ष', related: ['गुरुग्रन्थः'] }
        ]
    };

    let calCurrentMonth = new Date().getMonth();
    let calCurrentYear = new Date().getFullYear();

    function renderCalendar() {
        const grid = document.getElementById('cal-grid');
        const monthHindi = document.getElementById('cal-month-hindi');
        const monthEng = document.getElementById('cal-month-eng');
        const festivalsDiv = document.getElementById('cal-festivals-list');

        monthHindi.textContent = hindiMonthNames[calCurrentMonth] + ' ' + calCurrentYear;
        monthEng.textContent = engMonthNames[calCurrentMonth] + ' ' + calCurrentYear;

        const firstDay = new Date(calCurrentYear, calCurrentMonth, 1).getDay();
        const daysInMonth = new Date(calCurrentYear, calCurrentMonth + 1, 0).getDate();
        const today = new Date();
        const isCurrentMonth = (calCurrentMonth === today.getMonth() && calCurrentYear === today.getFullYear());

        let html = '';
        // Empty cells before first day
        for (let i = 0; i < firstDay; i++) {
            html += '<div class="cal-day cal-day-empty"></div>';
        }

        for (let d = 1; d <= daysInMonth; d++) {
            const isToday = isCurrentMonth && d === today.getDate();
            html += `<div class="cal-day${isToday ? ' cal-day-today' : ''}">
                <span class="cal-day-num">${d}</span>
            </div>`;
        }

        grid.innerHTML = html;

        // Render festivals for this month
        const monthNum = calCurrentMonth + 1;
        const festivals = festivalsByMonth[monthNum] || [];

        if (festivals.length === 0) {
            festivalsDiv.innerHTML = '<p class="cal-no-festivals">इस माह कोई विशेष पर्व चिह्नित नहीं है।</p>';
        } else {
            festivalsDiv.innerHTML = festivals.map(f => {
                const relLinks = f.related.map(r => {
                    if (stotraData && stotraData[r]) {
                        return `<a href="/ekatmata-stotra/#${encodeURIComponent(r)}" class="festival-person-link">${stotraData[r].name || r}</a>`;
                    }
                    return `<span class="festival-person">${r}</span>`;
                }).join(' ');
                return `<div class="festival-item">
                    <div class="festival-name">${f.name}</div>
                    <div class="festival-tithi">${f.tithi}</div>
                    ${relLinks ? `<div class="festival-related">${relLinks}</div>` : ''}
                </div>`;
            }).join('');
        }
    }

    document.getElementById('cal-prev').addEventListener('click', () => {
        calCurrentMonth--;
        if (calCurrentMonth < 0) { calCurrentMonth = 11; calCurrentYear--; }
        renderCalendar();
    });

    document.getElementById('cal-next').addEventListener('click', () => {
        calCurrentMonth++;
        if (calCurrentMonth > 11) { calCurrentMonth = 0; calCurrentYear++; }
        renderCalendar();
    });

    renderCalendar();
    </script>
</body>
</html>
