<?php
require_once '../../includes/auth.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/PanchangCalculator.php';
require_once __DIR__ . '/sync/auth_api.php';

header('Content-Type: application/json; charset=UTF-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

$userContext = authenticateAPIRequest();
$shakhaId = $userContext['shakha_id'];

$calc = new PanchangCalculator();

// Helper function to build daily panchang object
function getDailyPanchang($date, $shakhaId, $pdo, $calc, $userContext) {
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
        // Fetch via local HTTP call to fetch_panchang_ai.php (located in parent folder of v1)
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'];
        $dir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
        $url = $protocol . $host . $dir . '/../fetch_panchang_ai.php?date=' . urlencode($date) . '&token=' . urlencode(generateAPIToken($userContext['user_id'], $userContext['user_type'], $userContext['shakha_id']));

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
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

    $result = $calc->getPanchang($date);

    if ($aiPanchang) {
        $tithi = $aiPanchang['tithi'] ?? $result['tithi_hindi'];
        $paksha = $aiPanchang['paksha'] ?? $result['paksha_hindi'];
        
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

    if ($paksha === 'शुक्ल' || $paksha === 'कृष्ण') {
        $paksha .= ' पक्ष';
    }

    if (preg_match('/\d+/', (string)$vikram_samvat, $m)) {
        $vikram_samvat = $m[0];
    }
    if (preg_match('/\d+/', (string)$shaka_samvat, $m)) {
        $shaka_samvat = $m[0];
    }
    if (preg_match('/\d+/', (string)$yugabdha, $m)) {
        $yugabdha = $m[0];
    }

    return [
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
    ];
}

function getMonthlyPanchangs($year, $month, $shakhaId, $pdo, $calc) {
    // Fetch all cached AI Panchang for the month in one batch query
    $likeKey = "shakha_{$shakhaId}_{$year}-" . sprintf('%02d', $month) . "-%";
    $stmtC = $pdo->prepare("SELECT content_key, response_json FROM ai_content_cache WHERE content_type='panchang' AND content_key LIKE ?");
    $stmtC->execute([$likeKey]);
    $cachedRows = $stmtC->fetchAll(PDO::FETCH_KEY_PAIR);

    $daysInMonth = (int) (new DateTime("$year-$month-01"))->format('t');
    $list = [];

    for ($day = 1; $day <= $daysInMonth; $day++) {
        $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
        $cacheKey = "shakha_{$shakhaId}_{$date}";
        $aiPanchang = null;
        if (isset($cachedRows[$cacheKey])) {
            $aiPanchang = json_decode($cachedRows[$cacheKey], true);
        }

        $result = $calc->getPanchang($date);

        if ($aiPanchang) {
            $tithi = $aiPanchang['tithi'] ?? $result['tithi_hindi'];
            $paksha = $aiPanchang['paksha'] ?? $result['paksha_hindi'];
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

        if ($paksha === 'शुक्ल' || $paksha === 'कृष्ण') {
            $paksha .= ' पक्ष';
        }

        if (preg_match('/\d+/', (string)$vikram_samvat, $m)) {
            $vikram_samvat = $m[0];
        }
        if (preg_match('/\d+/', (string)$shaka_samvat, $m)) {
            $shaka_samvat = $m[0];
        }
        if (preg_match('/\d+/', (string)$yugabdha, $m)) {
            $yugabdha = $m[0];
        }

        $list[] = [
            'date' => $date,
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
        ];
    }
    return $list;
}

try {
    if (isset($_GET['date'])) {
        $date = $_GET['date'];
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid date format']);
            exit;
        }
        $panchang = getDailyPanchang($date, $shakhaId, $pdo, $calc, $userContext);
        echo json_encode([
            'status' => 'success',
            'date' => $date,
            'panchang' => $panchang
        ]);
    } else {
        $year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));
        $month = isset($_GET['month']) ? intval($_GET['month']) : intval(date('m'));
        if ($year < 2020 || $year > 2040 || $month < 1 || $month > 12) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid year or month']);
            exit;
        }
        $list = getMonthlyPanchangs($year, $month, $shakhaId, $pdo, $calc);
        echo json_encode([
            'status' => 'success',
            'year' => $year,
            'month' => $month,
            'panchang_list' => $list
        ]);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
