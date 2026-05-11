<?php
/**
 * api/fetch_panchang_ai.php — Final Fixed Version
 *
 * ARCHITECTURE: PHP calculates what it KNOWS for certain.
 *               AI calculates only what needs astronomical lookup (times).
 *
 * PHP provides to AI (locked, cannot be changed by AI):
 *   - Tithi name & paksha        (from PanchangCalculator — now fixed)
 *   - Rahu Kaal                  (from hardcoded weekday table)
 *   - Samvat numbers             (formula-based)
 *   - Nakshatra + Rashi          (from moon longitude calculation)
 *   - Maah name                  (from Full Moon table)
 *
 * AI provides (time-based, needs astronomy engine):
 *   - Exact tithi transition time
 *   - Nakshatra end time
 *   - Yoga name + end time
 *   - Karana name + end time
 *   - Sunrise / Sunset
 *   - Moonrise / Moonset
 *   - Shubh Muhurtas
 *   - Vrat/Tyohar
 */

require_once '../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/PanchangCalculator.php';
requireLogin();

header('Content-Type: application/json; charset=UTF-8');

$shakhaId = getCurrentShakhaId();
if (!$shakhaId) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$stmt = $pdo->prepare("SELECT gemini_api_key, openai_api_key, groq_api_key, use_ai_crosscheck, city_name FROM shakhas WHERE id = ?");
$stmt->execute([$shakhaId]);
$row           = $stmt->fetch();
$geminiKey     = $row['gemini_api_key']  ?? '';
$openaiKey     = $row['openai_api_key']  ?? '';
$groqKey       = $row['groq_api_key']    ?? '';
$useCrossCheck = ($row['use_ai_crosscheck'] ?? 0) == 1;
$cityName      = $row['city_name'] ?? 'मुम्बई';

if (empty($geminiKey) && empty($openaiKey) && empty($groqKey)) {
    echo json_encode(['success' => false, 'message' => 'AI सुविधा सक्रिय नहीं है। कृपया सेटिंग्स में API Key डालें।']);
    exit;
}

$date          = $_GET['date']     ?? date('Y-m-d');
$providerParam = $_GET['provider'] ?? 'all';
$forceFetch    = isset($_GET['force']) && $_GET['force'] === 'true';

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    echo json_encode(['success' => false, 'message' => 'Invalid date format']);
    exit;
}

// ─── Cache Check ──────────────────────────────────────────────────────────────
if (!$forceFetch) {
    try {
        $stmtC = $pdo->prepare("SELECT response_json FROM ai_content_cache WHERE content_type='panchang' AND content_key=?");
        $stmtC->execute([$date]);
        $cached = $stmtC->fetchColumn();
        if ($cached) {
            echo json_encode(['success'=>true,'date'=>$date,'panchang'=>json_decode($cached,true),'source'=>'cache'], JSON_UNESCAPED_UNICODE);
            exit;
        }
    } catch (Exception $e) {}
}

// ─── Date helpers ─────────────────────────────────────────────────────────────
$ts           = strtotime($date);
$dayOfWeek    = (int) date('w', $ts);
$hindiDays    = ['रविवार','सोमवार','मंगलवार','बुधवार','गुरुवार','शुक्रवार','शनिवार'];
$hindiMonths  = ['जनवरी','फ़रवरी','मार्च','अप्रैल','मई','जून','जुलाई','अगस्त','सितंबर','अक्टूबर','नवंबर','दिसंबर'];
$dayName      = $hindiDays[$dayOfWeek];
$formattedDate = date('j', $ts) . ' ' . $hindiMonths[date('n',$ts)-1] . ' ' . date('Y',$ts);

// ─── Rahu Kaal: hardcoded weekday table ──────────────────────────────────────
$rahuKaalTable = [
    0 => '04:30 PM to 06:00 PM',
    1 => '07:30 AM to 09:00 AM',
    2 => '03:00 PM to 04:30 PM',
    3 => '12:00 PM to 01:30 PM',
    4 => '01:30 PM to 03:00 PM',
    5 => '10:30 AM to 12:00 PM',
    6 => '09:00 AM to 10:30 AM',
];
$correctRahuKaal = $rahuKaalTable[$dayOfWeek];

// ─── PHP Panchang Calculator ──────────────────────────────────────────────────
$calc = new PanchangCalculator();
$pc   = $calc->getPanchang($date);

$tithiHindi     = $pc['tithi_hindi'];
$nextTithiHindi = $pc['next_tithi_hindi'];
$pakshaHindi    = $pc['paksha_hindi'];
$nakshatraHindi = $pc['nakshatra'];
$chandraRashi   = $pc['chandra_rashi'];
$maahPurnimant  = $pc['maah_purnimant_hindi'];
$maahAmant      = $pc['maah_amant_hindi'];
$vikramSamvat   = $pc['vikram_samvat'] . ' (' . $pc['vikram_samvat_name'] . ')';
$shakaSamvat    = (string) $pc['shaka_samvat'];
$yugabdha       = (string) $pc['yugabdha'];

// ─── System Prompt ────────────────────────────────────────────────────────────
$systemPrompt = <<<SYSTEM
You are a Vedic Panchang TIME calculator. Your ONLY job is to calculate TIME-BASED values.
You output ONLY a single valid JSON object — no preamble, no explanation, no markdown.

══════════════════════════════════════════════════════
SECTION A — FIXED VALUES (already computed — copy EXACTLY, no changes)
══════════════════════════════════════════════════════

These are mathematically verified. Do NOT recalculate them. Copy them verbatim.

  rahukaal:          "{$correctRahuKaal}"
  paksha:            "{$pakshaHindi}"
  tithi_base:        "{$tithiHindi}" (transitioning to "{$nextTithiHindi}")
  nakshatra_base:    "{$nakshatraHindi}"
  chandra_rashi:     "{$chandraRashi}"
  maah_purnimant:    "{$maahPurnimant}"
  maah_amant:        "{$maahAmant}"
  vikram_samvat:     "{$vikramSamvat}"
  shaka_samvat:      "{$shakaSamvat}"
  yugabdha:          "{$yugabdha}"

══════════════════════════════════════════════════════
SECTION B — YOUR CALCULATIONS (times only)
══════════════════════════════════════════════════════

Location: {$cityName}, India. All times in IST (UTC+5:30). Format: HH:MM AM/PM.

B1. SURYA: Calculate precise sunrise and sunset for {$cityName} on this date.

B2. CHANDRA: Calculate moonrise and moonset for {$cityName}.
    For {$pakshaHindi} paksha, {$tithiHindi}:
    - कृष्ण पक्ष = moon rises late at night, sets in morning hours. NOT afternoon.
    - शुक्ल पक्ष = moon rises in afternoon/evening, sets after midnight.
    Verify these are consistent with the paksha before outputting.

B3. TITHI TRANSITION TIME: At what time does {$tithiHindi} end and {$nextTithiHindi} begin?
    Moon-sun angle changes at ~0.5 degrees/hour. Calculate this precisely.
    Format: "{$tithiHindi} (ends HH:MM AM/PM) / {$nextTithiHindi} (from HH:MM AM/PM)"
    If {$tithiHindi} spans the full day: "{$tithiHindi} (अगले दिन तक)"

B4. NAKSHATRA END TIME: At what time does {$nakshatraHindi} end today?
    Moon moves ~0.55 deg/hour, nakshatra = 13.33 deg. End time must differ from sunrise.
    Format: "{$nakshatraHindi} (ends HH:MM AM/PM)"

B5. YOGA: Which yoga is active at sunrise? Give its Hindi name and end time.
    27 yogas based on sum of sun+moon longitude. Each spans ~13.33 degrees.
    Format: "योग_नाम (ends HH:MM AM/PM)"

B6. KARANA: Which karana is active at sunrise? Give its Hindi name and end time.
    Each karana = half tithi. Movable: बव, बालव, कौलव, तैतिल, गर, वणिज, विष्टि.
    Format: "करण_नाम (ends HH:MM AM/PM)"

B7. SHUBH MUHURTAS (all as ranges HH:MM to HH:MM):
    - abhijit     = midday ± 24 min. Midday = (sunrise+sunset)/2. Around 12:30 PM.
    - vijay       = 9th of 15 equal daytime slots. Around 2:00 PM. NOT near sunrise.
    - amrit_kaal  = nakshatra-based auspicious period. NOT the same as sunrise.
    - ravi_yoga   = range if applicable, else null.
    - sarvarth_siddhi = range if applicable, else null.

B8. VRAT_TYOHAR: Any Hindu festival or vrat on this date in Hindi? Else null.

B9. VISHESH: Any special note. Else null.

══════════════════════════════════════════════════════
SECTION C — SELF-CHECK
══════════════════════════════════════════════════════
□ rahukaal = exactly "{$correctRahuKaal}" ?
□ paksha = exactly "{$pakshaHindi}" ?
□ tithi starts with "{$tithiHindi}" ?
□ nakshatra starts with "{$nakshatraHindi}" ?
□ chandra.rashi = exactly "{$chandraRashi}" ?
□ No two fields share the same HH:MM timestamp?
□ vijay is ~2 PM, not sunrise?
□ chandra.asta consistent with {$pakshaHindi} paksha?

══════════════════════════════════════════════════════
SECTION D — OUTPUT JSON
══════════════════════════════════════════════════════
{
  "surya":    { "udaya": "HH:MM AM/PM", "asta": "HH:MM AM/PM" },
  "chandra":  { "udaya": "HH:MM AM/PM", "asta": "HH:MM AM/PM", "rashi": "{$chandraRashi}" },
  "samvat":   { "vikram": "{$vikramSamvat}", "shaka": "{$shakaSamvat}", "yugabdha": "{$yugabdha}" },
  "maah":     { "purnimant": "{$maahPurnimant}", "amant": "{$maahAmant}" },
  "paksha":   "{$pakshaHindi}",
  "tithi":    "{$tithiHindi} (ends HH:MM AM/PM) / {$nextTithiHindi} (from HH:MM AM/PM)",
  "nakshatra":"{$nakshatraHindi} (ends HH:MM AM/PM)",
  "yoga":     "Hindi yoga name (ends HH:MM AM/PM)",
  "karana":   "Hindi karana name (ends HH:MM AM/PM)",
  "rahukaal": "{$correctRahuKaal}",
  "shubh_muhurt": {
    "abhijit":         "HH:MM to HH:MM",
    "amrit_kaal":      "HH:MM to HH:MM",
    "vijay":           "HH:MM to HH:MM",
    "ravi_yoga":       "HH:MM to HH:MM or null",
    "sarvarth_siddhi": "HH:MM to HH:MM or null"
  },
  "vrat_tyohar": "Hindi name or null",
  "vishesh":     "note or null"
}
SYSTEM;

$userPrompt = "Date: {$date} ({$dayName}, {$formattedDate}). "
            . "Location: {$cityName}, India (IST=UTC+5:30). "
            . "Paksha: {$pakshaHindi}. Tithi: {$tithiHindi}. "
            . "Return JSON only.";

// ─── Strip markdown from AI response ─────────────────────────────────────────
function extractJson(string $text): string {
    $text = preg_replace('/```json\s*/i', '', $text);
    $text = preg_replace('/```\s*/', '', $text);
    $text = trim($text);
    if (preg_match('/\{.*\}/s', $text, $m)) return $m[0];
    return $text;
}

// ─── Gemini ───────────────────────────────────────────────────────────────────
function fetchGemini(string $apiKey, string $sys, string $usr): ?array {
    $url  = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$apiKey}";
    $body = json_encode([
        'systemInstruction' => ['parts' => [['text' => $sys]]],
        'contents'          => [['role' => 'user', 'parts' => [['text' => $usr]]]],
        'generationConfig'  => ['temperature' => 0, 'responseMimeType' => 'application/json'],
    ], JSON_UNESCAPED_UNICODE);
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,
        CURLOPT_HTTPHEADER=>['Content-Type: application/json'],
        CURLOPT_POSTFIELDS=>$body, CURLOPT_TIMEOUT=>45, CURLOPT_SSL_VERIFYPEER=>false]);
    $res = curl_exec($ch); curl_close($ch);
    if (!$res) return null;
    $d = json_decode($res, true);
    $t = $d['candidates'][0]['content']['parts'][0]['text'] ?? '';
    return $t ? (json_decode(extractJson($t), true) ?: null) : null;
}

// ─── OpenAI ───────────────────────────────────────────────────────────────────
function fetchOpenAI(string $apiKey, string $sys, string $usr): ?array {
    $body = json_encode([
        'model'           => 'gpt-4o-mini',
        'messages'        => [['role'=>'system','content'=>$sys],['role'=>'user','content'=>$usr]],
        'temperature'     => 0,
        'response_format' => ['type' => 'json_object'],
    ], JSON_UNESCAPED_UNICODE);
    $ch = curl_init("https://api.openai.com/v1/chat/completions");
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,
        CURLOPT_HTTPHEADER=>['Content-Type: application/json','Authorization: Bearer '.$apiKey],
        CURLOPT_POSTFIELDS=>$body, CURLOPT_TIMEOUT=>45, CURLOPT_SSL_VERIFYPEER=>false]);
    $res = curl_exec($ch); curl_close($ch);
    if (!$res) return null;
    $d = json_decode($res, true);
    $t = $d['choices'][0]['message']['content'] ?? '';
    return $t ? (json_decode($t, true) ?: null) : null;
}

// ─── Groq ─────────────────────────────────────────────────────────────────────
function fetchGroq(string $apiKey, string $sys, string $usr): ?array {
    $body = json_encode([
        'model'           => 'llama-3.3-70b-versatile',
        'messages'        => [['role'=>'system','content'=>$sys],['role'=>'user','content'=>$usr]],
        'temperature'     => 0,
        'response_format' => ['type' => 'json_object'],
    ], JSON_UNESCAPED_UNICODE);
    $ch = curl_init("https://api.groq.com/openai/v1/chat/completions");
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,
        CURLOPT_HTTPHEADER=>['Content-Type: application/json','Authorization: Bearer '.$apiKey],
        CURLOPT_POSTFIELDS=>$body, CURLOPT_TIMEOUT=>45, CURLOPT_SSL_VERIFYPEER=>false]);
    $res = curl_exec($ch); curl_close($ch);
    if (!$res) return null;
    $d = json_decode($res, true);
    $t = $d['choices'][0]['message']['content'] ?? '';
    return $t ? (json_decode($t, true) ?: null) : null;
}

// ─── Fetch ────────────────────────────────────────────────────────────────────
$geminiData = null; $openaiData = null; $groqData = null;

if ($providerParam === 'gemini' || $providerParam === 'all') {
    if (!empty($geminiKey)) $geminiData = fetchGemini($geminiKey, $systemPrompt, $userPrompt);
}
if ($providerParam === 'groq' || ($providerParam === 'all' && (!$geminiData || $useCrossCheck))) {
    if (!empty($groqKey)) $groqData = fetchGroq($groqKey, $systemPrompt, $userPrompt);
}
if ($providerParam === 'openai' || ($providerParam === 'all' && (!$geminiData && !$groqData || $useCrossCheck))) {
    if (!empty($openaiKey)) $openaiData = fetchOpenAI($openaiKey, $systemPrompt, $userPrompt);
}

$final = $geminiData ?: ($groqData ?: $openaiData);

// ─── Cross-check warning ──────────────────────────────────────────────────────
$sources = array_filter(['Gemini'=>$geminiData,'Groq'=>$groqData,'OpenAI'=>$openaiData]);
if (count($sources) >= 2 && $providerParam === 'all') {
    $keys = array_keys($sources);
    $y1   = mb_substr($sources[$keys[0]]['yoga'] ?? '', 0, 6);
    $y2   = mb_substr($sources[$keys[1]]['yoga'] ?? '', 0, 6);
    if ($y1 && $y2 && mb_strpos($y1,$y2)===false && mb_strpos($y2,$y1)===false) {
        $note = "⚠️ Yoga cross-check: {$keys[0]}→'{$y1}' vs {$keys[1]}→'{$y2}'";
        $final['vishesh'] = ($final['vishesh'] ?? '') ? $final['vishesh'].' | '.$note : $note;
    }
}

// ─── PHP overrides — lock all known-certain values ────────────────────────────
if ($final) {

    // 1. Rahu Kaal
    $final['rahukaal'] = $correctRahuKaal;

    // 2. Paksha
    $final['paksha'] = $pakshaHindi;

    // 3. Tithi — preserve AI's time, fix name if wrong
    $aiTithi = $final['tithi'] ?? '';
    if (mb_strpos($aiTithi, $tithiHindi) === false) {
        preg_match('/(\d{1,2}:\d{2}\s*[AP]M)/i', $aiTithi, $tm);
        $timeHint = $tm[1] ?? 'अगले दिन तक';
        $final['tithi'] = "{$tithiHindi} (ends {$timeHint}) / {$nextTithiHindi}";
        $note = "⚠️ तिथि PHP द्वारा सुधारी ({$tithiHindi})";
        $final['vishesh'] = ($final['vishesh'] ?? '') ? $final['vishesh'].' | '.$note : $note;
    }

    // 4. Nakshatra — preserve AI's time, fix name if wrong
    $aiNak = $final['nakshatra'] ?? '';
    if (mb_strpos($aiNak, $nakshatraHindi) === false) {
        preg_match('/(\d{1,2}:\d{2}\s*[AP]M)/i', $aiNak, $tm);
        $timeHint = $tm[1] ?? '';
        $final['nakshatra'] = $timeHint
            ? "{$nakshatraHindi} (ends {$timeHint})"
            : $nakshatraHindi;
        $note = "⚠️ नक्षत्र PHP द्वारा सुधारा ({$nakshatraHindi})";
        $final['vishesh'] = ($final['vishesh'] ?? '') ? $final['vishesh'].' | '.$note : $note;
    }

    // 5. Chandra Rashi — always override
    if (!isset($final['chandra'])) $final['chandra'] = [];
    $final['chandra']['rashi'] = $chandraRashi;

    // 6. Samvat — always override
    $final['samvat'] = ['vikram'=>$vikramSamvat,'shaka'=>$shakaSamvat,'yugabdha'=>$yugabdha];

    // 7. Maah — always override
    if (!isset($final['maah'])) $final['maah'] = [];
    $final['maah']['purnimant'] = $maahPurnimant;
    $final['maah']['amant']     = $maahAmant;

    // 8. Krishna paksha chandraast sanity check
    if ($pakshaHindi === 'कृष्ण') {
        $chandraAsta = $final['chandra']['asta'] ?? '';
        if (preg_match('/(\d{1,2}):(\d{2})\s*PM/i', $chandraAsta, $m)) {
            $hour = (int)$m[1];
            if ($hour >= 1 && $hour <= 7) {
                $note = "⚠️ चंद्रास्त जाँचें: कृष्ण पक्ष में दोपहर/शाम को चंद्रास्त सामान्यतः नहीं होता।";
                $final['vishesh'] = ($final['vishesh'] ?? '') ? $final['vishesh'].' | '.$note : $note;
            }
        }
    }
}

// ─── Save & respond ───────────────────────────────────────────────────────────
if ($final) {
    try {
        $pdo->prepare("REPLACE INTO ai_content_cache (content_type,content_key,response_json) VALUES ('panchang',?,?)")
            ->execute([$date, json_encode($final, JSON_UNESCAPED_UNICODE)]);
    } catch (Exception $e) {}
    $src = count($sources) > 1 ? 'ai-multi' : 'ai-'.array_key_first($sources);
    echo json_encode(['success'=>true,'date'=>$date,'panchang'=>$final,'source'=>$src], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode(['success'=>false,'message'=>'Failed to generate Panchang. All AI models failed. Please check API Keys/Quota.']);
}
