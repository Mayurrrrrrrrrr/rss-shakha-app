<?php

/**
 * Daily Morning Message Script
 * 
 * This script is meant to be run via cron job at 6:00 AM IST daily.
 * It generates a creative image with panchang + subhashit and sends it
 * to the configured WhatsApp group.
 * 
 * Cron entry:
 * 0 6 * * * cd /var/www/html/sanghasthan && php scripts/send_daily_message.php >> storage/logs/daily_message.log 2>&1
 */

// Bootstrap
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/app/Core/Autoloader.php';
\App\Core\Autoloader::register();
require_once BASE_PATH . '/config/db.php';
\App\Core\DB::init($pdo);
require_once BASE_PATH . '/includes/PanchangHelper.php';

$today = date('Y-m-d');
$timestamp = date('Y-m-d H:i:s');
echo "[{$timestamp}] === Starting Daily Message Script ===\n";

// 1. Get all shakhas with WhatsApp enabled
$configs = $pdo->query("
    SELECT dmc.*, s.name AS shakha_name, s.logo AS shakha_logo, s.city_name
    FROM daily_message_config dmc
    JOIN shakhas s ON s.id = dmc.shakha_id
    WHERE dmc.whatsapp_enabled = 1
")->fetchAll();

if (empty($configs)) {
    echo "[{$timestamp}] No shakhas have WhatsApp messaging enabled. Exiting.\n";
    exit(0);
}

foreach ($configs as $config) {
    $shakhaId = $config['shakha_id'];
    $shakhaName = $config['shakha_name'];
    echo "\n--- Processing Shakha: {$shakhaName} (ID: {$shakhaId}) ---\n";

    try {
        // 2. Check if message already sent today
        $alreadySent = $pdo->prepare("SELECT id FROM daily_message_log WHERE shakha_id = ? AND message_date = ? AND channel = 'whatsapp' AND status = 'success'");
        $alreadySent->execute([$shakhaId, $today]);
        if ($alreadySent->fetch()) {
            echo "Message already sent today. Skipping.\n";
            continue;
        }

        // 3. Get today's panchang
        $cityName = $config['city_name'] ?? 'Mumbai';
        $panchang = \PanchangHelper::getForDate($today, $pdo, $cityName);
        if (empty($panchang)) {
            throw new \Exception("Panchang data not available for {$today}");
        }
        echo "Panchang fetched: Tithi={$panchang['tithi']}, Nakshatra={$panchang['nakshatra']}\n";

        // 4. Get next subhashit in round-robin order (non-repeating for a long time)
        $lastSubhashitId = (int)$config['last_subhashit_id'];
        
        $subhashitStmt = $pdo->prepare("
            SELECT * FROM subhashits 
            WHERE shakha_id = ? AND (is_deleted IS NULL OR is_deleted = 0) AND id > ?
            ORDER BY id ASC 
            LIMIT 1
        ");
        $subhashitStmt->execute([$shakhaId, $lastSubhashitId]);
        $subhashit = $subhashitStmt->fetch();

        // If no subhashit found after last ID, loop back to the beginning
        if (!$subhashit) {
            $subhashitStmt = $pdo->prepare("
                SELECT * FROM subhashits 
                WHERE shakha_id = ? AND (is_deleted IS NULL OR is_deleted = 0)
                ORDER BY id ASC 
                LIMIT 1
            ");
            $subhashitStmt->execute([$shakhaId]);
            $subhashit = $subhashitStmt->fetch();
        }

        if ($subhashit) {
            echo "Subhashit selected: ID={$subhashit['id']}\n";
            // Update last_subhashit_id for next run
            $pdo->prepare("UPDATE daily_message_config SET last_subhashit_id = ? WHERE shakha_id = ?")
                ->execute([$subhashit['id'], $shakhaId]);
        } else {
            echo "Warning: No subhashit available for this shakha.\n";
        }

        // 5. Generate the creative image
        $logoPath = BASE_PATH . '/' . ($config['shakha_logo'] ?: 'assets/images/logo.svg');
        $generator = new \App\Core\ImageGenerator();
        $imagePath = $generator->generate($panchang, $subhashit, $logoPath, $shakhaName);
        echo "Creative generated: {$imagePath}\n";

        // 6. Build caption text
        $caption = buildCaption($panchang, $subhashit, $shakhaName);

        // 7. Send to WhatsApp
        if (empty($config['whatsapp_api_instance']) || empty($config['whatsapp_api_token']) || empty($config['whatsapp_group_id'])) {
            throw new \Exception("WhatsApp API credentials not configured");
        }

        $whatsapp = new \App\Core\WhatsAppService($config['whatsapp_api_instance'], $config['whatsapp_api_token']);
        $result = $whatsapp->sendImageToGroup($config['whatsapp_group_id'], $imagePath, $caption);

        if ($result['success']) {
            echo "WhatsApp message sent successfully!\n";
            $logStatus = 'success';
            $logError = null;
        } else {
            echo "WhatsApp send failed: {$result['response']}\n";
            $logStatus = 'failed';
            $logError = $result['response'];
        }

        // 8. Log the result
        $logStmt = $pdo->prepare("
            INSERT INTO daily_message_log (shakha_id, message_date, channel, status, error_message, image_path, subhashit_id)
            VALUES (?, ?, 'whatsapp', ?, ?, ?, ?)
        ");
        $logStmt->execute([
            $shakhaId, $today, $logStatus, $logError, $imagePath, $subhashit['id'] ?? null
        ]);

    } catch (\Exception $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
        
        // Log the failure
        $logStmt = $pdo->prepare("
            INSERT INTO daily_message_log (shakha_id, message_date, channel, status, error_message)
            VALUES (?, ?, 'whatsapp', 'failed', ?)
        ");
        $logStmt->execute([$shakhaId, $today, $e->getMessage()]);
    }
}

echo "\n[" . date('Y-m-d H:i:s') . "] === Daily Message Script Complete ===\n";

// ====== HELPER FUNCTIONS ======

function buildCaption(array $panchang, ?array $subhashit, string $shakhaName): string
{
    $dayNames = ['रविवार', 'सोमवार', 'मंगलवार', 'बुधवार', 'गुरुवार', 'शुक्रवार', 'शनिवार'];
    $dayName = $dayNames[(int)date('w')];

    $lines = [];
    $lines[] = "🚩 *{$shakhaName}*";
    $lines[] = "📅 *{$dayName}* | " . date('d-m-Y');
    $lines[] = "";
    $lines[] = "🕉️ *आज का पंचांग:*";

    if (!empty($panchang['tithi'])) {
        $tithi = $panchang['tithi'];
        if (!empty($panchang['paksha'])) $tithi .= ' (' . $panchang['paksha'] . ')';
        $lines[] = "तिथि: {$tithi}";
    }
    if (!empty($panchang['nakshatra'])) $lines[] = "नक्षत्र: {$panchang['nakshatra']}";
    if (!empty($panchang['sunrise']))   $lines[] = "🌅 सूर्योदय: {$panchang['sunrise']}";
    if (!empty($panchang['sunset']))    $lines[] = "🌇 सूर्यास्त: {$panchang['sunset']}";

    if (!empty($panchang['utsav'])) {
        $lines[] = "";
        $lines[] = "🎉 *{$panchang['utsav']}*";
    }

    if ($subhashit) {
        $lines[] = "";
        $lines[] = "📜 *आज का सुभाषित:*";
        $lines[] = "_{$subhashit['sanskrit_text']}_";
        if (!empty($subhashit['hindi_meaning'])) {
            $lines[] = "अर्थ: {$subhashit['hindi_meaning']}";
        }
    }

    $lines[] = "";
    $lines[] = "🚩 _भारत माता की जय!_";

    return implode("\n", $lines);
}
