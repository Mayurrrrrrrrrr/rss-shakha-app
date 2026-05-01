<?php
$pageTitle = 'सुभाषित — संस्कृत सूक्तियाँ | संघस्थान';
$pageDesc = 'संस्कृत के अमूल्य सुभाषितों का संग्रह — जीवन मार्गदर्शन हेतु सूक्तियाँ, अर्थ एवं शब्दार्थ सहित।';
$pageCanonical = 'https://sanghasthan.yuktaa.com/subhashit/';
require_once __DIR__ . '/../includes/public_header.php';

// Fetch all subhashits
$items = [];
try {
    $stmt = $pdo->query("SELECT s.*, sh.name as shakha_name FROM subhashits s LEFT JOIN shakhas sh ON s.shakha_id = sh.id ORDER BY s.subhashit_date DESC, s.created_at DESC");
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}
?>

<div class="public-header">
    <h1>✨ सुभाषित</h1>
    <p>संस्कृत के अमूल्य सूक्तियों का संग्रह — जीवन के हर पहलू पर मार्गदर्शन</p>
    <?php if (count($items)): ?>
        <span class="public-count">कुल <?php echo count($items); ?> सुभाषित</span>
    <?php endif; ?>
</div>

<?php if (empty($items)): ?>
    <div class="content-empty">
        <div class="empty-icon">✨</div>
        <p>अभी तक कोई सुभाषित प्रकाशित नहीं हुआ है।</p>
        <p style="font-size:0.85rem;margin-top:8px;">मुख्य शिक्षक शाखा पैनल से सुभाषित जोड़ सकते हैं।</p>
    </div>
<?php else: ?>
    <?php foreach ($items as $item): ?>
        <div class="content-item">
            <?php if (!empty($item['sanskrit_text'])): ?>
                <div class="content-item-sanskrit"><?php echo nl2br(htmlspecialchars($item['sanskrit_text'])); ?></div>
            <?php endif; ?>
            <?php if (!empty($item['hindi_meaning'])): ?>
                <div class="content-item-meaning">
                    <span class="label">हिंदी अर्थ —</span>
                    <?php echo nl2br(htmlspecialchars($item['hindi_meaning'])); ?>
                </div>
            <?php endif; ?>
            <?php
            // Shabdarth
            if (!empty($item['shabdarth'])) {
                $shabdarth = json_decode($item['shabdarth'], true);
                if ($shabdarth && is_array($shabdarth) && count($shabdarth)):
            ?>
                <table class="shabdarth-table">
                    <thead><tr><th>शब्द</th><th>अर्थ</th></tr></thead>
                    <tbody>
                    <?php foreach ($shabdarth as $s): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($s['word'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($s['meaning'] ?? ''); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; } ?>
            <div class="content-item-meta">
                <span>📅 <?php echo date('d M Y', strtotime($item['subhashit_date'])); ?></span>
                <?php if (!empty($item['panchang_text'])): ?>
                    <span>🕉️ <?php echo htmlspecialchars($item['panchang_text']); ?></span>
                <?php endif; ?>
                <?php if (!empty($item['shakha_name'])): ?>
                    <span>🚩 <?php echo htmlspecialchars($item['shakha_name']); ?></span>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/public_footer.php'; ?>
