<?php
require_once '../includes/auth.php';
require_once '../config/db.php';
requireLogin();

$shakhaId = getCurrentShakhaId();
$stmtShakha = $pdo->prepare("SELECT name, city_name FROM shakhas WHERE id = ?");
$stmtShakha->execute([$shakhaId]);
$shakha = $stmtShakha->fetch();
$shakhaName = $shakha['name'] ?? 'शाखा';
$shakhaCity = $shakha['city_name'] ?? 'मुम्बई';

// Force Hindi if it's still in English from default or manual entry
$cityMap = [
    'mumbai' => 'मुम्बई',
    'bhopal' => 'भोपाल',
    'delhi' => 'दिल्ली',
    'pune' => 'पुणे',
    'nagpur' => 'नागपुर',
    'indore' => 'इंदौर',
    'lucknow' => 'लखनऊ',
    'jaipur' => 'जयपुर'
];
$checkCity = strtolower($shakhaCity);
if (isset($cityMap[$checkCity])) {
    $shakhaCity = $cityMap[$checkCity];
}

$selectedDate = $_GET['date'] ?? date('Y-m-d');

$pageTitle = 'दैनिक पंचांग (Daily Panchang)';
require_once '../includes/header.php';
?>

<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Noto+Serif+Devanagari:wght@400;700;800;900&display=swap" rel="stylesheet">

<style>
    /* ===== PREMIUM PANCHANG CAPTURE FRAME ===== */
    .panchang-capture-container {
        background: linear-gradient(135deg, #aa771c, #fcf6ba, #aa771c);
        padding: 12px; border-radius: 4px; box-shadow: 0 20px 60px rgba(0,0,0,0.2);
        width: 100%; max-width: 550px; margin: 0 auto; position: relative;
        font-family: 'Noto Serif Devanagari', serif;
    }
    .panchang-capture-inner {
        background: #fff9e3;
        background-image: radial-gradient(circle at 50% 10%, #fffdf5 0%, #fff9e3 70%);
        border: 2px solid #5a4408;
        padding: 25px 18px;
        position: relative; overflow: hidden; box-sizing: border-box;
    }
    .corner-svg { position: absolute; width: 70px; height: 70px; fill: #8b6b0d; z-index: 5; pointer-events: none; }
    .tl { top: 0; left: 0; }
    .tr { top: 0; right: 0; transform: scaleX(-1); }
    .bl { bottom: 0; left: 0; transform: scaleY(-1); }
    .br { bottom: 0; right: 0; transform: scale(-1); }

    .flag-wrap { position: absolute; top: 12px; width: 35px; height: 50px; z-index: 10; }
    .flag-l { left: 12px; } .flag-r { right: 12px; transform: scaleX(-1); }
    .pole { width: 3px; height: 100%; background: linear-gradient(to right, #444, #888, #333); position: absolute; left: 0; }
    .dhwaj { position: absolute; left: 3px; width: 30px; height: 22px; fill: #ff8c00; transform-origin: left center; }

    .p-om { font-size: 40px; color: #cc0000; font-weight: 900; text-align: center; margin-bottom: 2px; line-height: 1; }

    .p-date-block {
        text-align: center; margin: 8px 0 12px;
    }
    .p-day-name { font-size: 22px; font-weight: 900; color: #002266; }
    .p-date-text { font-size: 15px; font-weight: 700; color: #5a4408; margin-top: 2px; }
    .p-shakha { font-size: 14px; font-weight: 900; color: #880E4F; margin-top: 5px; }

    .p-divider { width: 80%; height: 2px; background: linear-gradient(to right, transparent, #bf953f, transparent); margin: 10px auto; }

    .p-title-row {
        display: flex; align-items: center; justify-content: center; gap: 12px; margin: 8px 0;
    }
    .p-wing { width: 30px; height: 30px; fill: #cc0000; }
    .p-main-title {
        font-size: clamp(28px, 7vw, 42px); font-weight: 900; color: #002266; margin: 0;
        text-shadow: 2px 2px 0 #fff, -1px -1px 0 #fff, 1px -1px 0 #fff, -1px 1px 0 #fff;
    }

    .p-section {
        margin: 14px 0 6px; padding: 0;
    }
    .p-section-title {
        font-size: 16px; font-weight: 900; color: #610000;
        padding: 4px 14px; background: rgba(97,0,0,0.08);
        border-radius: 20px 4px 20px 4px; display: inline-block; margin-bottom: 8px;
        border-left: 3px solid #cc0000;
    }

    .p-row {
        display: flex; justify-content: space-between; align-items: flex-start;
        padding: 4px 0; border-bottom: 1px dashed rgba(139,107,13,0.2);
        font-size: 14px; color: #333; line-height: 1.5;
    }
    .p-row:last-child { border-bottom: none; }
    .p-label { font-weight: 800; color: #5a4408; min-width: 100px; flex-shrink: 0; }
    .p-value { text-align: right; font-weight: 600; color: #002266; flex: 1; }

    .p-muhurt-grid {
        display: grid; grid-template-columns: 1fr 1fr; gap: 6px 14px;
    }
    .p-muhurt-item {
        font-size: 13px; padding: 5px 8px; border-radius: 8px;
        background: rgba(0,100,0,0.05); border: 1px solid rgba(0,100,0,0.1);
    }
    .p-muhurt-name { font-weight: 800; color: #1B5E20; font-size: 12px; }
    .p-muhurt-time { color: #002266; font-weight: 600; }

    .p-vrat-box {
        background: #FFF3E0; border: 1px solid #FFB74D; border-radius: 10px;
        padding: 8px 12px; text-align: center; font-weight: 700; color: #E65100; margin-top: 10px;
    }

    .p-footer {
        text-align: center; margin-top: 12px; padding-top: 8px;
        border-top: 1px solid rgba(139,107,13,0.3);
    }
    .p-footer-text { font-size: 12px; color: #8b6b0d; font-weight: 600; }

    .p-ornament { margin-top: 12px; width: 120px; height: 12px; fill: #bf953f; display: block; margin-left: auto; margin-right: auto; }

    /* Controls */
    .panchang-controls {
        display: flex; gap: 10px; align-items: center; justify-content: center; flex-wrap: wrap;
        margin-bottom: 20px;
    }
    .panchang-controls input[type="date"] {
        padding: 10px 14px; background: rgba(15, 15, 20, 0.6); border: 1px solid var(--border-light);
        border-radius: 10px; color: var(--text-primary); font-size: 1rem;
    }

    /* Loading Spinner */
    .panchang-loading {
        text-align: center; padding: 60px 20px; color: var(--text-muted);
    }
    .panchang-loading .spinner {
        width: 50px; height: 50px; border: 4px solid rgba(233,30,99,0.1);
        border-top: 4px solid #E91E63; border-radius: 50%;
        animation: spin 1s linear infinite; margin: 0 auto 20px;
    }
    @keyframes spin { to { transform: rotate(360deg); } }

    #panchang-error {
        text-align: center; padding: 30px; color: var(--danger);
        display: none;
    }
</style>

<div class="page-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
    <h1>🕉️ दैनिक पंचांग</h1>
</div>

<div class="panchang-controls">
    <div class="form-group" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap; justify-content: center;">
        <input type="date" id="panchang-date" value="<?php echo $selectedDate; ?>">
        
        <select id="panchang-provider" style="padding: 10px 14px; background: rgba(15, 15, 20, 0.6); border: 1px solid var(--border-light); border-radius: 10px; color: var(--text-primary); font-size: 1rem; outline: none;">
            <option value="all">🔄 All (Cross-check)</option>
            <option value="gemini">♊ Google Gemini</option>
            <option value="groq">⚡ Groq (Llama 3.1)</option>
            <option value="openai">🤖 OpenAI (ChatGPT)</option>
        </select>

        <select id="panchang-model" style="padding: 10px 14px; background: rgba(15, 15, 20, 0.6); border: 1px solid var(--border-light); border-radius: 10px; color: var(--text-primary); font-size: 1rem; outline: none; display:none;">
            <option value="flash">Flash</option>
        </select>

        <button id="btn-fetch" class="btn btn-primary" onclick="loadPanchang(false)" style="padding: 10px 20px;">✨ पंचांग प्राप्त करें</button>
        <button id="btn-refetch" class="btn btn-outline" onclick="loadPanchang(true)" style="padding: 10px 20px; border-color: var(--primary); color: var(--primary);">🔄 दोबारा प्राप्त करें (Refetch)</button>
    </div>
</div>

<div class="share-actions" style="justify-content: center; margin-bottom: 15px; gap: 16px; display: flex;">
    <button id="btn-download" class="btn btn-success" disabled>⬇️ डाउनलोड (JPG)</button>
    <button id="btn-share" class="btn btn-whatsapp" disabled>📱 व्हाट्सएप शेयर</button>
</div>

<!-- Loading -->
<div id="panchang-loading" class="panchang-loading" style="display: none;">
    <div class="spinner"></div>
    <div>🕉️ पंचांग लोड हो रहा है... कृपया प्रतीक्षा करें</div>
</div>

<!-- Error -->
<div id="panchang-error"></div>

<!-- Initial Placeholder -->
<div id="panchang-placeholder" style="text-align: center; padding: 40px 20px; border: 1px dashed var(--border-light); border-radius: 12px; margin: 20px auto; max-width: 550px; background: rgba(255,255,255,0.02);">
    <div style="font-size: 3rem; margin-bottom: 15px;">🕉️</div>
    <h3 style="color: var(--text-primary);">दैनिक पंचांग देखने के लिए तैयार</h3>
    <p style="color: var(--text-muted);">कृपया तारीख चुनें और "पंचांग प्राप्त करें" बटन पर क्लिक करें।</p>
</div>

<!-- Capture Area -->
<div style="overflow-x: auto; padding-bottom: 40px; text-align: center;">
    <div id="capture-area" class="panchang-capture-container" style="display: none;">
        <div class="panchang-capture-inner">
            <!-- Corner SVGs -->
            <svg class="corner-svg tl" viewBox="0 0 100 100"><path d="M0,0 Q50,0 50,50 Q0,50 0,0 M10,10 Q40,10 40,40 Q10,40 10,10 M0,20 Q20,20 20,40 M20,0 Q20,20 40,20" /></svg>
            <svg class="corner-svg tr" viewBox="0 0 100 100"><path d="M0,0 Q50,0 50,50 Q0,50 0,0 M10,10 Q40,10 40,40 Q10,40 10,10 M0,20 Q20,20 20,40 M20,0 Q20,20 40,20" /></svg>
            <svg class="corner-svg bl" viewBox="0 0 100 100"><path d="M0,0 Q50,0 50,50 Q0,50 0,0 M10,10 Q40,10 40,40 Q10,40 10,10 M0,20 Q20,20 20,40 M20,0 Q20,20 40,20" /></svg>
            <svg class="corner-svg br" viewBox="0 0 100 100"><path d="M0,0 Q50,0 50,50 Q0,50 0,0 M10,10 Q40,10 40,40 Q10,40 10,10 M0,20 Q20,20 20,40 M20,0 Q20,20 40,20" /></svg>

            <!-- Flags -->
            <div class="flag-wrap flag-l"><div class="pole"></div><svg class="dhwaj" viewBox="0 0 100 80"><path d="M0,0 L100,25 L20,40 L100,55 L0,80 Z" /></svg></div>
            <div class="flag-wrap flag-r"><div class="pole"></div><svg class="dhwaj" viewBox="0 0 100 80"><path d="M0,0 L100,25 L20,40 L100,55 L0,80 Z" /></svg></div>

            <!-- OM -->
            <div class="p-om">॥ ॐ ॥</div>

            <!-- Title -->
            <div class="p-title-row">
                <svg class="p-wing" viewBox="0 0 100 100"><path d="M100,50 Q70,20 40,50 Q70,80 100,50 M80,50 Q60,35 40,50 Q60,65 80,50 M40,50 Q20,40 0,50 Q20,60 40,50" /></svg>
                <div style="display: flex; flex-direction: column; align-items: center;">
                    <h1 class="p-main-title">दैनिक पंचांग</h1>
                    <div style="font-family: 'Noto Serif Devanagari', serif; font-size: 1.1rem; color: #cc0000; font-weight: 700; margin-top: -5px;">
                        🚩 <?php echo htmlspecialchars($shakhaCity); ?> 🚩
                    </div>
                </div>
                <svg class="p-wing" style="transform: scaleX(-1)" viewBox="0 0 100 100"><path d="M100,50 Q70,20 40,50 Q70,80 100,50 M80,50 Q60,35 40,50 Q60,65 80,50 M40,50 Q20,40 0,50 Q20,60 40,50" /></svg>
            </div>

            <!-- Date Block -->
            <div class="p-date-block">
                <div class="p-day-name" id="p-day"></div>
                <div class="p-date-text" id="p-date-text"></div>
            </div>

            <div class="p-divider"></div>

            <!-- Section: Samvat & Tithi -->
            <div class="p-section">
                <div class="p-section-title">📅 संवत् एवं तिथि</div>
                <div class="p-row"><span class="p-label">विक्रम संवत्</span><span class="p-value" id="p-vikram">—</span></div>
                <div class="p-row"><span class="p-label">माह</span><span class="p-value" id="p-maah">—</span></div>
                <div class="p-row"><span class="p-label">पक्ष</span><span class="p-value" id="p-paksha">—</span></div>
                <div class="p-row"><span class="p-label">तिथि</span><span class="p-value" id="p-tithi">—</span></div>
                <div class="p-row"><span class="p-label">नक्षत्र</span><span class="p-value" id="p-nakshatra">—</span></div>
                <div class="p-row"><span class="p-label">योग</span><span class="p-value" id="p-yoga">—</span></div>
                <div class="p-row"><span class="p-label">करण</span><span class="p-value" id="p-karana">—</span></div>
                <div class="p-row"><span class="p-label">राहुकाल</span><span class="p-value" id="p-rahukaal" style="color: #cc0000;">—</span></div>
                <div id="p-vishesh-row" class="p-row" style="display: none; background: rgba(204,0,0,0.05);"><span class="p-label" style="color: #cc0000;">विशेष</span><span class="p-value" id="p-vishesh" style="color: #cc0000; font-style: italic;">—</span></div>
            </div>

            <div class="p-divider"></div>

            <!-- Section: Surya Chandra -->
            <div class="p-section">
                <div class="p-section-title">☀️ सूर्य-चंद्र</div>
                <div class="p-row"><span class="p-label">🌅 सूर्योदय</span><span class="p-value" id="p-surya-udaya">—</span></div>
                <div class="p-row"><span class="p-label">🌇 सूर्यास्त</span><span class="p-value" id="p-surya-asta">—</span></div>
                <div class="p-row"><span class="p-label">🌙 चन्द्रोदय</span><span class="p-value" id="p-chandra-udaya">—</span></div>
                <div class="p-row"><span class="p-label">🌑 चंद्रास्त</span><span class="p-value" id="p-chandra-asta">—</span></div>
                <div class="p-row"><span class="p-label">♑ चंद्र राशि</span><span class="p-value" id="p-chandra-rashi">—</span></div>
            </div>

            <div class="p-divider"></div>

            <!-- Section: Shubh Muhurt -->
            <div class="p-section">
                <div class="p-section-title">🌟 शुभ मुहूर्त</div>
                <div class="p-muhurt-grid" id="p-muhurt-grid">
                    <!-- Filled by JS -->
                </div>
            </div>

            <!-- Vrat / Festival -->
            <div id="p-vrat-wrap" style="display: none;">
                <div class="p-vrat-box" id="p-vrat">—</div>
            </div>

            <!-- Footer -->
            <svg class="p-ornament" viewBox="0 0 200 20"><path d="M0,10 Q100,0 200,10" fill="none" stroke="currentColor" stroke-width="2" /><circle cx="100" cy="10" r="4" fill="#cc0000" /></svg>
            <div class="p-footer">
                <div class="p-shakha" style="margin-top: 0; font-size: 11px; opacity: 0.6; font-weight: normal; letter-spacing: 0.5px;">🚩 <?php echo htmlspecialchars($shakhaName); ?> 🚩</div>
                <div style="font-size: 8px; opacity: 0.4; margin-top: 5px; font-weight: normal; line-height: 1.2;">
                    यह पंचांग AI द्वारा निर्मित है। महत्वपूर्ण निर्णयों के लिए कृपया अपने ज्योतिषाचार्य से परामर्श लें।
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const hindiDays = ['रविवार', 'सोमवार', 'मंगलवार', 'बुधवार', 'गुरुवार', 'शुक्रवार', 'शनिवार'];
const hindiMonths = ['जनवरी', 'फ़रवरी', 'मार्च', 'अप्रैल', 'मई', 'जून', 'जुलाई', 'अगस्त', 'सितंबर', 'अक्टूबर', 'नवंबर', 'दिसंबर'];

function formatHindiDate(dateStr) {
    const d = new Date(dateStr);
    return `${d.getDate()} ${hindiMonths[d.getMonth()]} ${d.getFullYear()}, ${hindiDays[d.getDay()]}`;
}

async function loadPanchang(force = false) {
    const date = document.getElementById('panchang-date').value;
    const provider = document.getElementById('panchang-provider').value;
    if (!date) return alert('कृपया तारीख चुनें');

    document.getElementById('panchang-loading').style.display = 'block';
    document.getElementById('capture-area').style.display = 'none';
    document.getElementById('panchang-placeholder').style.display = 'none';
    document.getElementById('panchang-error').style.display = 'none';
    document.getElementById('btn-download').disabled = true;
    document.getElementById('btn-share').disabled = true;

    const btnFetch = document.getElementById('btn-fetch');
    const btnRefetch = document.getElementById('btn-refetch');
    
    if (force) {
        btnRefetch.innerHTML = '⏳ रिफ्रेश हो रहा है...';
    } else {
        btnFetch.innerHTML = '⏳ लोड हो रहा है...';
    }
    btnFetch.disabled = true;
    btnRefetch.disabled = true;

    try {
        const url = `../api/fetch_panchang_ai.php?date=${date}&provider=${provider}${force ? '&force=true' : ''}`;
        const res = await fetch(url);
        const data = await res.json();

        if (!data.success) {
            const err = new Error(data.message || 'API Error');
            err.debug_raw = data.debug_raw;
            throw err;
        }

        populateCard(data, date);
        document.getElementById('capture-area').style.display = 'block';
        document.getElementById('btn-download').disabled = false;
        document.getElementById('btn-share').disabled = false;

    } catch (e) {
        console.error(e);
        const errDiv = document.getElementById('panchang-error');
        let debugInfo = '';
        if (e.debug_raw) {
            debugInfo = `<details style="margin-top:10px; font-size:11px; color: #888; text-align:left;"><summary>Debug Info</summary><pre style="white-space: pre-wrap;">${e.debug_raw}</pre></details>`;
        }
        errDiv.innerHTML = `<div class="card" style="padding: 30px; text-align: center;"><p>❌ त्रुटि: ${e.message}</p><p style="color: var(--text-muted);">कृपया दोबारा प्रयास करें।</p>${debugInfo}</div>`;
        errDiv.style.display = 'block';
    }

    document.getElementById('panchang-loading').style.display = 'none';
    btnFetch.innerHTML = '✨ पंचांग प्राप्त करें';
    btnRefetch.innerHTML = '🔄 दोबारा प्राप्त करें (Refetch)';
    btnFetch.disabled = false;
    btnRefetch.disabled = false;
}

function populateCard(data, date) {
    const p = data.panchang;

    // Date
    document.getElementById('p-day').textContent = data.day || formatHindiDate(date).split(',')[1]?.trim();
    document.getElementById('p-date-text').textContent = data.formatted_date || formatHindiDate(date);

    // Surya-Chandra
    document.getElementById('p-surya-udaya').textContent = p.surya?.udaya || '—';
    document.getElementById('p-surya-asta').textContent = p.surya?.asta || '—';
    document.getElementById('p-chandra-udaya').textContent = p.chandra?.udaya || '—';
    document.getElementById('p-chandra-asta').textContent = p.chandra?.asta || '—';
    document.getElementById('p-chandra-rashi').textContent = p.chandra?.rashi || '—';

    // Samvat & Tithi
    document.getElementById('p-vikram').textContent = p.samvat?.vikram || '—';
    
    let maahText = '—';
    if (p.maah) {
        let purnimant = p.maah.purnimant || '';
        let amant = p.maah.amant || '';
        
        if (purnimant && amant) {
            if (purnimant === amant) {
                maahText = purnimant;
            } else {
                maahText = `${purnimant} (पूर्णिमान्त) / ${amant} (अमान्त)`;
            }
        } else {
            maahText = purnimant || amant || '—';
        }
    }
    document.getElementById('p-maah').textContent = maahText;
    document.getElementById('p-paksha').textContent = p.paksha || '—';
    document.getElementById('p-tithi').textContent = p.tithi || '—';
    document.getElementById('p-nakshatra').textContent = p.nakshatra || '—';
    document.getElementById('p-yoga').textContent = p.yoga || '—';
    document.getElementById('p-karana').textContent = p.karana || '—';
    document.getElementById('p-rahukaal').textContent = p.rahukaal || '—';

    // Vishesh
    const vRow = document.getElementById('p-vishesh-row');
    const vVal = document.getElementById('p-vishesh');
    if (p.vishesh && p.vishesh !== 'null') {
        vRow.style.display = 'flex';
        vVal.textContent = p.vishesh;
    } else {
        vRow.style.display = 'none';
    }

    // Shubh Muhurt
    const muhurtGrid = document.getElementById('p-muhurt-grid');
    muhurtGrid.innerHTML = '';
    const sm = p.shubh_muhurt || {};
    const muhurtMap = {
        'abhijit': 'अभिजीत',
        'amrit_kaal': 'अमृत काल',
        'vijay': 'विजय मुहूर्त',
        'ravi_yoga': 'रवि योग',
        'sarvarth_siddhi': 'सर्वार्थ सिद्धि'
    };
    for (const [key, label] of Object.entries(muhurtMap)) {
        const val = sm[key];
        if (val && val !== 'N/A' && val !== 'null') {
            muhurtGrid.innerHTML += `
                <div class="p-muhurt-item">
                    <div class="p-muhurt-name">🌟 ${label}</div>
                    <div class="p-muhurt-time">${val}</div>
                </div>`;
        }
    }

    // Vrat / Festival
    const vratWrap = document.getElementById('p-vrat-wrap');
    const vratEl = document.getElementById('p-vrat');
    if (p.vrat_tyohar && p.vrat_tyohar !== 'null') {
        vratWrap.style.display = 'block';
        vratEl.textContent = '🌺 ' + p.vrat_tyohar;
    } else {
        vratWrap.style.display = 'none';
    }
}

// Download
document.getElementById('btn-download').addEventListener('click', async () => {
    const btn = document.getElementById('btn-download');
    btn.innerHTML = '⏳...'; btn.disabled = true;
    try {
        const captureArea = document.getElementById('capture-area');
        const canvas = await html2canvas(captureArea, {
            scale: 2, 
            useCORS: true, 
            allowTaint: true,
            backgroundColor: '#FFF9E3'
        });
        if (window.FlutterShareChannel) {
            const b64 = canvas.toDataURL('image/jpeg', 0.95);
            window.FlutterShareChannel.postMessage(JSON.stringify({ image: b64, text: 'दैनिक पंचांग', filename: 'panchang.jpg' }));
        } else {
            const a = document.createElement('a');
            a.href = canvas.toDataURL('image/jpeg', 0.95);
            a.download = 'panchang_' + document.getElementById('panchang-date').value + '.jpg';
            a.click();
        }
    } catch(e) { 
        console.error(e); 
        alert('डाउनलोड में त्रुटि: ' + e.message);
    }
    btn.innerHTML = '⬇️ डाउनलोड (JPG)'; btn.disabled = false;
});

// WhatsApp Share
document.getElementById('btn-share').addEventListener('click', async () => {
    const btn = document.getElementById('btn-share');
    btn.innerHTML = '⏳...'; btn.disabled = true;
    try {
        const captureArea = document.getElementById('capture-area');
        const canvas = await html2canvas(captureArea, {
            scale: 2, 
            useCORS: true, 
            allowTaint: true,
            backgroundColor: '#FFF9E3'
        });
        const blob = await new Promise(r => canvas.toBlob(r, 'image/jpeg', 0.95));
        const file = new File([blob], 'panchang.jpg', { type: 'image/jpeg' });
        const textStr = '🕉️ दैनिक पंचांग — ' + document.getElementById('p-date-text').textContent;

        if (window.FlutterShareChannel) {
            const b64 = canvas.toDataURL('image/jpeg', 0.95);
            window.FlutterShareChannel.postMessage(JSON.stringify({ image: b64, text: textStr, filename: 'panchang.jpg' }));
            btn.innerHTML = '📱 व्हाट्सएप शेयर'; btn.disabled = false;
            return;
        }
        if (navigator.share && navigator.canShare && navigator.canShare({ files: [file] })) {
            await navigator.share({ title: 'दैनिक पंचांग', text: textStr, files: [file] });
        } else {
            const a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = 'panchang.jpg'; a.click();
            window.open('https://wa.me/?text=' + encodeURIComponent(textStr), '_blank');
        }
    } catch(e) {
        if (e.name !== 'AbortError') {
            console.error(e);
            alert('शेयर करने में त्रुटि: ' + e.message);
        }
    }
    btn.innerHTML = '📱 व्हाट्सएप शेयर'; btn.disabled = false;
});

// Auto-load for today removed to avoid API demand issues and as per user request
window.addEventListener('DOMContentLoaded', () => {
    // Show a placeholder or instructions if needed
    console.log('Panchang page loaded. Click the button to fetch data.');
});
</script>

<?php require_once '../includes/footer.php'; ?>
