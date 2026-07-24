<?php
// send_evening_message.php
require_once __DIR__ . '/../app/Core/Autoloader.php';
\App\Core\Autoloader::register();
define('BASE_PATH', dirname(__DIR__));
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/PanchangHelper.php';
\App\Core\DB::init($pdo);

$current_time = date('H:i:00');
$today = date('Y-m-d');
$log_prefix = "[$today $current_time] ";

echo "{$log_prefix}Starting evening Amrit Vachan message job...\n";

// Get all active shakhas that have evening whatsapp enabled and time matches
$stmt = $pdo->prepare("
    SELECT c.*, s.name as shakha_name, s.logo 
    FROM daily_message_config c
    JOIN shakhas s ON c.shakha_id = s.id
    WHERE c.evening_whatsapp_enabled = 1 
      AND c.whatsapp_api_instance IS NOT NULL 
      AND c.whatsapp_api_token IS NOT NULL 
      AND c.whatsapp_group_id IS NOT NULL
      AND c.evening_send_time = ?
");
$stmt->execute([$current_time]);
$configs = $stmt->fetchAll();

if (empty($configs)) {
    echo "{$log_prefix}No evening messages scheduled for $current_time.\n";
    exit;
}

foreach ($configs as $config) {
    $shakhaId = $config['shakha_id'];
    echo "{$log_prefix}Processing Shakha ID: $shakhaId ({$config['shakha_name']})\n";

    // Check if already sent today for evening
    $checkLog = $pdo->prepare("SELECT id FROM daily_message_log WHERE shakha_id = ? AND message_date = ? AND channel = 'whatsapp_evening'");
    $checkLog->execute([$shakhaId, $today]);
    if ($checkLog->fetch()) {
        echo "  - Evening Message already sent today. Skipping.\n";
        continue;
    }

    try {
        // Fetch panchang
        $panchang = \PanchangHelper::getForDate($pdo, $today, $shakhaId);

        // Fetch Amrit Vachan sequentially
        $lastId = $config['last_amritvachan_id'];
        
        $vachanStmt = $pdo->prepare("
            SELECT * FROM amrit_vachan 
            WHERE shakha_id = ? AND id > ? AND (is_deleted IS NULL OR is_deleted = 0)
            ORDER BY id ASC LIMIT 1
        ");
        $vachanStmt->execute([$shakhaId, $lastId]);
        $amritVachan = $vachanStmt->fetch();
        
        if (!$amritVachan) {
            // Loop back to the first one
            $vachanStmt->execute([$shakhaId, 0]);
            $amritVachan = $vachanStmt->fetch();
        }

        if (!$amritVachan) {
            echo "  - No Amrit Vachans found in database for shakha.\n";
            continue;
        }

        $logoPath = dirname(__DIR__) . '/' . ($config['logo'] ?: 'assets/images/logo.svg');
        $generator = new \App\Core\AmritVachanImageGenerator();
        $imagePath = $generator->generate($panchang, $amritVachan, $logoPath, $config['shakha_name']);

        $caption = "🚩 *{$config['shakha_name']}*\n\n📜 *आज का अमृत वचन*\n\n_{$amritVachan['content']}_\n" . ($amritVachan['author'] ? "— {$amritVachan['author']}" : "") . "\n\n🚩 _जय श्री राम | भारत माता की जय_";

        $wa = new \App\Core\WhatsAppService($config['whatsapp_api_instance'], $config['whatsapp_api_token']);
        $result = $wa->sendImageToGroup($config['whatsapp_group_id'], $imagePath, $caption);

        if ($result['success']) {
            echo "  - Successfully sent evening message to WhatsApp.\n";
            $pdo->prepare("
                INSERT INTO daily_message_log (shakha_id, message_date, channel, status, sent_at)
                VALUES (?, ?, 'whatsapp_evening', 'success', NOW())
            ")->execute([$shakhaId, $today]);

            // Update last_amritvachan_id
            $pdo->prepare("UPDATE daily_message_config SET last_amritvachan_id = ? WHERE shakha_id = ?")
                ->execute([$amritVachan['id'], $shakhaId]);
        } else {
            echo "  - Failed to send: " . $result['response'] . "\n";
            $pdo->prepare("
                INSERT INTO daily_message_log (shakha_id, message_date, channel, status, error_message, sent_at)
                VALUES (?, ?, 'whatsapp_evening', 'failed', ?, NOW())
            ")->execute([$shakhaId, $today, substr($result['response'], 0, 500)]);
        }

    } catch (\Throwable $e) {
        echo "  - Exception: " . $e->getMessage() . "\n";
        $pdo->prepare("
            INSERT INTO daily_message_log (shakha_id, message_date, channel, status, error_message, sent_at)
            VALUES (?, ?, 'whatsapp_evening', 'failed', ?, NOW())
        ")->execute([$shakhaId, $today, substr($e->getMessage(), 0, 500)]);
    }
}
echo "{$log_prefix}Job complete.\n";
