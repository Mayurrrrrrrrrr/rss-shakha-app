<?php
$pageTitle = 'घोषणाएं — प्रेरणादायक उद्घोष | संघस्थान';
$pageDesc = 'संस्कृत और हिंदी की प्रेरणादायक घोषणाओं का संकलन — शाखा एवं व्यक्तिगत जीवन हेतु।';
$pageCanonical = 'https://sanghasthan.yuktaa.com/ghoshnayein/';
require_once __DIR__ . '/../includes/public_header.php';

$items = [];
try {
    $stmt = $pdo->query("SELECT g.*, sh.name as shakha_name FROM ghoshnayein g LEFT JOIN shakhas sh ON g.shakha_id = sh.id ORDER BY g.ghoshna_date DESC, g.created_at DESC");
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}
?>

<div class="public-header">
    <h1>🗣️ घोषणाएं</h1>
    <p>संस्कृत और हिंदी की प्रेरणादायक घोषणाओं का संकलन</p>
    <?php if (count($items)): ?>
        <span class="public-count">कुल <?php echo count($items); ?> घोषणाएं</span>
    <?php endif; ?>
</div>

<?php if (empty($items)): ?>
    <div class="content-empty">
        <div class="empty-icon">🗣️</div>
        <p>अभी तक कोई घोषणा प्रकाशित नहीं हुई है।</p>
        <p style="font-size:0.85rem;margin-top:8px;">मुख्य शिक्षक शाखा पैनल से घोषणाएं जोड़ सकते हैं।</p>
    </div>
<?php else: ?>
    <?php foreach ($items as $item): ?>
        <div class="content-item">
            <?php if (!empty($item['slogan_sanskrit'])): ?>
                <div class="content-item-sanskrit"><?php echo nl2br(htmlspecialchars($item['slogan_sanskrit'])); ?></div>
            <?php endif; ?>
            <?php if (!empty($item['slogan_hindi'])): ?>
                <div class="content-item-meaning">
                    <span class="label">हिंदी उद्घोष —</span>
                    <?php echo nl2br(htmlspecialchars($item['slogan_hindi'])); ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($item['context'])): ?>
                <div class="content-item-meaning" style="margin-top:12px; background: rgba(46, 125, 50, 0.05);">
                    <span class="label" style="color: var(--green-deep);">संदर्भ / अर्थ —</span>
                    <?php echo nl2br(htmlspecialchars($item['context'])); ?>
                </div>
            <?php endif; ?>
            <div class="content-item-meta">
                <span>📅 <?php echo date('d M Y', strtotime($item['ghoshna_date'])); ?></span>
                <?php if (!empty($item['shakha_name'])): ?>
                    <span>🚩 <?php echo htmlspecialchars($item['shakha_name']); ?></span>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/public_footer.php'; ?>
