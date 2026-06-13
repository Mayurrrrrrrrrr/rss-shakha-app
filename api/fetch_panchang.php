<?php
require_once '../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/PanchangCalculator.php';
require_once __DIR__ . '/sync/auth_api.php';

/**
 * Local Panchang API with AI Cache Integration
 * ----------------------------
 * Calculates daily Tithi, Paksha, and Hindu Month using Surya Siddhanta approximation.
 * If AI is enabled for the Shakha, it integrates the cached or fetched AI Panchang.
 */
header('Content-Type: application/json; charset=UTF-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

$userContext = authenticateAPIRequest();
$shakhaId = $userContext['shakha_id'];

$date = $_GET['date'] ?? date('Y-m-d');

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid date format']);
    exit;
}

try {
    // 1. Fetch AI credentials from shakhas table
    $stmt = $pdo->prepare("SELECT gemini_api_key, openai_api_key, groq_api_key FROM shakhas WHERE id = ?");
    $stmt->execute([$shakhaId]);
    $shakha = $stmt->fetch();
    $hasAi = (!empty($shakha['gemini_api_key']) || !empty($shakha['openai_api_key']) || !empty($shakha['groq_api_key']));

    $aiPanchang = null;
    $cacheKey = "shakha_{$shakhaId}_{$date}";

    // 2. Try to get cached AI Panchang
    $stmtC = $pdo->prepare("SELECT response_json FROM ai_content_cache WHERE content_type='panchang' AND content_key=?");
    $stmtC->execute([$cacheKey]);
    $cachedJson = $stmtC->fetchColumn();

    if ($cachedJson) {
        $aiPanchang = json_decode($cachedJson, true);
    } elseif ($hasAi) {
        // If not cached but AI is enabled, fetch via local HTTP call to fetch_panchang_ai.php
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'];
        $dir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
        $token = generateAPIToken($userContext['user_id'], $userContext['user_type'], $userContext['shakha_id']);
        $url = $protocol . $host . $dir . '/fetch_panchang_ai.php?date=' . urlencode($date) . '&token=' . urlencode($token);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 25,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);
        $responseAi = curl_exec($ch);
        curl_close($ch);

        if ($responseAi) {
            $decodedAi = json_decode($responseAi, true);
            if ($decodedAi && isset($decodedAi['success']) && $decodedAi['success'] && isset($decodedAi['panchang'])) {
                $aiPanchang = $decodedAi['panchang'];
            }
        }
    }

    // 3. Fall back to local calculation if AI data is unavailable
    $calc = new PanchangCalculator();
    $result = $calc->getPanchang($date);

    if ($aiPanchang) {
        $tithi = $aiPanchang['tithi'] ?? $result['tithi_hindi'];
        $paksha = $aiPanchang['paksha'] ?? $result['paksha_hindi'];
        
        // Extract month name from AI maah object
        if (is_array($aiPanchang['maah'] ?? null)) {
            $vikram_month = $aiPanchang['maah']['purnimant'] ?? $result['maah_purnimant_hindi'];
        } else {
            $vikram_month = $result['maah_purnimant_hindi'];
        }
        
        $utsav = $aiPanchang['vrat_tyohar'] ?? '';
        $vikram_samvat = $aiPanchang['samvat']['vikram'] ?? $result['vikram_samvat'];
        $shaka_samvat = $aiPanchang['samvat']['shaka'] ?? $result['shaka_samvat'];
        $yugabdha = $aiPanchang['samvat']['yugabdha'] ?? $result['yugabdha'];
    } else {
        $tithi = $result['tithi_hindi'];
        $paksha = $result['paksha_hindi'];
        $vikram_month = $result['maah_purnimant_hindi'];
        $utsav = '';
        $vikram_samvat = $result['vikram_samvat'];
        $shaka_samvat = $result['shaka_samvat'];
        $yugabdha = $result['yugabdha'];
    }

    // Ensure paksha has 'पक्ष' suffix for dropdown matching
    if ($paksha === 'शुक्ल' || $paksha === 'कृष्ण') {
        $paksha .= ' पक्ष';
    }

    // Keep only digits for samvat numbers to align with web/app dropdown lists
    if (preg_match('/\d+/', (string)$vikram_samvat, $m)) {
        $vikram_samvat = $m[0];
    }
    if (preg_match('/\d+/', (string)$shaka_samvat, $m)) {
        $shaka_samvat = $m[0];
    }
    if (preg_match('/\d+/', (string)$yugabdha, $m)) {
        $yugabdha = $m[0];
    }

    echo json_encode([
        'status' => 'success',
        'date' => $date,
        'cached' => !empty($cachedJson),
        'panchang' => [
            'tithi' => $tithi,
            'paksha' => $paksha,
            'vikram_month' => $vikram_month,
            'shaka_month' => $vikram_month,
            'vikram_samvat' => $vikram_samvat,
            'shaka_samvat' => $shaka_samvat,
            'yugabdha' => $yugabdha,
            'utsav' => $utsav,
            'nakshatra' => $aiPanchang ? ($aiPanchang['nakshatra'] ?? $result['nakshatra'] ?? '-') : ($result['nakshatra'] ?? '-'),
            'sunrise' => $aiPanchang ? ($aiPanchang['surya']['udaya'] ?? '06:00 AM') : '06:00 AM',
            'sunset' => $aiPanchang ? ($aiPanchang['surya']['asta'] ?? '06:30 PM') : '06:30 PM'
        ]
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
