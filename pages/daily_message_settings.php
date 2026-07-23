<?php
require_once '../includes/auth.php';
require_once '../config/db.php';
requireLogin();
if (!isMukhyashikshak() && !isAdmin()) {
    header('Location: ../pages/dashboard.php');
    exit;
}

$shakhaId = getCurrentShakhaId();
$msg = $_GET['msg'] ?? '';

// Fetch or create config
$stmt = $pdo->prepare("SELECT * FROM daily_message_config WHERE shakha_id = ?");
$stmt->execute([$shakhaId]);
$config = $stmt->fetch();

if (!$config) {
    $pdo->prepare("INSERT INTO daily_message_config (shakha_id) VALUES (?)")->execute([$shakhaId]);
    $stmt->execute([$shakhaId]);
    $config = $stmt->fetch();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';
    
    if ($action === 'save') {
        $whatsappEnabled = isset($_POST['whatsapp_enabled']) ? 1 : 0;
        $apiInstance = trim($_POST['whatsapp_api_instance'] ?? '');
        $apiToken = trim($_POST['whatsapp_api_token'] ?? '');
        $groupId = trim($_POST['whatsapp_group_id'] ?? '');
        $sendTime = $_POST['send_time'] ?? '06:00:00';

        $pdo->prepare("
            UPDATE daily_message_config 
            SET whatsapp_enabled = ?, whatsapp_api_instance = ?, whatsapp_api_token = ?, 
                whatsapp_group_id = ?, send_time = ?
            WHERE shakha_id = ?
        ")->execute([$whatsappEnabled, $apiInstance, $apiToken, $groupId, $sendTime, $shakhaId]);

        header('Location: daily_message_settings.php?msg=saved');
        exit;
    }
    
    if ($action === 'test_status') {
        // Test WhatsApp connection
        require_once '../app/Core/Autoloader.php';
        \App\Core\Autoloader::register();
        
        $wa = new \App\Core\WhatsAppService($config['whatsapp_api_instance'], $config['whatsapp_api_token']);
        $status = $wa->checkStatus();
        $testResult = $status['authorized'] ? '✅ WhatsApp कनेक्शन सफल!' : '❌ कनेक्शन विफल: ' . $status['response'];
    }

    if ($action === 'fetch_groups') {
        require_once '../app/Core/Autoloader.php';
        \App\Core\Autoloader::register();

        $wa = new \App\Core\WhatsAppService($config['whatsapp_api_instance'], $config['whatsapp_api_token']);
        $groups = $wa->getGroups();
    }

    if ($action === 'test_send') {
        // Generate and send a test creative
        require_once '../app/Core/Autoloader.php';
        \App\Core\Autoloader::register();
        define('BASE_PATH', dirname(__DIR__));
        require_once '../includes/PanchangHelper.php';
        \App\Core\DB::init($pdo);

        $today = date('Y-m-d');
        
        // Fetch shakha details
        $shakha = $pdo->prepare("SELECT * FROM shakhas WHERE id = ?");
        $shakha->execute([$shakhaId]);
        $shakha = $shakha->fetch();

        // Fetch panchang
        $panchang = \PanchangHelper::getForDate($pdo, $today, $shakhaId);

        // Fetch a random subhashit
        $sub = $pdo->prepare("SELECT * FROM subhashits WHERE shakha_id = ? AND (is_deleted IS NULL OR is_deleted = 0) ORDER BY RAND() LIMIT 1");
        $sub->execute([$shakhaId]);
        $subhashit = $sub->fetch();

        try {
            $logoPath = dirname(__DIR__) . '/' . ($shakha['logo'] ?: 'assets/images/logo.svg');
            
            $generator = new \App\Core\ImageGenerator();
            $imagePath = $generator->generate($panchang, $subhashit ?: null, $logoPath, $shakha['name']);

            if (!empty($config['whatsapp_api_instance']) && !empty($config['whatsapp_api_token']) && !empty($config['whatsapp_group_id'])) {
                // Build caption
                $caption = buildTestCaption($panchang, $subhashit, $shakha['name']);
                
                $wa = new \App\Core\WhatsAppService($config['whatsapp_api_instance'], $config['whatsapp_api_token']);
                $result = $wa->sendImageToGroup($config['whatsapp_group_id'], $imagePath, $caption);
                $testResult = $result['success'] ? '✅ टेस्ट संदेश सफलतापूर्वक भेजा गया!' : '❌ भेजने में त्रुटि: ' . $result['response'];
            } else {
                $testResult = '✅ इमेज बनाई गई: ' . basename($imagePath) . ' (WhatsApp credentials नहीं हैं, इसलिए भेजा नहीं गया)';
            }
            $testImagePath = $imagePath;
        } catch (\Throwable $e) {
            $testResult = '❌ टेस्ट फेल: ' . $e->getMessage();
        }
    }
}

// Fetch recent logs
$logs = $pdo->prepare("
    SELECT * FROM daily_message_log 
    WHERE shakha_id = ? 
    ORDER BY sent_at DESC 
    LIMIT 14
");
$logs->execute([$shakhaId]);
$recentLogs = $logs->fetchAll();

function buildTestCaption($panchang, $subhashit, $shakhaName) {
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
    if (!empty($panchang['utsav'])) { $lines[] = ""; $lines[] = "🎉 *{$panchang['utsav']}*"; }
    if ($subhashit) {
        $lines[] = ""; $lines[] = "📜 *आज का सुभाषित:*";
        $lines[] = "_{$subhashit['sanskrit_text']}_";
        if (!empty($subhashit['hindi_meaning'])) $lines[] = "अर्थ: {$subhashit['hindi_meaning']}";
    }
    $lines[] = ""; $lines[] = "🚩 _जय श्री राम | भारत माता की जय_";
    return implode("\n", $lines);
}

$pageTitle = 'दैनिक संदेश सेटिंग्स';
require_once '../includes/header.php';
?>

<div class="page-header">
    <h1>📩 दैनिक संदेश सेटिंग्स (Daily Message)</h1>
</div>

<?php if ($msg === 'saved'): ?>
    <div class="alert alert-success">✅ सेटिंग्स सफलतापूर्वक सहेजी गई!</div>
<?php endif; ?>

<?php if (!empty($testResult)): ?>
    <div class="alert <?= strpos($testResult, '✅') !== false ? 'alert-success' : 'alert-danger' ?>">
        <?= htmlspecialchars($testResult) ?>
    </div>
<?php endif; ?>

<?php if (!empty($testImagePath) && file_exists($testImagePath)): ?>
    <div class="card" style="margin-bottom: 20px;">
        <div class="card-header">🖼️ जनरेट की गई इमेज (Preview)</div>
        <div style="padding: 16px; text-align: center;">
            <img src="../storage/creatives/<?= basename($testImagePath) ?>" alt="Daily Creative" 
                 style="max-width: 400px; width: 100%; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.3);">
        </div>
    </div>
<?php endif; ?>

<!-- WhatsApp Configuration -->
<div class="card premium-card">
    <div class="card-header premium-header">
        <h2 style="margin: 0; font-size: 1.25rem;">📱 WhatsApp कॉन्फ़िगरेशन</h2>
    </div>
    <form method="POST">
        <input type="hidden" name="action" value="save">
        <div class="card-body">
            <div class="alert alert-warning" style="border-radius: var(--radius-md); font-size: 0.95rem; line-height: 1.5;">
                <h4 style="margin-top: 0; color: var(--saffron);"><i class="fas fa-info-circle"></i> सेटअप के चरण:</h4>
                <ol style="margin-bottom: 0; padding-left: 20px;">
                    <li><a href="https://green-api.com" target="_blank" style="color: var(--saffron); font-weight: bold; text-decoration: underline;">green-api.com</a> पर जाएं और फ्री ट्रायल अकाउंट बनाएं।</li>
                    <li>QR कोड स्कैन करके अपना WhatsApp कनेक्ट करें।</li>
                    <li>Instance ID और API Token नीचे डालें।</li>
                    <li>"ग्रुप लिस्ट लाएं" बटन से अपना ग्रुप चुनें।</li>
                </ol>
            </div>

            <div class="form-group" style="margin-bottom: 24px; padding: 12px; background: var(--bg-secondary); border-radius: var(--radius-sm); border: 1px solid var(--border-light);">
                <label style="display: flex; align-items: center; cursor: pointer; margin: 0; font-weight: 600; color: var(--text-primary);">
                    <input type="checkbox" name="whatsapp_enabled" value="1" <?= $config['whatsapp_enabled'] ? 'checked' : '' ?> style="width: 20px; height: 20px; margin-right: 12px; accent-color: var(--saffron);">
                    WhatsApp दैनिक संदेश सक्रिय करें
                </label>
            </div>

            <div class="settings-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                <div class="form-group">
                    <label for="whatsapp_api_instance" class="form-label">Instance ID (Green API)</label>
                    <input type="text" id="whatsapp_api_instance" name="whatsapp_api_instance" class="form-control premium-input" 
                           placeholder="e.g., 1101234567" value="<?= htmlspecialchars($config['whatsapp_api_instance'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="whatsapp_api_token" class="form-label">API Token</label>
                    <input type="text" id="whatsapp_api_token" name="whatsapp_api_token" class="form-control premium-input" 
                           placeholder="e.g., abc123def456..." value="<?= htmlspecialchars($config['whatsapp_api_token'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="whatsapp_group_id" class="form-label">WhatsApp Group ID</label>
                    <input type="text" id="whatsapp_group_id" name="whatsapp_group_id" class="form-control premium-input" 
                           placeholder="e.g., 120363XXXXXXXXX@g.us" value="<?= htmlspecialchars($config['whatsapp_group_id'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="send_time" class="form-label">भेजने का समय (IST)</label>
                    <input type="time" id="send_time" name="send_time" class="form-control premium-input" 
                           value="<?= htmlspecialchars($config['send_time'] ?? '06:00') ?>">
                </div>
            </div>

            <div class="form-actions" style="margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--border-light); display: flex; gap: 12px; flex-wrap: wrap;">
                <button type="submit" class="btn btn-primary" style="flex: 1; min-width: 200px;">
                    <i class="fas fa-save"></i> सेटिंग्स सहेजें
                </button>
            </div>
        </div>
    </form>

    <div class="card-footer" style="background: var(--bg-secondary); padding: 16px 20px; border-top: 1px solid var(--border-light); border-radius: 0 0 var(--radius-lg) var(--radius-lg);">
        <div class="d-flex gap-2" style="flex-wrap: wrap;">
            <form method="POST" style="flex: 1; min-width: 150px;">
                <input type="hidden" name="action" value="test_status">
                <button type="submit" class="btn btn-outline" style="width: 100%;">
                    <i class="fas fa-link"></i> कनेक्शन जांचें
                </button>
            </form>
            <form method="POST" style="flex: 1; min-width: 150px;">
                <input type="hidden" name="action" value="fetch_groups">
                <button type="submit" class="btn btn-outline" style="width: 100%;">
                    <i class="fas fa-users"></i> ग्रुप लिस्ट लाएं
                </button>
            </form>
            <form method="POST" style="flex: 1; min-width: 150px;">
                <input type="hidden" name="action" value="test_send">
                <button type="submit" class="btn btn-primary" style="width: 100%; background: linear-gradient(135deg, #4CAF50, #45a049); border: none;">
                    <i class="fas fa-paper-plane"></i> टेस्ट संदेश भेजें
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Groups List (if fetched) -->
<?php if (!empty($groups)): ?>
<div class="card" style="margin-top: 16px;">
    <div class="card-header">📋 आपके WhatsApp ग्रुप</div>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ग्रुप का नाम</th>
                    <th>Group ID</th>
                    <th>कार्रवाई</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($groups as $group): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($group['name']) ?></strong></td>
                    <td><code style="font-size: 0.85rem;"><?= htmlspecialchars($group['id']) ?></code></td>
                    <td>
                        <button class="btn btn-sm btn-primary" onclick="document.getElementById('whatsapp_group_id').value='<?= htmlspecialchars($group['id']) ?>'; window.scrollTo(0,0); alert('Group ID सेट कर दिया गया है। अब सेटिंग्स सहेजें बटन दबाएं।');">
                            ✅ चुनें
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Recent Logs -->
<?php if (!empty($recentLogs)): ?>
<div class="card" style="margin-top: 16px;">
    <div class="card-header">📊 हाल की डिलीवरी (Last 14 Days)</div>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>तारीख</th>
                    <th>चैनल</th>
                    <th>स्थिति</th>
                    <th>भेजा गया</th>
                    <th>त्रुटि</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentLogs as $log): ?>
                <tr>
                    <td><?= htmlspecialchars($log['message_date']) ?></td>
                    <td><?= $log['channel'] === 'whatsapp' ? '📱 WhatsApp' : '📧 Email' ?></td>
                    <td>
                        <?php if ($log['status'] === 'success'): ?>
                            <span style="color: #4CAF50; font-weight: bold;">✅ सफल</span>
                        <?php else: ?>
                            <span style="color: #f44336; font-weight: bold;">❌ विफल</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($log['sent_at']) ?></td>
                    <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?= htmlspecialchars($log['error_message'] ?? '-') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
