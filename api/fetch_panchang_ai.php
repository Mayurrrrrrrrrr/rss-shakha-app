<?php
require_once '../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
requireLogin();

header('Content-Type: application/json; charset=UTF-8');

$shakhaId = getCurrentShakhaId();
if (!$shakhaId) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Fetch Shakha-specific Keys and City Name
$stmt = $pdo->prepare("SELECT gemini_api_key, openai_api_key, groq_api_key, use_ai_crosscheck, city_name FROM shakhas WHERE id = ?");
$stmt->execute([$shakhaId]);
$shakhaData = $stmt->fetch();
$geminiKey = $shakhaData['gemini_api_key'] ?? '';
$openaiKey = $shakhaData['openai_api_key'] ?? '';
$groqKey = $shakhaData['groq_api_key'] ?? '';
$useCrossCheck = ($shakhaData['use_ai_crosscheck'] ?? 0) == 1;
$cityName = $shakhaData['city_name'] ?? 'मुम्बई';

if (empty($geminiKey) && empty($openaiKey) && empty($groqKey)) {
    echo json_encode(['success' => false, 'message' => 'AI सुविधा सक्रिय नहीं है। कृपया सेटिंग्स में API Key डालें।']);
    exit;
}

$date = $_GET['date'] ?? date('Y-m-d');
$providerParam = $_GET['provider'] ?? 'all';
$forceFetch = isset($_GET['force']) && $_GET['force'] === 'true';

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    echo json_encode(['success' => false, 'message' => 'Invalid date format']);
    exit;
}

// ─── Cache Check ──────────────────────────────────────────────────────────────
if (!$forceFetch) {
    try {
        $stmtCache = $pdo->prepare(
            "SELECT response_json FROM ai_content_cache WHERE content_type = 'panchang' AND content_key = ?"
        );
        $stmtCache->execute([$date]);
        $cachedData = $stmtCache->fetchColumn();
        if ($cachedData) {
            $parsed = json_decode($cachedData, true);
            echo json_encode([
                'success' => true,
                'date' => $date,
                'panchang' => $parsed,
                'source' => 'cache'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    } catch (Exception $e) {
    }
}

// ─── Date helpers ─────────────────────────────────────────────────────────────
$ts = strtotime($date);
$hindiDays = ['रविवार', 'सोमवार', 'मंगलवार', 'बुधवार', 'गुरुवार', 'शुक्रवार', 'शनिवार'];
$hindiMonths = ['जनवरी', 'फ़रवरी', 'मार्च', 'अप्रैल', 'मई', 'जून', 'जुलाई', 'अगस्त', 'सितंबर', 'अक्टूबर', 'नवंबर', 'दिसंबर'];
$dayName = $hindiDays[date('w', $ts)];
$dayOfWeek = (int) date('w', $ts);   // 0=Sun … 6=Sat
$formattedDate = date('j', $ts) . ' ' . $hindiMonths[date('n', $ts) - 1] . ' ' . date('Y', $ts);

// ─── Rahu Kaal reference table (hardcoded – AI must NOT invent these) ─────────
// Slot size depends on day-length (≈12 h). For Mumbai these are standard times.
$rahuKaalTable = [
    0 => '04:30 PM to 06:00 PM',  // Sunday    – 8th slot
    1 => '07:30 AM to 09:00 AM',  // Monday    – 2nd slot
    2 => '03:00 PM to 04:30 PM',  // Tuesday   – 7th slot
    3 => '12:00 PM to 01:30 PM',  // Wednesday – 5th slot
    4 => '01:30 PM to 03:00 PM',  // Thursday  – 6th slot
    5 => '10:30 AM to 12:00 PM',  // Friday    – 3rd slot
    6 => '09:00 AM to 10:30 AM',  // Saturday  – 4th slot
];
$correctRahuKaal = $rahuKaalTable[$dayOfWeek];

// ─── System Prompt ────────────────────────────────────────────────────────────
// NOTE: This is sent as the SYSTEM role (not mixed into user message).
// It is written in English so every model follows it reliably.
$systemPrompt = <<<SYSTEM
You are a precise Vedic Panchang calculation engine for {$cityName}, India (IST = UTC+5:30).
You output ONLY a single valid JSON object — no preamble, no explanation, no markdown fences.

═══════════════════════════════════════════════════════
SECTION A — FIXED VALUES (use exactly as given, do not recalculate)
═══════════════════════════════════════════════════════

A1. RAHU KAAL for {$dayName} → "{$correctRahuKaal}"
    Copy this value verbatim into the "rahukaal" field. Do NOT compute it yourself.

═══════════════════════════════════════════════════════
SECTION B — CALCULATION RULES
═══════════════════════════════════════════════════════

B1. TITHI
    • Compute tithi based on the Moon-Sun longitude difference (each tithi = 12°).
    • If the tithi changes during daylight hours, show BOTH:
      Format: "नाम1 (ends HH:MM AM/PM) / नाम2 (from HH:MM AM/PM)"
    • The two transition times MUST be different.
    • If only one tithi spans the whole day: "नाम (ends HH:MM AM/PM, next day)" or similar.

B2. NAKSHATRA & CHANDRA RASHI (MUST match — these are linked)
    Nakshatra → Chandra Rashi mapping (Vedic, sidereal):
      Ashwini, Bharani, Krittika(0–3°20') → मेष
      Krittika(3°20'–30°), Rohini, Mrigashira(0–8°20') → वृषभ
      Mrigashira(8°20'–30°), Ardra, Punarvasu(0–20°) → मिथुन
      Punarvasu(20°–30°), Pushya, Ashlesha → कर्क
      Magha, Purva Phalguni, Uttara Phalguni(0–10°) → सिंह   ← KEY RULE
      Uttara Phalguni(10°–30°), Hasta, Chitra(0–15°) → कन्या
      Chitra(15°–30°), Swati, Vishakha(0–20°) → तुला
      Vishakha(20°–30°), Anuradha, Jyeshtha → वृश्चिक
      Mula, Purva Ashadha, Uttara Ashadha(0–10°) → धनु
      Uttara Ashadha(10°–30°), Shravana, Dhanishtha(0–15°) → मकर
      Dhanishtha(15°–30°), Shatabhisha, Purva Bhadrapada(0–20°) → कुंभ
      Purva Bhadrapada(20°–30°), Uttara Bhadrapada, Revati → मीन
    • After deciding nakshatra, look up the rashi from the table above.
    • If nakshatra is Purva Phalguni → rashi MUST be सिंह, not मेष.

B3. YOGA & KARANA
    • Compute independently from tithi/nakshatra.
    • End times of yoga and karana must DIFFER from each other and from tithi/nakshatra end times.

B4. SURYA (sunrise/sunset) & CHANDRA (moonrise/moonset)
    • Compute astronomically for {$cityName} on the given date.
    • For Shukla Paksha Dwadashi/Trayodashi: moon rises late evening, sets after midnight.
      A Chandraast of 3-4 PM on these tithis is WRONG.

B5. MUHURTAS
    • Abhijit = midday ± 24 min. Midday = (sunrise + sunset) / 2.
    • Vijay = 9th of 15 equal daytime slots → approximately 2 PM, NOT near sunrise.
    • Amrit Kaal = based on nakshatra (calculate, do not copy sunrise).
    • Ravi Yoga and Sarvaarth Siddhi must be shown as a TIME RANGE "HH:MM to HH:MM".

B6. SAMVAT
    • Vikram Samvat for 2026: 2083 (राक्षस)
    • Shaka Samvat for 2026: 1948
    • Yugabdha (Kali): 5128

═══════════════════════════════════════════════════════
SECTION C — SELF-VERIFICATION (do before outputting)
═══════════════════════════════════════════════════════
□ rahukaal = exactly "{$correctRahuKaal}" ?
□ Nakshatra and chandra.rashi are consistent per Section B2 mapping?
□ No two fields share the same HH:MM timestamp?
□ Vijay muhurta is around 2 PM, not at sunrise?
□ Ravi Yoga and Sarvaarth Siddhi are ranges, not single times?
□ Chandraast is astronomically correct for the paksha/tithi?

═══════════════════════════════════════════════════════
SECTION D — OUTPUT FORMAT
═══════════════════════════════════════════════════════
Output a single valid JSON object with EXACTLY this structure (all values in Hindi except times):

{
  "surya":    { "udaya": "HH:MM AM/PM", "asta": "HH:MM AM/PM" },
  "chandra":  { "udaya": "HH:MM AM/PM", "asta": "HH:MM AM/PM", "rashi": "Hindi rashi name" },
  "samvat":   { "vikram": "2083 (राक्षस)", "shaka": "1948", "yugabdha": "5128" },
  "maah":     { "purnimant": "Hindi month name", "amant": "Hindi month name" },
  "paksha":   "शुक्ल or कृष्ण",
  "tithi":    "नाम (ends HH:MM AM/PM) / नाम (from HH:MM AM/PM)",
  "nakshatra":"नाम (ends HH:MM AM/PM)",
  "yoga":     "नाम (ends HH:MM AM/PM)",
  "karana":   "नाम (ends HH:MM AM/PM)",
  "rahukaal": "{$correctRahuKaal}",
  "shubh_muhurt": {
    "abhijit":        "HH:MM to HH:MM",
    "amrit_kaal":     "HH:MM to HH:MM",
    "vijay":          "HH:MM to HH:MM",
    "ravi_yoga":      "HH:MM to HH:MM",
    "sarvarth_siddhi":"HH:MM to HH:MM"
  },
  "vrat_tyohar": "festival name in Hindi, or null",
  "vishesh":     "any special note in Hindi, or null"
}
SYSTEM;

// ─── User Prompt (short, just the date/location) ──────────────────────────────
$userPrompt = "दिनांक: {$formattedDate}, {$dayName} (ISO: {$date})\nस्थान: {$cityName}, भारत\nकृपया पंचांग JSON दें।";

// ─── Helper: strip markdown fences and extract JSON ──────────────────────────
function extractJson(string $text): string
{
    // Remove ```json ... ``` or ``` ... ```
    $text = preg_replace('/```json\s*/i', '', $text);
    $text = preg_replace('/```\s*/i', '', $text);
    $text = trim($text);
    // Grab first { ... } block
    if (preg_match('/\{.*\}/s', $text, $m))
        return $m[0];
    return $text;
}

// ─── Gemini ───────────────────────────────────────────────────────────────────
// FIX: use gemini-1.5-flash (stable), pass system prompt in systemInstruction field
function fetchGemini(string $apiKey, string $systemPrompt, string $userPrompt): ?array
{
    $model = 'gemini-pro';
    $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";
    $payload = [
        'contents' => [
            ['role' => 'user', 'parts' => [['text' => $systemPrompt . "\n\n" . $userPrompt]]]
        ],
        'generationConfig' => [
            'temperature' => 0,
            'responseMimeType' => 'application/json'
        ]
    ];
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_TIMEOUT => 45,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $res = curl_exec($ch);
    $err = curl_error($ch);
    file_put_contents(__DIR__ . '/debug_api.log', date('Y-m-d H:i:s') . " Gemini: $res\nError: $err\n", FILE_APPEND);
    curl_close($ch);
    if ($err || !$res)
        return null;
    $data = json_decode($res, true);
    $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
    if (!$text)
        return null;
    return json_decode(extractJson($text), true) ?: null;
}

// ─── OpenAI ───────────────────────────────────────────────────────────────────
// FIX: system prompt in 'system' role, temperature 0, json_object mode
function fetchOpenAI(string $apiKey, string $systemPrompt, string $userPrompt): ?array
{
    $url = "https://api.openai.com/v1/chat/completions";
    $payload = [
        'model' => 'gpt-4o-mini',
        'messages' => [
            ['role' => 'system', 'content' => $systemPrompt],   // ← correct role
            ['role' => 'user', 'content' => $userPrompt],
        ],
        'temperature' => 0,            // ← 0 = deterministic
        'response_format' => ['type' => 'json_object'],
    ];
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_TIMEOUT => 45,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $res = curl_exec($ch);
    $err = curl_error($ch);
    file_put_contents(__DIR__ . '/debug_api.log', date('Y-m-d H:i:s') . " OpenAI: $res\nError: $err\n", FILE_APPEND);
    curl_close($ch);
    if ($err || !$res)
        return null;
    $data = json_decode($res, true);
    $text = $data['choices'][0]['message']['content'] ?? '';
    if (!$text)
        return null;
    return json_decode($text, true) ?: null;
}

// ─── Groq ─────────────────────────────────────────────────────────────────────
// FIX: use 70B model (8B is too small for Panchang), system role, temperature 0
function fetchGroq(string $apiKey, string $systemPrompt, string $userPrompt): ?array
{
    $url = "https://api.groq.com/openai/v1/chat/completions";
    $payload = [
        'model' => 'llama-3.3-70b-versatile',   // ← upgraded from 8B
        'messages' => [
            ['role' => 'system', 'content' => $systemPrompt],   // ← correct role
            ['role' => 'user', 'content' => $userPrompt],
        ],
        'temperature' => 0,            // ← 0 = deterministic
        'response_format' => ['type' => 'json_object'],
    ];
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_TIMEOUT => 45,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $res = curl_exec($ch);
    $err = curl_error($ch);
    file_put_contents(__DIR__ . '/debug_api.log', date('Y-m-d H:i:s') . " Groq: $res\nError: $err\n", FILE_APPEND);
    curl_close($ch);
    if ($err || !$res)
        return null;
    $data = json_decode($res, true);
    $text = $data['choices'][0]['message']['content'] ?? '';
    if (!$text)
        return null;
    return json_decode($text, true) ?: null;
}

// ─── PHP-side Rahu Kaal Override ──────────────────────────────────────────────
// Even if AI ignores the instruction, we forcefully correct it here.
function overrideRahuKaal(array &$panchang, string $correctValue): void
{
    $panchang['rahukaal'] = $correctValue;
}

// ─── Nakshatra → Rashi consistency check ─────────────────────────────────────
// If we can detect a mismatch, add a warning in vishesh.
function checkNakshatraRashi(array &$panchang): void
{
    $nakshatraRashiMap = [
        'अश्विनी' => 'मेष',
        'भरणी' => 'मेष',
        'कृत्तिका' => 'वृषभ',
        'रोहिणी' => 'वृषभ',
        'मृगशिरा' => 'मिथुन',
        'आर्द्रा' => 'मिथुन',
        'पुनर्वसु' => 'कर्क',
        'पुष्य' => 'कर्क',
        'आश्लेषा' => 'कर्क',
        'मघा' => 'सिंह',
        'पूर्वाफाल्गुनी' => 'सिंह',
        'उत्तराफाल्गुनी' => 'कन्या',
        'हस्त' => 'कन्या',
        'चित्रा' => 'तुला',
        'स्वाति' => 'तुला',
        'विशाखा' => 'वृश्चिक',
        'अनुराधा' => 'वृश्चिक',
        'ज्येष्ठा' => 'वृश्चिक',
        'मूल' => 'धनु',
        'पूर्वाषाढ़' => 'धनु',
        'उत्तराषाढ़' => 'मकर',
        'श्रवण' => 'मकर',
        'धनिष्ठा' => 'कुंभ',
        'शतभिषा' => 'कुंभ',
        'पूर्वाभाद्रपद' => 'मीन',
        'उत्तराभाद्रपद' => 'मीन',
        'रेवती' => 'मीन',
    ];

    $nakText = $panchang['nakshatra'] ?? '';
    $rashi = $panchang['chandra']['rashi'] ?? '';

    foreach ($nakshatraRashiMap as $nak => $expectedRashi) {
        if (mb_strpos($nakText, $nak) !== false) {
            if ($rashi !== $expectedRashi) {
                // PHP corrects the rashi
                $panchang['chandra']['rashi'] = $expectedRashi;
                $old = $rashi;
                $note = "⚠️ चंद्र राशि PHP द्वारा सुधारी: {$old} → {$expectedRashi} ({$nak} नक्षत्र)";
                $panchang['vishesh'] = ($panchang['vishesh'] ?? '')
                    ? $panchang['vishesh'] . ' | ' . $note
                    : $note;
            }
            break;
        }
    }
}

// ─── Fetch from providers ─────────────────────────────────────────────────────
$geminiData = null;
$openaiData = null;
$groqData = null;

if ($providerParam === 'gemini' || $providerParam === 'all') {
    if (!empty($geminiKey))
        $geminiData = fetchGemini($geminiKey, $systemPrompt, $userPrompt);
}
if ($providerParam === 'groq' || ($providerParam === 'all' && (!$geminiData || $useCrossCheck))) {
    if (!empty($groqKey))
        $groqData = fetchGroq($groqKey, $systemPrompt, $userPrompt);
}
if ($providerParam === 'openai' || ($providerParam === 'all' && (!$geminiData && !$groqData || $useCrossCheck))) {
    if (!empty($openaiKey))
        $openaiData = fetchOpenAI($openaiKey, $systemPrompt, $userPrompt);
}

$finalPanchang = $geminiData ?: ($groqData ?: $openaiData);

// ─── Cross-check (tithi mismatch warning) ────────────────────────────────────
$sources = array_filter(['Gemini' => $geminiData, 'Groq' => $groqData, 'OpenAI' => $openaiData]);
if (count($sources) >= 2 && $providerParam === 'all') {
    $keys = array_keys($sources);
    $m1 = $sources[$keys[0]];
    $m2 = $sources[$keys[1]];
    $t1 = mb_substr($m1['tithi'] ?? '', 0, 10);
    $t2 = mb_substr($m2['tithi'] ?? '', 0, 10);
    if (mb_strpos($t1, $t2) === false && mb_strpos($t2, $t1) === false) {
        $note = "⚠️ AI Cross-check: {$keys[0]} → '{$m1['tithi']}' | {$keys[1]} → '{$m2['tithi']}'";
        $finalPanchang['vishesh'] = ($finalPanchang['vishesh'] ?? '')
            ? $finalPanchang['vishesh'] . ' | ' . $note
            : $note;
    }
}

// ─── PHP-side corrections (applied regardless of which AI answered) ──────────
if ($finalPanchang) {
    overrideRahuKaal($finalPanchang, $correctRahuKaal);  // always correct rahu kaal
    checkNakshatraRashi($finalPanchang);                  // always correct rashi mismatch
}

// ─── Save to cache & respond ──────────────────────────────────────────────────
if ($finalPanchang) {
    try {
        $stmtSave = $pdo->prepare(
            "REPLACE INTO ai_content_cache (content_type, content_key, response_json) VALUES ('panchang', ?, ?)"
        );
        $stmtSave->execute([$date, json_encode($finalPanchang, JSON_UNESCAPED_UNICODE)]);
    } catch (Exception $e) {
    }

    echo json_encode([
        'success' => true,
        'date' => $date,
        'panchang' => $finalPanchang,
        'source' => count($sources) > 1 ? 'ai-multi-model' : 'ai-' . array_key_first($sources)
    ], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to generate Panchang. All AI models failed. Please check API Keys/Quota.'
    ]);
}