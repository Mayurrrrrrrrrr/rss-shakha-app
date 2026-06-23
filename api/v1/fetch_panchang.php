<?php
require_once '../../includes/auth.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/PanchangHelper.php';
require_once __DIR__ . '/sync/auth_api.php';

header('Content-Type: application/json; charset=UTF-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

// Helper function to build daily panchang object
function getDailyPanchang($date, $shakhaId, $pdo, $calc, $userContext) {
    // 1. Fetch AI credentials from shakhas table
    $stmt = $pdo->prepare("SELECT gemini_api_key, openai_api_key, groq_api_key FROM shakhas WHERE id = ?");
    $stmt->execute([$shakhaId]);
    $shakha = $stmt->fetch();
    $hasAi = (!empty($shakha['gemini_api_key']) || !empty($shakha['openai_api_key']) || !empty($shakha['groq_api_key']));

    $cacheKey = "shakha_{$shakhaId}_{$date}";

    // 2. Check if we need to call AI to generate and cache AI content
    $stmtC = $pdo->prepare("SELECT response_json FROM ai_content_cache WHERE content_type='panchang' AND content_key=?");
    $stmtC->execute([$cacheKey]);
    $cachedJson = $stmtC->fetchColumn();

    if (!$cachedJson && $hasAi) {
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
        curl_exec($ch);
        curl_close($ch);
    }

    // 3. Now let PanchangHelper fetch or calculate the complete Panchang
    $panchang = PanchangHelper::getForDate($pdo, $date, $shakhaId);
    
    // Add extra fields for compatibility in return array
    if (strpos($panchang['paksha'], 'पक्ष') === false && ($panchang['paksha'] === 'शुक्ल' || $panchang['paksha'] === 'कृष्ण')) {
        $panchang['paksha'] .= ' पक्ष';
    }
    
    if (preg_match('/\d+/', (string)$panchang['vikram_samvat'], $m)) {
        $panchang['vikram_samvat'] = $m[0];
    }
    if (preg_match('/\d+/', (string)$panchang['shaka_samvat'], $m)) {
        $panchang['shaka_samvat'] = $m[0];
    }
    if (preg_match('/\d+/', (string)$panchang['yugabdha'], $m)) {
        $panchang['yugabdha'] = $m[0];
    }

    return $panchang;
}

function getMonthlyPanchangs($year, $month, $shakhaId, $pdo, $calc) {
    $daysInMonth = (int) (new DateTime("$year-$month-01"))->format('t');
    $list = [];

    for ($day = 1; $day <= $daysInMonth; $day++) {
        $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
        $panchang = PanchangHelper::getForDate($pdo, $date, $shakhaId);
        $panchang['date'] = $date;

        if (strpos($panchang['paksha'], 'पक्ष') === false && ($panchang['paksha'] === 'शुक्ल' || $panchang['paksha'] === 'कृष्ण')) {
            $panchang['paksha'] .= ' पक्ष';
        }

        if (preg_match('/\d+/', (string)$panchang['vikram_samvat'], $m)) {
            $panchang['vikram_samvat'] = $m[0];
        }
        if (preg_match('/\d+/', (string)$panchang['shaka_samvat'], $m)) {
            $panchang['shaka_samvat'] = $m[0];
        }
        if (preg_match('/\d+/', (string)$panchang['yugabdha'], $m)) {
            $panchang['yugabdha'] = $m[0];
        }

        $list[] = $panchang;
    }
    return $list;
}

try {
    $userContext = authenticateAPIRequest();
    $shakhaId = $userContext['shakha_id'];
    $calc = new PanchangCalculator();

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
