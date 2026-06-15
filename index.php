<?php
$isHome = true;
$pageTitle = 'संघस्थान — सनातन ज्ञान एवं शाखा प्रबंधन';
$pageDesc = 'संघस्थान - प्रार्थना, एकात्मता स्तोत्र, सुभाषित, गीत, घोषणाओं का सार्वजनिक संग्रह। RSS Shakha Management Portal.';
$pageCanonical = 'https://sanghasthan.yuktaa.com/';
require_once __DIR__ . '/includes/public_header.php';
?>

    <!-- Premium Indic Hero -->
    <style>
        .indic-hero {
            position: relative;
            min-height: 85vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #FFF9F2 0%, #FFEFE0 100%);
            overflow: hidden;
            text-align: center;
            border-bottom: 4px solid var(--saffron);
        }
        .indic-hero::before {
            content: '';
            position: absolute;
            top: -50%; left: -50%; width: 200%; height: 200%;
            background: radial-gradient(circle, rgba(255,107,0,0.05) 0%, transparent 60%);
            animation: rotateBg 60s linear infinite;
        }
        @keyframes rotateBg { 100% { transform: rotate(360deg); } }
        
        .hero-mandala {
            position: absolute;
            width: 800px;
            height: 800px;
            opacity: 0.04;
            background-image: url('data:image/svg+xml;utf8,<svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"><circle cx="50" cy="50" r="48" fill="none" stroke="%23FF6B00" stroke-width="0.5"/><path d="M50 2 L50 98 M2 50 L98 50 M16 16 L84 84 M16 84 L84 16" stroke="%23FF6B00" stroke-width="0.5"/></svg>');
            animation: slowSpin 120s linear infinite;
        }
        @keyframes slowSpin { 100% { transform: rotate(360deg); } }

        .indic-hero-content {
            position: relative;
            z-index: 2;
            max-width: 900px;
            padding: 40px;
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 30px;
            border: 1px solid rgba(255, 107, 0, 0.2);
            box-shadow: 0 30px 60px rgba(0,0,0,0.05), inset 0 0 0 1px rgba(255,255,255,0.5);
            transform: translateY(20px);
            animation: floatUp 1s cubic-bezier(0.2, 0.8, 0.2, 1) forwards;
        }
        @keyframes floatUp { to { transform: translateY(0); } }

        .indic-hero-flag {
            width: 90px;
            margin-bottom: 24px;
            filter: drop-shadow(0 10px 15px rgba(255,107,0,0.4));
            animation: floatGently 4s ease-in-out infinite;
        }
        @keyframes floatGently { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }

        .indic-hero-title {
            font-size: clamp(3rem, 6vw, 5.5rem);
            font-family: 'Noto Sans Devanagari', sans-serif;
            font-weight: 800;
            background: linear-gradient(135deg, #FF6B00 0%, #D83100 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
            line-height: 1.2;
        }

        .indic-hero-subtitle {
            font-size: clamp(1.2rem, 2.5vw, 1.8rem);
            color: #3C2A21;
            font-weight: 600;
            margin-bottom: 24px;
        }

        .indic-hero-desc {
            font-size: 1.2rem;
            color: #665A54;
            line-height: 1.8;
            max-width: 700px;
            margin: 0 auto;
        }
    </style>

    <section class="indic-hero">
        <div class="hero-mandala"></div>
        <div class="indic-hero-content">
            <img src="assets/images/flag_icon.png" alt="भगवा ध्वज" class="indic-hero-flag">
            <h1 class="indic-hero-title">संघस्थान</h1>
            <h2 class="indic-hero-subtitle">॥ परं वैभवं नेतुमेतत् स्वराष्ट्रम् ॥</h2>
            <div style="width: 60px; height: 3px; background: var(--saffron); margin: 0 auto 24px; border-radius: 2px;"></div>
            <p class="indic-hero-desc">शाखा जीवन, सनातन ज्ञान एवं भारतीय संस्कृति का सार्वजनिक मंच। हमारी गौरवशाली परम्पराओं और सांस्कृतिक रत्नों का संकलन।</p>
        </div>
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
                <a href="/ekatmata-mantra/" class="content-card" id="card-mantra">
                    <div class="card-glow"></div>
                    <div class="card-icon">🕉️</div>
                    <h3 class="card-title">एकात्मता मन्त्र</h3>
                    <p class="card-desc">यं वैदिका मन्त्रदृशः पुराणः — सर्वपन्थ समभाव का दिव्य मन्त्र</p>
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
                <a href="/pages/vyaktitv_view.php" class="content-card" id="card-vyaktitv">
                    <div class="card-glow"></div>
                    <div class="card-icon">🚩</div>
                    <h3 class="card-title">व्यक्तित्व</h3>
                    <p class="card-desc">डॉ. केशव बलिराम हेडगेवार — राष्ट्रीय स्वयंसेवक संघ के संस्थापक का प्रेरक जीवन परिचय</p>
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

    <script>
    // Search and Animations
    document.addEventListener('DOMContentLoaded', () => {
        const nav = document.getElementById('home-nav');
        if (nav) window.addEventListener('scroll', () => nav.classList.toggle('scrolled', window.scrollY > 50));

        const searchInput = document.getElementById('search-input');
        const searchResults = document.getElementById('search-results');
        let stotraData = null;
        fetch('/ekatmata-stotra/data.json').then(r => r.json()).then(d => stotraData = d).catch(() => {});

        const prarthnaData = {
            "नमस्ते सदा वत्सले मातृभूमे": { title: "प्रार्थना — पहली पंक्ति", text: "नमस्ते सदा वत्सले मातृभूमे, त्वया हिन्दुभूमे सुखं वर्धितोऽहम्", link: "/prarthna/" },
            "प्रार्थना": { title: "प्रार्थना", text: "नमस्ते सदा वत्सले मातृभूमे — दैनिक प्रार्थना", link: "/prarthna/" },
            "एकात्मता मन्त्र": { title: "एकात्मता मन्त्र", text: "यं वैदिका मन्त्रदृशः पुराणः — सर्वपन्थ समभाव का दिव्य मन्त्र", link: "/ekatmata-mantra/" },
            "यं वैदिका मन्त्रदृशः": { title: "एकात्मता मन्त्र — पहली पंक्ति", text: "यं वैदिका मन्त्रदृशः पुराणः इन्द्रं यमं मातरिश्वानमाहुः", link: "/ekatmata-mantra/" },
            "एकात्मता स्तोत्र": { title: "एकात्मता स्तोत्र", text: "भारत की एकता का अनुपम सूत्र — ३३ श्लोकों का संग्रह", link: "/ekatmata-stotra/" }
        };

        let searchTimeout;
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => performSearch(this.value.trim()), 300);
            });
        }

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

        const obs = new IntersectionObserver(entries => entries.forEach(e => { if (e.isIntersecting) e.target.classList.add('animate-in'); }), { threshold: 0.15 });
        document.querySelectorAll('.content-card, .section-header, .cal-controls').forEach(el => obs.observe(el));

        // Tithi Calc
        function getTithi(date) {
            const ref = new Date(Date.UTC(2000, 0, 6, 18, 14));
            const syn = 29.53059;
            const diff = (date.getTime() - ref.getTime()) / 86400000;
            const age = ((diff % syn) + syn) % syn;
            const num = Math.floor(age / (syn / 30)) + 1;
            const hMonths = ['चैत्र','वैशाख','ज्येष्ठ','आषाढ़','श्रावण','भाद्रपद','आश्विन','कार्तिक','मार्गशीर्ष','पौष','माघ','फाल्गुन'];
            const march22 = new Date(date.getFullYear(), 2, 22);
            let mDiff = (date - march22) / (86400000 * 29.53);
            let mIdx = Math.floor(mDiff) % 12; if (mIdx < 0) mIdx += 12;
            const hMonth = hMonths[mIdx];
            const shukla = ['प्रतिपदा','द्वितीया','तृतीया','चतुर्थी','पंचमी','षष्ठी','सप्तमी','अष्टमी','नवमी','दशमी','एकादशी','द्वादशी','त्रयोदशी','चतुर्दशी','पूर्णिमा'];
            const krishna = ['प्रतिपदा','द्वितीया','तृतीया','चतुर्थी','पंचमी','षष्ठी','सप्तमी','अष्टमी','नवमी','दशमी','एकादशी','द्वादशी','त्रयोदशी','चतुर्दशी','अमावस्या'];
            let res = { month: hMonth };
            if (num <= 15) { res.paksha = 'शुक्ल'; res.name = shukla[num-1]; res.full = hMonth + ' शुक्ल ' + shukla[num-1]; }
            else { res.paksha = 'कृष्ण'; res.name = krishna[num-16]; res.full = hMonth + ' कृष्ण ' + krishna[num-16]; }
            return res;
        }

        const hindiMonths = ['जनवरी','फ़रवरी','मार्च','अप्रैल','मई','जून','जुलाई','अगस्त','सितंबर','अक्टूबर','नवंबर','दिसंबर'];
        const engMonths = ['January','February','March','April','May','June','July','August','September','October','November','December'];
        const festivalsByMonth = {
            1:[{name:'मकर संक्रांति',tithi:'पौष/माघ',related:['आरावलि']},{name:'पराक्रम दिवस',tithi:'23 जनवरी',related:['सुभाष']},{name:'गणतंत्र दिवस',tithi:'26 जनवरी',related:['भारतमातरम्']}],
            2:[{name:'बसंत पंचमी',tithi:'माघ शुक्ल पंचमी',related:['सरस्वती']},{name:'शिव जयंती',tithi:'फाल्गुन कृष्ण तृतीया',related:['शिवभूपति']}],
            3:[{name:'महाशिवरात्रि',tithi:'फाल्गुन कृष्ण चतुर्दशी',related:['सोमनाथ']},{name:'होलिका/गौरांग पूर्णिमा',tithi:'फाल्गुन पूर्णिमा',related:['चैतन्य']},{name:'नव संवत्सर',tithi:'चैत्र शुक्ल प्रतिपदा',related:['केशव']}],
            4:[{name:'राम नवमी',tithi:'चैत्र शुक्ल नवमी',related:['श्रीरामो','अयोध्या']},{name:'महावीर जयंती',tithi:'चैत्र शुक्ल त्रयोदशी',related:['वैशाली']},{name:'बैसाखी',tithi:'13/14 अप्रैल',related:['अमृतसर','गुरुनानक']},{name:'अम्बेडकर जयंती',tithi:'14 अप्रैल',related:['भीमराव']},{name:'हनुमान जयंती',tithi:'चैत्र पूर्णिमा',related:['हनुमान्']}],
            5:[{name:'बुद्ध पूर्णिमा',tithi:'वैशाख पूर्णिमा',related:['बुद्ध']},{name:'शंकर जयंती',tithi:'वैशाख शुक्ल पंचमी',related:['शङ्कर']},{name:'रवीन्द्र जयंती',tithi:'7 मई',related:['रवीन्द्र']}],
            6:[{name:'गंगा दशहरा',tithi:'ज्येष्ठ शुक्ल दशमी',related:['गङ्गा']},{name:'रथ यात्रा',tithi:'आषाढ़ शुक्ल द्वितीया',related:['पुरी']}],
            7:[{name:'गुरु पूर्णिमा',tithi:'आषाढ़ पूर्णिमा',related:['व्यास','गोरक्ष']},{name:'तिलक जयंती',tithi:'23 जुलाई',related:['तिलक']}],
            8:[{name:'स्वतंत्रता दिवस',tithi:'15 अगस्त',related:['भारतमातरम्']},{name:'रक्षाबंधन',tithi:'श्रावण पूर्णिमा',related:[]},{name:'जन्माष्टमी',tithi:'भाद्रपद कृष्ण अष्टमी',related:['कृष्णो','मथुरा']}],
            9:[{name:'गणेश चतुर्थी',tithi:'भाद्रपद शुक्ल चतुर्थी',related:['सह्य']},{name:'पितृ पक्ष',tithi:'भाद्रपद कृष्ण पक्ष',related:['गया']}],
            10:[{name:'नवरात्रि',tithi:'आश्विन शुक्ल प्रतिपदा',related:['विन्ध्य']},{name:'विजयदशमी',tithi:'आश्विन शुक्ल दशमी',related:['रामायणं']},{name:'दीपावली',tithi:'कार्तिक अमावस्या',related:[]},{name:'गाँधी जयंती',tithi:'2 अक्टूबर',related:['गान्धि']}],
            11:[{name:'कार्तिक पूर्णिमा',tithi:'कार्तिक पूर्णिमा',related:['काशी']},{name:'गुरु नानक जयंती',tithi:'कार्तिक पूर्णिमा',related:['गुरुनानक']},{name:'बिरसा मुंडा जयंती',tithi:'15 नवंबर',related:['बिरसा']}],
            12:[{name:'गीता जयंती',tithi:'मार्गशीर्ष शुक्ल एकादशी',related:['गीता','भारतं']},{name:'विवेकानंद जयंती तैयारी',tithi:'12 जनवरी',related:['विवेकानन्द']}]
        };

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

        let calMonth = new Date().getMonth(), calYear = new Date().getFullYear();

        function renderCalendar() {
            const grid = document.getElementById('cal-grid');
            if (!grid) return;
            const popup = document.getElementById('cal-tithi-popup');
            const hMonthName = hindiMonths[calMonth];
            document.getElementById('cal-month-hindi').textContent = hMonthName + ' ' + calYear;
            document.getElementById('cal-month-eng').textContent = engMonths[calMonth] + ' ' + calYear;
            
            const vSamvat = calYear + 56 + (calMonth >= 2 ? 1 : 0);
            const sSamvat = calYear - 79 + (calMonth >= 2 ? 1 : 0);
            document.getElementById('cal-vikram-samvat').textContent = `विक्रम संवत ${vSamvat}`;
            document.getElementById('cal-shaka-samvat').textContent = `शालिवाहन शक ${sSamvat}`;
            
            let firstDay = new Date(calYear, calMonth, 1).getDay();
            firstDay = (firstDay + 6) % 7;
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
                let festInfo = null;
                fests.forEach(f => { if (f.tithi.includes(d + ' ' + hindiMonths[calMonth]) || f.tithi.includes(t.full) || f.tithi.includes(t.name)) festInfo = f; });
                html += `<div class="cal-day${isToday ? ' cal-day-today' : ''}${festInfo ? ' cal-day-festival' : ''}" data-tithi="${t.full}" data-fest="${festInfo?festInfo.name:''}" onclick="showTithi(this)">
                    <span class="cal-day-num">${d}</span>
                    <span class="cal-day-tithi">${t.paksha === 'शुक्ल' ? 'शु' : 'कृ'} ${t.name.substring(0,3)}</span>
                    ${festInfo ? `<div class="cal-fest-dot"></div>` : ''}
                </div>`;
            }
            grid.innerHTML = html;
            
            const fTitle = document.querySelector('.cal-festivals-title');
            if (fTitle) fTitle.textContent = `${hMonthName} माह के प्रमुख पर्व एवं जयंतियाँ`;

            const fDiv = document.getElementById('cal-festivals-list');
            if (!fests.length) { fDiv.innerHTML = '<p class="cal-no-festivals">इस माह कोई विशेष पर्व चिह्नित नहीं है।</p>'; }
            else fDiv.innerHTML = fests.map(f => {
                const uLink = utsavLinks[f.name];
                const nameHtml = uLink ? `<a href="${uLink}" class="festival-name-link">${f.name}</a>` : `<div class="festival-name">${f.name}</div>`;
                const links = f.related.map(r => stotraData && stotraData[r] ? `<a href="/ekatmata-stotra/#${encodeURIComponent(r)}" class="festival-person-link">${stotraData[r].name||r}</a>` : `<span class="festival-person">${r}</span>`).join(' ');
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

        const prev = document.getElementById('cal-prev');
        const next = document.getElementById('cal-next');
        if (prev) prev.addEventListener('click', () => { calMonth--; if (calMonth < 0) { calMonth = 11; calYear--; } renderCalendar(); });
        if (next) next.addEventListener('click', () => { calMonth++; if (calMonth > 11) { calMonth = 0; calYear++; } renderCalendar(); });
        renderCalendar();
    });
    </script>

    <?php require_once __DIR__ . '/includes/public_footer.php'; ?>
