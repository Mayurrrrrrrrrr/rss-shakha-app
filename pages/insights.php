<?php
require_once '../includes/auth.php';
/**
 * AI Insights Page — शाखा अंतर्दृष्टि
 * Uses Gemini AI to analyze shakha data over a selected date range
 */
$pageTitle = 'AI अंतर्दृष्टि (Insights)';
require_once '../includes/header.php';
require_once '../config/db.php';
requireLogin();

if (isSwayamsevak()) {
    header('Location: swayamsevak_dashboard.php');
    exit;
}

$shakhaId = getCurrentShakhaId();
$stmtShakha = $pdo->prepare("SELECT name FROM shakhas WHERE id = ?");
$stmtShakha->execute([$shakhaId]);
$shakhaName = $stmtShakha->fetchColumn() ?: 'शाखा';

// Default date range: last 30 days
$toDate = date('Y-m-d');
$fromDate = date('Y-m-d', strtotime('-30 days'));
?>

<style>
    .insights-header {
        text-align: center;
        margin-bottom: 30px;
    }
    .insights-header h1 {
        font-size: 1.8rem;
        background: linear-gradient(135deg, #FF6F00, #FFB74D);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        margin-bottom: 6px;
    }
    .insights-header p {
        color: #888;
        font-size: 0.95rem;
    }

    /* Date range card */
    .date-range-card {
        background: rgba(30,30,45,0.85);
        backdrop-filter: blur(15px);
        border: 1px solid rgba(255,107,0,0.2);
        border-radius: 16px;
        padding: 24px;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        gap: 16px;
        flex-wrap: wrap;
        justify-content: center;
    }
    .date-range-card label {
        color: #BBB;
        font-size: 0.85rem;
        margin-right: 4px;
    }
    .date-range-card input[type="date"] {
        padding: 10px 14px;
        background: rgba(255,255,255,0.06);
        border: 1px solid rgba(255,255,255,0.12);
        border-radius: 10px;
        color: #FFF;
        font-size: 0.95rem;
        font-family: inherit;
    }
    .date-range-card input[type="date"]:focus {
        outline: none;
        border-color: #FF9800;
        box-shadow: 0 0 0 2px rgba(255,152,0,0.15);
    }
    .btn-generate {
        padding: 12px 28px;
        background: linear-gradient(135deg, #FF6F00, #E65100);
        color: #FFF;
        border: none;
        border-radius: 12px;
        font-size: 1rem;
        font-weight: 700;
        cursor: pointer;
        font-family: inherit;
        box-shadow: 0 4px 15px rgba(230,81,0,0.35);
        transition: all 0.3s;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .btn-generate:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(230,81,0,0.5);
    }
    .btn-generate:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    /* Quick range buttons */
    .quick-ranges {
        display: flex;
        gap: 6px;
        flex-wrap: wrap;
        justify-content: center;
        margin-top: 8px;
        width: 100%;
    }
    .quick-range-btn {
        padding: 5px 12px;
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.1);
        color: #BBB;
        border-radius: 20px;
        cursor: pointer;
        font-size: 0.78rem;
        font-family: inherit;
        transition: all 0.2s;
    }
    .quick-range-btn:hover {
        background: rgba(255,152,0,0.15);
        border-color: rgba(255,152,0,0.3);
        color: #FFB74D;
    }

    /* Loading shimmer */
    .insights-loading {
        display: none;
        text-align: center;
        padding: 40px 20px;
    }
    .insights-loading.active { display: block; }
    .shimmer-container {
        max-width: 700px;
        margin: 0 auto;
    }
    .shimmer-line {
        height: 16px;
        background: linear-gradient(90deg, rgba(255,255,255,0.04) 25%, rgba(255,152,0,0.08) 50%, rgba(255,255,255,0.04) 75%);
        background-size: 200% 100%;
        animation: shimmer 1.5s infinite;
        border-radius: 8px;
        margin-bottom: 12px;
    }
    .shimmer-line:nth-child(1) { width: 85%; }
    .shimmer-line:nth-child(2) { width: 65%; }
    .shimmer-line:nth-child(3) { width: 90%; }
    .shimmer-line:nth-child(4) { width: 50%; height: 20px; margin-top: 24px; }
    .shimmer-line:nth-child(5) { width: 75%; }
    .shimmer-line:nth-child(6) { width: 80%; }
    .shimmer-line:nth-child(7) { width: 60%; }
    .shimmer-line:nth-child(8) { width: 40%; height: 20px; margin-top: 24px; }
    .shimmer-line:nth-child(9) { width: 88%; }
    .shimmer-line:nth-child(10) { width: 70%; }
    @keyframes shimmer {
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }
    .loading-text {
        color: #FFB74D;
        font-size: 1rem;
        font-weight: 600;
        margin-bottom: 24px;
        animation: pulse 2s infinite;
    }
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }

    /* Results card */
    .insights-result {
        display: none;
    }
    .insights-result.active { display: block; }

    .meta-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
        gap: 12px;
        margin-bottom: 20px;
    }
    .meta-stat {
        background: rgba(255,255,255,0.04);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 12px;
        padding: 14px;
        text-align: center;
    }
    .meta-stat .num {
        font-size: 1.5rem;
        font-weight: 800;
        color: #FFB74D;
        line-height: 1;
    }
    .meta-stat .label {
        font-size: 0.72rem;
        color: #888;
        margin-top: 4px;
    }

    .ai-response-card {
        background: rgba(30,30,45,0.9);
        backdrop-filter: blur(15px);
        border: 1px solid rgba(255,107,0,0.15);
        border-radius: 16px;
        padding: 28px;
        position: relative;
        overflow: hidden;
    }
    .ai-response-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, #FF6F00, #FFB74D, #FF6F00);
    }
    .ai-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: rgba(255,107,0,0.1);
        border: 1px solid rgba(255,107,0,0.3);
        border-radius: 20px;
        padding: 4px 14px;
        font-size: 0.78rem;
        color: #FFB74D;
        font-weight: 600;
        margin-bottom: 16px;
    }
    .ai-response-body {
        color: #DDD;
        line-height: 1.75;
        font-size: 0.95rem;
    }
    .ai-response-body h2, .ai-response-body h3 {
        color: #FFB74D;
        margin-top: 24px;
        margin-bottom: 8px;
        font-size: 1.1rem;
    }
    .ai-response-body strong {
        color: #FFF;
    }
    .ai-response-body ul {
        padding-left: 20px;
        margin: 8px 0;
    }
    .ai-response-body li {
        margin-bottom: 6px;
    }
    .ai-response-body p {
        margin-bottom: 10px;
    }
    .ai-response-body hr {
        border: none;
        border-top: 1px solid rgba(255,255,255,0.08);
        margin: 16px 0;
    }

    /* Error state */
    .insights-error {
        display: none;
        text-align: center;
        padding: 30px;
        background: rgba(244,67,54,0.08);
        border: 1px solid rgba(244,67,54,0.25);
        border-radius: 16px;
    }
    .insights-error.active { display: block; }
    .insights-error .error-icon {
        font-size: 48px;
        margin-bottom: 12px;
    }
    .insights-error .error-text {
        color: #FF8A80;
        font-size: 0.95rem;
    }

    /* Print / share section */
    .insights-actions {
        display: flex;
        gap: 10px;
        justify-content: center;
        margin-top: 20px;
        flex-wrap: wrap;
    }
    .insights-actions button {
        padding: 10px 20px;
        border-radius: 10px;
        border: 1px solid rgba(255,255,255,0.12);
        background: rgba(255,255,255,0.05);
        color: #CCC;
        cursor: pointer;
        font-family: inherit;
        font-size: 0.85rem;
        transition: all 0.2s;
    }
    .insights-actions button:hover {
        background: rgba(255,152,0,0.12);
        border-color: rgba(255,152,0,0.3);
        color: #FFB74D;
    }

    @media (max-width: 600px) {
        .date-range-card {
            flex-direction: column;
            gap: 12px;
        }
        .meta-stats {
            grid-template-columns: repeat(3, 1fr);
        }
        .ai-response-card {
            padding: 18px;
        }
    }
</style>

<div class="insights-header">
    <h1>🧠 AI अंतर्दृष्टि (Insights)</h1>
    <p><?php echo htmlspecialchars($shakhaName); ?> — Gemini AI द्वारा शाखा गतिविधि विश्लेषण</p>
</div>

<!-- Date Range Selection -->
<div class="date-range-card">
    <div>
        <label for="from-date">📅 से:</label>
        <input type="date" id="from-date" value="<?php echo $fromDate; ?>">
    </div>
    <div>
        <label for="to-date">📅 तक:</label>
        <input type="date" id="to-date" value="<?php echo $toDate; ?>">
    </div>
    <button class="btn-generate" id="btn-generate" onclick="generateInsights()">
        <span id="btn-text">🧠 Insights बनाएं</span>
    </button>
    <div class="quick-ranges">
        <button class="quick-range-btn" onclick="setRange(7)">पिछले 7 दिन</button>
        <button class="quick-range-btn" onclick="setRange(15)">पिछले 15 दिन</button>
        <button class="quick-range-btn" onclick="setRange(30)">पिछले 30 दिन</button>
        <button class="quick-range-btn" onclick="setRange(90)">पिछले 3 महीने</button>
        <button class="quick-range-btn" onclick="setRange(180)">पिछले 6 महीने</button>
    </div>
</div>

<!-- Loading State -->
<div class="insights-loading" id="loading-state">
    <div class="loading-text">🧠 Gemini AI विश्लेषण कर रहा है...</div>
    <div class="shimmer-container">
        <div class="shimmer-line"></div>
        <div class="shimmer-line"></div>
        <div class="shimmer-line"></div>
        <div class="shimmer-line"></div>
        <div class="shimmer-line"></div>
        <div class="shimmer-line"></div>
        <div class="shimmer-line"></div>
        <div class="shimmer-line"></div>
        <div class="shimmer-line"></div>
        <div class="shimmer-line"></div>
    </div>
</div>

<!-- Error State -->
<div class="insights-error" id="error-state">
    <div class="error-icon">⚠️</div>
    <div class="error-text" id="error-text">कोई त्रुटि हुई।</div>
    <button onclick="generateInsights()" style="margin-top: 16px; padding: 10px 24px; background: rgba(255,107,0,0.2); border: 1px solid #FF6F00; color: #FFB74D; border-radius: 10px; cursor: pointer; font-family: inherit;">🔄 पुनः प्रयास करें</button>
</div>

<!-- Results -->
<div class="insights-result" id="result-state">
    <!-- Meta Stats -->
    <div class="meta-stats" id="meta-stats"></div>

    <!-- AI Response -->
    <div class="ai-response-card">
        <div class="ai-badge">✨ Gemini AI — शाखा विश्लेषण</div>
        <div class="ai-response-body" id="ai-response-body"></div>
    </div>

    <div class="insights-actions">
        <button onclick="copyInsights()">📋 कॉपी करें</button>
        <button onclick="shareInsights()">📱 व्हाट्सएप शेयर</button>
        <button onclick="window.print()">🖨️ प्रिंट</button>
    </div>
</div>

<script>
    const fromInput = document.getElementById('from-date');
    const toInput = document.getElementById('to-date');
    const btnGenerate = document.getElementById('btn-generate');
    const btnText = document.getElementById('btn-text');
    const loadingState = document.getElementById('loading-state');
    const errorState = document.getElementById('error-state');
    const resultState = document.getElementById('result-state');

    let lastInsightsText = '';

    function setRange(days) {
        const to = new Date();
        const from = new Date();
        from.setDate(from.getDate() - days);
        fromInput.value = from.toISOString().split('T')[0];
        toInput.value = to.toISOString().split('T')[0];
    }

    function showState(state) {
        loadingState.classList.toggle('active', state === 'loading');
        errorState.classList.toggle('active', state === 'error');
        resultState.classList.toggle('active', state === 'result');
    }

    async function generateInsights() {
        const from = fromInput.value;
        const to = toInput.value;
        if (!from || !to) {
            alert('कृपया दोनों तारीख चुनें।');
            return;
        }

        showState('loading');
        btnGenerate.disabled = true;
        btnText.textContent = '⏳ विश्लेषण जारी...';

        try {
            const response = await fetch(`../api/ai_insights.php?from=${from}&to=${to}`);
            const data = await response.json();

            if (data.success) {
                renderResults(data);
                showState('result');
            } else {
                document.getElementById('error-text').textContent = data.message || 'अज्ञात त्रुटि';
                showState('error');
            }
        } catch (err) {
            console.error(err);
            document.getElementById('error-text').textContent = 'नेटवर्क त्रुटि। कृपया इंटरनेट कनेक्शन जांचें।';
            showState('error');
        }

        btnGenerate.disabled = false;
        btnText.textContent = '🧠 Insights बनाएं';
    }

    function renderResults(data) {
        // Meta stats
        const m = data.meta;
        const statsHtml = `
            <div class="meta-stat"><div class="num">${m.records_analyzed}</div><div class="label">दिन विश्लेषित</div></div>
            <div class="meta-stat"><div class="num">${m.swayamsevaks_tracked}</div><div class="label">स्वयंसेवक</div></div>
            <div class="meta-stat"><div class="num">${m.new_members || 0}</div><div class="label">नए सदस्य</div></div>
            <div class="meta-stat"><div class="num">${m.activities_tracked}</div><div class="label">गतिविधियाँ</div></div>
            <div class="meta-stat"><div class="num">${m.schedule_adherence !== null ? m.schedule_adherence + '%' : '—'}</div><div class="label">शेड्यूल पालन</div></div>
            <div class="meta-stat"><div class="num">${m.notices_count}</div><div class="label">सूचनाएं</div></div>
            <div class="meta-stat"><div class="num">${m.subhashits_count}</div><div class="label">सुभाषित</div></div>
            <div class="meta-stat"><div class="num">${m.events_count}</div><div class="label">कार्यक्रम</div></div>
        `;
        document.getElementById('meta-stats').innerHTML = statsHtml;

        // AI response — convert markdown-like to HTML
        lastInsightsText = data.insights;
        const html = markdownToHtml(data.insights);
        document.getElementById('ai-response-body').innerHTML = html;
    }

    function markdownToHtml(text) {
        // Basic markdown to HTML conversion
        let html = text
            // Headers
            .replace(/^### (.+)$/gm, '<h3>$1</h3>')
            .replace(/^## (.+)$/gm, '<h2>$1</h2>')
            .replace(/^# (.+)$/gm, '<h2>$1</h2>')
            // Bold
            .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
            // Horizontal rule
            .replace(/^---$/gm, '<hr>')
            // Line breaks → paragraphs
            .replace(/\n\n/g, '</p><p>')
            // Bullet points
            .replace(/^\* (.+)$/gm, '<li>$1</li>')
            .replace(/^- (.+)$/gm, '<li>$1</li>')
            // Wrap consecutive <li> in <ul>
            .replace(/(<li>.*?<\/li>\n?)+/gs, function(match) {
                return '<ul>' + match + '</ul>';
            })
            // Remaining single newlines 
            .replace(/\n/g, '<br>');

        return '<p>' + html + '</p>';
    }

    function copyInsights() {
        if (!lastInsightsText) return;
        navigator.clipboard.writeText(lastInsightsText).then(() => {
            alert('✅ Insights कॉपी हो गई!');
        }).catch(() => {
            // Fallback
            const ta = document.createElement('textarea');
            ta.value = lastInsightsText;
            document.body.appendChild(ta);
            ta.select();
            document.execCommand('copy');
            document.body.removeChild(ta);
            alert('✅ Insights कॉपी हो गई!');
        });
    }

    function shareInsights() {
        if (!lastInsightsText) return;
        const text = "🧠 शाखा AI Insights\n" + fromInput.value + " से " + toInput.value + "\n\n" + lastInsightsText.substring(0, 3000);
        
        if (navigator.share) {
            navigator.share({ title: 'शाखा Insights', text: text });
        } else {
            window.open('https://wa.me/?text=' + encodeURIComponent(text), '_blank');
        }
    }
</script>

<?php require_once '../includes/footer.php'; ?>
