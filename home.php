<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
$userType = $_SESSION['user_type'] ?? null;
$dashUrl = '/pages/dashboard.php';
if ($userType === 'admin') $dashUrl = '/pages/admin_dashboard.php';
elseif ($userType === 'swayamsevak') $dashUrl = '/pages/swayamsevak_dashboard.php';
?>
<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>संघस्थान — सनातन ज्ञान एवं शाखा प्रबंधन</title>
    <meta name="description" content="संघस्थान - प्रार्थना, एकात्मता स्तोत्र, सुभाषित, गीत, घोषणाओं का सार्वजनिक संग्रह। RSS Shakha Management Portal.">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://sanghasthan.yuktaa.com/">
    <meta property="og:title" content="संघस्थान — सनातन ज्ञान का अमृत कोश">
    <meta property="og:description" content="प्रार्थना, एकात्मता स्तोत्र, सुभाषित, गीत और घोषणाओं का सुंदर संग्रह।">
    <meta property="og:type" content="website">
    <script type="application/ld+json">
    {"@context":"https://schema.org","@type":"WebSite","name":"संघस्थान","url":"https://sanghasthan.yuktaa.com","description":"सनातन ज्ञान एवं भारतीय संस्कृति का सार्वजनिक मंच","inLanguage":"hi"}
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Devanagari:wght@300;400;500;600;700;800;900&family=Tiro+Devanagari+Sanskrit:ital@0;1&display=swap" rel="stylesheet">
    <link rel="icon" href="assets/images/favicon.png" type="image/png">
    <link rel="stylesheet" href="assets/css/home.css?v=20260501b">
</head>
<body>

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
                    <a href="<?php echo $dashUrl; ?>" class="nav-link nav-link-cta">🚩 डैशबोर्ड</a>
                <?php else: ?>
                    <a href="/index.php" class="nav-link nav-link-cta">🔑 स्वयंसेवक लॉगिन</a>
                <?php endif; ?>
            </div>
            <button class="nav-hamburger" id="nav-hamburger" aria-label="मेनू">
                <span></span><span></span><span></span>
            </button>
        </div>
    </nav>

    <!-- Hero -->
    <section class="hero" id="hero">
        <div class="hero-bg-pattern"></div>
        <div class="hero-content">
            <div class="hero-icon">
                <img src="assets/images/flag_icon.png" alt="भगवा ध्वज" class="hero-flag">
            </div>
            <h1 class="hero-title">
                <span class="hero-title-main">संघस्थान</span>
                <span class="hero-title-sub">॥ परं वैभवं नेतुमेतत् स्वराष्ट्रम् ॥</span>
            </h1>
            <p class="hero-desc">शाखा जीवन, सनातन ज्ञान एवं भारतीय संस्कृति का सार्वजनिक मंच</p>
            <div class="hero-ornament">
                <span class="ornament-line"></span>
                <span class="ornament-symbol">ॐ</span>
                <span class="ornament-line"></span>
            </div>
        </div>
        <div class="scroll-indicator"><span class="scroll-arrow">↓</span></div>
    </section>

    <!-- Content Grid -->
    <section class="sanskriti-section" id="sanskriti">
        <div class="section-inner">
            <div class="section-header">
                <div class="section-ornament-left"></div>
                <h2 class="section-title">सांस्कृतिक सामग्री</h2>
                <div class="section-ornament-right"></div>
            </div>
            <p class="section-subtitle">भारतीय संस्कृति के अमूल्य रत्नों का संग्रह — सभी के लिए सुलभ</p>
            <div class="content-grid">
                <a href="/prarthna/" class="content-card" id="card-prarthna">
                    <div class="card-glow"></div>
                    <div class="card-icon">🙏</div>
                    <h3 class="card-title">प्रार्थना</h3>
                    <p class="card-desc">नमस्ते सदा वत्सले मातृभूमे — राष्ट्रीय स्वयंसेवक संघ की प्रार्थना</p>
                    <span class="card-arrow">→</span>
                </a>
                <a href="/ekatmata-stotra/" class="content-card" id="card-ekatmata">
                    <div class="card-glow"></div>
                    <div class="card-icon">📜</div>
                    <h3 class="card-title">एकात्मता स्तोत्र</h3>
                    <p class="card-desc">भारत की सांस्कृतिक एकता का अनुपम सूत्र — ३३ श्लोकों का संवादात्मक अनुभव</p>
                    <span class="card-arrow">→</span>
                </a>
                <a href="/subhashit/" class="content-card" id="card-subhashit">
                    <div class="card-glow"></div>
                    <div class="card-icon">✨</div>
                    <h3 class="card-title">सुभाषित</h3>
                    <p class="card-desc">संस्कृत के अमूल्य सूक्तियों का संग्रह — जीवन के हर पहलू पर मार्गदर्शन</p>
                    <span class="card-arrow">→</span>
                </a>
                <a href="/geet/" class="content-card" id="card-geet">
                    <div class="card-glow"></div>
                    <div class="card-icon">🎵</div>
                    <h3 class="card-title">गीत</h3>
                    <p class="card-desc">राष्ट्रभक्ति और सांस्कृतिक गीतों का संग्रह — एकल एवं सांघिक गीत</p>
                    <span class="card-arrow">→</span>
                </a>
                <a href="/ghoshnayein/" class="content-card" id="card-ghoshna">
                    <div class="card-glow"></div>
                    <div class="card-icon">🗣️</div>
                    <h3 class="card-title">घोषणाएं</h3>
                    <p class="card-desc">संस्कृत और हिंदी की प्रेरणादायक घोषणाओं का संकलन</p>
                    <span class="card-arrow">→</span>
                </a>
                <a href="/amrit-vachan/" class="content-card" id="card-amritvachan">
                    <div class="card-glow"></div>
                    <div class="card-icon">💎</div>
                    <h3 class="card-title">अमृत वचन</h3>
                    <p class="card-desc">मुख्य शिक्षकों द्वारा प्रेषित प्रेरणादायक अमृत वचन एवं सुविचार</p>
                    <span class="card-arrow">→</span>
                </a>
            </div>
        </div>
    </section>

    <!-- Panchang Calendar -->
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
                <span>सोम</span><span>मंगल</span><span>बुध</span><span>गुरु</span><span>शुक्र</span><span>शनि</span><span>रवि</span>
            </div>
            <div class="cal-grid" id="cal-grid"></div>
            <!-- Tithi popup -->
            <div class="cal-tithi-popup" id="cal-tithi-popup"></div>
            <div class="cal-festivals" id="cal-festivals">
                <h3 class="cal-festivals-title">इस माह के प्रमुख पर्व एवं जयंतियाँ</h3>
                <div class="cal-festivals-list" id="cal-festivals-list"></div>
            </div>
        </div>
    </section>

    <!-- Search -->
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
    const nav = document.getElementById('home-nav');
    window.addEventListener('scroll', () => nav.classList.toggle('scrolled', window.scrollY > 50));

    const hamburger = document.getElementById('nav-hamburger');
    const navLinks = document.getElementById('nav-links');
    hamburger.addEventListener('click', () => {
        navLinks.classList.toggle('open');
        hamburger.classList.toggle('active');
    });

    document.querySelectorAll('a[href^="#"]').forEach(a => {
        a.addEventListener('click', function(e) {
            e.preventDefault();
            const t = document.querySelector(this.getAttribute('href'));
            if (t) { t.scrollIntoView({ behavior: 'smooth', block: 'start' }); navLinks.classList.remove('open'); hamburger.classList.remove('active'); }
        });
    });

    // Search
    const searchInput = document.getElementById('search-input');
    const searchResults = document.getElementById('search-results');
    let stotraData = null;
    fetch('/ekatmata-stotra/data.json').then(r => r.json()).then(d => stotraData = d).catch(() => {});

    const prarthnaData = {
        "नमस्ते सदा वत्सले मातृभूमे": { title: "प्रार्थना — पहली पंक्ति", text: "नमस्ते सदा वत्सले मातृभूमे, त्वया हिन्दुभूमे सुखं वर्धितोऽहम्", link: "/prarthna/" },
        "प्रार्थना": { title: "प्रार्थना", text: "नमस्ते सदा वत्सले मातृभूमे — दैनिक प्रार्थना", link: "/prarthna/" },
        "एकात्मता स्तोत्र": { title: "एकात्मता स्तोत्र", text: "भारत की एकता का अनुपम सूत्र — ३३ श्लोकों का संग्रह", link: "/ekatmata-stotra/" }
    };

    let searchTimeout;
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => performSearch(this.value.trim()), 300);
    });

    function performSearch(q) {
        if (q.length < 2) { searchResults.innerHTML = ''; searchResults.classList.remove('has-results'); return; }
        const results = [];
        const ql = q.toLowerCase();
        if (stotraData) Object.keys(stotraData).forEach(k => {
            const e = stotraData[k];
            if (`${k} ${e.name} ${e.summary}`.toLowerCase().includes(ql))
                results.push({ title: e.name, desc: e.summary, link: `/ekatmata-stotra/#${encodeURIComponent(k)}`, source: 'एकात्मता स्तोत्र' });
        });
        Object.keys(prarthnaData).forEach(k => {
            const e = prarthnaData[k];
            if (`${k} ${e.title} ${e.text}`.toLowerCase().includes(ql))
                results.push({ title: e.title, desc: e.text, link: e.link, source: 'प्रार्थना' });
        });
        if (!results.length) { searchResults.innerHTML = '<div class="search-empty">कोई परिणाम नहीं मिला।</div>'; searchResults.classList.add('has-results'); return; }
        searchResults.innerHTML = results.slice(0, 8).map(r => `<a href="${r.link}" class="search-result-item"><div class="result-source">${r.source}</div><div class="result-title">${r.title}</div><div class="result-desc">${r.desc.substring(0,100)}</div></a>`).join('');
        searchResults.classList.add('has-results');
    }

    // Scroll animations
    const obs = new IntersectionObserver(entries => entries.forEach(e => { if (e.isIntersecting) e.target.classList.add('animate-in'); }), { threshold: 0.15, rootMargin: '0px 0px -50px 0px' });
    document.querySelectorAll('.content-card, .section-header, .cal-controls').forEach(el => obs.observe(el));

    // ========== TITHI CALCULATOR ==========
    function getTithi(date) {
        const ref = new Date(Date.UTC(2000, 0, 6, 18, 14)); // known new moon
        const syn = 29.53059;
        const diff = (date.getTime() - ref.getTime()) / 86400000;
        const age = ((diff % syn) + syn) % syn;
        const num = Math.floor(age / (syn / 30)) + 1;
        const shukla = ['प्रतिपदा','द्वितीया','तृतीया','चतुर्थी','पंचमी','षष्ठी','सप्तमी','अष्टमी','नवमी','दशमी','एकादशी','द्वादशी','त्रयोदशी','चतुर्दशी','पूर्णिमा'];
        const krishna = ['प्रतिपदा','द्वितीया','तृतीया','चतुर्थी','पंचमी','षष्ठी','सप्तमी','अष्टमी','नवमी','दशमी','एकादशी','द्वादशी','त्रयोदशी','चतुर्दशी','अमावस्या'];
        if (num <= 15) return { paksha: 'शुक्ल', name: shukla[num-1], full: 'शुक्ल ' + shukla[num-1] };
        return { paksha: 'कृष्ण', name: krishna[num-16], full: 'कृष्ण ' + krishna[num-16] };
    }

    // ========== CALENDAR ==========
    const hindiMonths = ['जनवरी','फ़रवरी','मार्च','अप्रैल','मई','जून','जुलाई','अगस्त','सितंबर','अक्टूबर','नवंबर','दिसंबर'];
    const engMonths = ['January','February','March','April','May','June','July','August','September','October','November','December'];

    const festivalsByMonth = {
        1:[{name:'मकर संक्रांति',tithi:'पौष/माघ',related:['आरावलि']},{name:'पराक्रम दिवस',tithi:'23 जनवरी',related:['सुभाष']},{name:'गणतंत्र दिवस',tithi:'26 जनवरी',related:['भारतमातरम्']}],
        2:[{name:'बसंत पंचमी',tithi:'माघ शुक्ल पंचमी',related:['सरस्वती']},{name:'शिव जयंती',tithi:'फाल्गुन कृष्ण तृतीया',related:['शिवभूपति']}],
        3:[{name:'महाशिवरात्रि',tithi:'फाल्गुन कृष्ण चतुर्दशी',related:['सोमनाथ']},{name:'होलिका/गौरांग पूर्णिमा',tithi:'फाल्गुन पूर्णिमा',related:['चैतन्य']},{name:'नव संवत्सर',tithi:'चैत्र शुक्ल प्रतिपदा',related:['केशव']}],
        4:[{name:'राम नवमी',tithi:'चैत्र शुक्ल नवमी',related:['श्रीरामो','अयोध्या']},{name:'महावीर जयंती',tithi:'चैत्र शुक्ल त्रयोदशी',related:['वैशाली']},{name:'बैसाखी',tithi:'13/14 अप्रैल',related:['अमृतसर','गुरुनानक']},{name:'अंबेडकर जयंती',tithi:'14 अप्रैल',related:['भीमराव']},{name:'हनुमान जयंती',tithi:'चैत्र पूर्णिमा',related:['हनुमान्']}],
        5:[{name:'बुद्ध पूर्णिमा',tithi:'वैशाख पूर्णिमा',related:['बुद्ध']},{name:'शंकर जयंती',tithi:'वैशाख शुक्ल पंचमी',related:['शङ्कर']},{name:'रवीन्द्र जयंती',tithi:'7 मई',related:['रवीन्द्र']}],
        6:[{name:'गंगा दशहरा',tithi:'ज्येष्ठ शुक्ल दशमी',related:['गङ्गा']},{name:'रथ यात्रा',tithi:'आषाढ़ शुक्ल द्वितीया',related:['पुरी']}],
        7:[{name:'गुरु पूर्णिमा',tithi:'आषाढ़ पूर्णिमा',related:['व्यास','गोरक्ष']},{name:'तिलक जयंती',tithi:'23 जुलाई',related:['तिलक']}],
        8:[{name:'स्वतंत्रता दिवस',tithi:'15 अगस्त',related:['भारतमातरम्']},{name:'रक्षाबंधन',tithi:'श्रावण पूर्णिमा',related:[]},{name:'जन्माष्टमी',tithi:'भाद्रपद कृष्ण अष्टमी',related:['कृष्णो','मथुरा']}],
        9:[{name:'गणेश चतुर्थी',tithi:'भाद्रपद शुक्ल चतुर्थी',related:['सह्य']},{name:'पितृ पक्ष',tithi:'भाद्रपद कृष्ण पक्ष',related:['गया']}],
        10:[{name:'नवरात्रि',tithi:'आश्विन शुक्ल प्रतिपदा',related:['विन्ध्य']},{name:'विजयदशमी',tithi:'आश्विन शुक्ल दशमी',related:['रामायणं']},{name:'दीपावली',tithi:'कार्तिक अमावस्या',related:[]},{name:'गाँधी जयंती',tithi:'2 अक्टूबर',related:['गान्धि']}],
        11:[{name:'कार्तिक पूर्णिमा',tithi:'कार्तिक पूर्णिमा',related:['काशी']},{name:'गुरु नानक जयंती',tithi:'कार्तिक पूर्णिमा',related:['गुरुनानक']},{name:'बिरसा मुंडा जयंती',tithi:'15 नवंबर',related:['बिरसा']}],
        12:[{name:'गीता जयंती',tithi:'मार्गशीर्ष शुक्ल एकादशी',related:['गीता','भारतं']},{name:'विवेकानंद जयंती तैयारी',tithi:'12 जनवरी',related:['विवेकानन्द']}]
    };

    let calMonth = new Date().getMonth(), calYear = new Date().getFullYear();

    function renderCalendar() {
        const grid = document.getElementById('cal-grid');
        const popup = document.getElementById('cal-tithi-popup');
        document.getElementById('cal-month-hindi').textContent = hindiMonths[calMonth] + ' ' + calYear;
        document.getElementById('cal-month-eng').textContent = engMonths[calMonth] + ' ' + calYear;

        let firstDay = new Date(calYear, calMonth, 1).getDay();
        firstDay = (firstDay + 6) % 7; // Monday=0
        const days = new Date(calYear, calMonth + 1, 0).getDate();
        const today = new Date();
        const isCur = calMonth === today.getMonth() && calYear === today.getFullYear();

        let html = '';
        for (let i = 0; i < firstDay; i++) html += '<div class="cal-day cal-day-empty"></div>';

        for (let d = 1; d <= days; d++) {
            const dt = new Date(calYear, calMonth, d, 12);
            const t = getTithi(dt);
            const isToday = isCur && d === today.getDate();
            html += `<div class="cal-day${isToday ? ' cal-day-today' : ''}" data-tithi="${t.full}" onclick="showTithi(this)">
                <span class="cal-day-num">${d}</span>
                <span class="cal-day-tithi">${t.paksha === 'शुक्ल' ? 'शु' : 'कृ'} ${t.name.substring(0,3)}</span>
            </div>`;
        }
        grid.innerHTML = html;
        popup.style.display = 'none';

        const fests = festivalsByMonth[calMonth + 1] || [];
        const fDiv = document.getElementById('cal-festivals-list');
        if (!fests.length) { fDiv.innerHTML = '<p class="cal-no-festivals">इस माह कोई विशेष पर्व चिह्नित नहीं है।</p>'; return; }
        fDiv.innerHTML = fests.map(f => {
            const links = f.related.map(r => stotraData && stotraData[r]
                ? `<a href="/ekatmata-stotra/#${encodeURIComponent(r)}" class="festival-person-link">${stotraData[r].name||r}</a>`
                : `<span class="festival-person">${r}</span>`).join(' ');
            return `<div class="festival-item"><div class="festival-name">${f.name}</div><div class="festival-tithi">${f.tithi}</div>${links?`<div class="festival-related">${links}</div>`:''}</div>`;
        }).join('');
    }

    window.showTithi = function(el) {
        const popup = document.getElementById('cal-tithi-popup');
        const day = el.querySelector('.cal-day-num').textContent;
        popup.innerHTML = `<strong>${day} ${hindiMonths[calMonth]}</strong><br>${el.dataset.tithi}`;
        popup.style.display = 'block';
        const rect = el.getBoundingClientRect();
        popup.style.top = (rect.bottom + window.scrollY + 8) + 'px';
        popup.style.left = Math.max(10, rect.left + rect.width/2 - 80) + 'px';
        setTimeout(() => popup.style.display = 'none', 3000);
    };

    document.getElementById('cal-prev').addEventListener('click', () => { calMonth--; if (calMonth < 0) { calMonth = 11; calYear--; } renderCalendar(); });
    document.getElementById('cal-next').addEventListener('click', () => { calMonth++; if (calMonth > 11) { calMonth = 0; calYear++; } renderCalendar(); });
    renderCalendar();
    </script>
</body>
</html>
