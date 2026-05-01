<?php
$isHome = true;
$pageTitle = 'संघस्थान — सनातन ज्ञान एवं शाखा प्रबंधन';
$pageDesc = 'संघस्थान - प्रार्थना, एकात्मता स्तोत्र, सुभाषित, गीत, घोषणाओं का सार्वजनिक संग्रह। RSS Shakha Management Portal.';
$pageCanonical = 'https://sanghasthan.yuktaa.com/';
require_once __DIR__ . '/includes/public_header.php';
?>

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
            
            <div class="cal-samvat-display" style="text-align: center; margin-bottom: 20px; color: var(--ink-muted); font-size: 0.9rem; font-weight: 500;">
                <span id="cal-vikram-samvat">विक्रम संवत २०८३</span> | <span id="cal-shaka-samvat">शालिवाहन शक १९४८</span>
            </div>
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
    if (nav) window.addEventListener('scroll', () => nav.classList.toggle('scrolled', window.scrollY > 50));

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
        
        // Month approximation
        const hMonths = ['चैत्र','वैशाख','ज्येष्ठ','आषाढ़','श्रावण','भाद्रपद','आश्विन','कार्तिक','मार्गशीर्ष','पौष','माघ','फाल्गुन'];
        // Chaitra starts around March 22
        const march22 = new Date(date.getFullYear(), 2, 22);
        let mDiff = (date - march22) / (86400000 * 29.53);
        let mIdx = Math.floor(mDiff) % 12;
        if (mIdx < 0) mIdx += 12;
        const hMonth = hMonths[mIdx];

        const shukla = ['प्रतिपदा','द्वितीया','तृतीया','चतुर्थी','पंचमी','षष्ठी','सप्तमी','अष्टमी','नवमी','दशमी','एकादशी','द्वादशी','त्रयोदशी','चतुर्दशी','पूर्णिमा'];
        const krishna = ['प्रतिपदा','द्वितीया','तृतीया','चतुर्थी','पंचमी','षष्ठी','सप्तमी','अष्टमी','नवमी','दशमी','एकादशी','द्वादशी','त्रयोदशी','चतुर्दशी','अमावस्या'];
        
        let res = { month: hMonth };
        if (num <= 15) {
            res.paksha = 'शुक्ल';
            res.name = shukla[num-1];
            res.full = hMonth + ' शुक्ल ' + shukla[num-1];
        } else {
            res.paksha = 'कृष्ण';
            res.name = krishna[num-16];
            res.full = hMonth + ' कृष्ण ' + krishna[num-16];
        }
        return res;
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

    const utsavLinks = {
        'वर्ष प्रतिपदा': '/utsav/details.php?id=varsh-pratipada',
        'राम नवमी': '/utsav/details.php?id=ram-navmi',
        'हनुमान जयंती': '/utsav/details.php?id=hanuman-jayanti',
        'हिंदू साम्राज्य दिवस': '/utsav/details.php?id=hindu-samrajya-diwas',
        'गुरु पूर्णिमा': '/utsav/details.php?id=guru-purnima',
        'रक्षाबंधन': '/utsav/details.php?id=raksha-bandhan',
        'विजयदशमी': '/utsav/details.php?id=vijayadashami',
        'दीपावली': '/utsav/details.php?id=diwali',
        'मकर संक्रांति': '/utsav/details.php?id=makar-sankranti',
        'नव संवत्सर': '/utsav/details.php?id=varsh-pratipada'
    };

    function renderCalendar() {
        const grid = document.getElementById('cal-grid');
        const popup = document.getElementById('cal-tithi-popup');
        document.getElementById('cal-month-hindi').textContent = hindiMonths[calMonth] + ' ' + calYear;
        document.getElementById('cal-month-eng').textContent = engMonths[calMonth] + ' ' + calYear;

        // Samvat Update
        const vSamvat = calYear + 57 + (calMonth > 2 ? 1 : 0);
        const sSamvat = calYear - 78 + (calMonth > 2 ? 1 : 0);
        document.getElementById('cal-vikram-samvat').textContent = `विक्रम संवत ${vSamvat}`;
        document.getElementById('cal-shaka-samvat').textContent = `शालिवाहन शक ${sSamvat}`;

        let firstDay = new Date(calYear, calMonth, 1).getDay();
        firstDay = (firstDay + 6) % 7; // Monday=0
        const days = new Date(calYear, calMonth + 1, 0).getDate();
        const today = new Date();
        const isCur = calMonth === today.getMonth() && calYear === today.getFullYear();

        const fests = festivalsByMonth[calMonth + 1] || [];

        let html = '';
        for (let i = 0; i < firstDay; i++) html += '<div class="cal-day cal-day-empty"></div>';

        for (let d = 1; d <= days; d++) {
            const dt = new Date(calYear, calMonth, d, 12);
            const t = getTithi(dt);
            const isToday = isCur && d === today.getDate();
            
            // Check if festival
            let festInfo = null;
            fests.forEach(f => {
                if (f.tithi.includes(d + ' ' + hindiMonths[calMonth]) || f.tithi.includes(t.full) || f.tithi.includes(t.name)) {
                    festInfo = f;
                }
            });

            html += `<div class="cal-day${isToday ? ' cal-day-today' : ''}${festInfo ? ' cal-day-festival' : ''}" data-tithi="${t.full}" data-fest="${festInfo?festInfo.name:''}" onclick="showTithi(this)">
                <span class="cal-day-num">${d}</span>
                <span class="cal-day-tithi">${t.paksha === 'शुक्ल' ? 'शु' : 'कृ'} ${t.name.substring(0,3)}</span>
                ${festInfo ? `<div class="cal-fest-dot"></div>` : ''}
            </div>`;
        }
        grid.innerHTML = html;
        popup.style.display = 'none';

        const fDiv = document.getElementById('cal-festivals-list');
        if (!fests.length) { fDiv.innerHTML = '<p class="cal-no-festivals">इस माह कोई विशेष पर्व चिह्नित नहीं है।</p>'; return; }
        fDiv.innerHTML = fests.map(f => {
            const uLink = utsavLinks[f.name];
            const nameHtml = uLink ? `<a href="${uLink}" class="festival-name-link">${f.name}</a>` : `<div class="festival-name">${f.name}</div>`;
            
            const links = f.related.map(r => stotraData && stotraData[r]
                ? `<a href="/ekatmata-stotra/#${encodeURIComponent(r)}" class="festival-person-link">${stotraData[r].name||r}</a>`
                : `<span class="festival-person">${r}</span>`).join(' ');
            return `<div class="festival-item">${nameHtml}<div class="festival-tithi">${f.tithi}</div>${links?`<div class="festival-related">${links}</div>`:''}</div>`;
        }).join('');
    }

    window.showTithi = function(el) {
        const popup = document.getElementById('cal-tithi-popup');
        const day = el.querySelector('.cal-day-num').textContent;
        const fest = el.dataset.fest;
        popup.innerHTML = `<strong>${day} ${hindiMonths[calMonth]}</strong><br>${el.dataset.tithi}${fest ? `<br><span style="color:var(--saffron);font-weight:700;">🚩 ${fest}</span>` : ''}`;
        popup.style.display = 'block';
        const rect = el.getBoundingClientRect();
        popup.style.top = (rect.bottom + window.scrollY + 8) + 'px';
        popup.style.left = Math.max(10, rect.left + rect.width/2 - 80) + 'px';
        
        if (!fest) setTimeout(() => popup.style.display = 'none', 3000);
    };

    document.getElementById('cal-prev').addEventListener('click', () => { calMonth--; if (calMonth < 0) { calMonth = 11; calYear--; } renderCalendar(); });
    document.getElementById('cal-next').addEventListener('click', () => { calMonth++; if (calMonth > 11) { calMonth = 0; calYear++; } renderCalendar(); });
    renderCalendar();
    </script>
</body>
</html>
