<?php
$pageTitle = 'गीत — राष्ट्रभक्ति एवं सांस्कृतिक गीत | संघस्थान';
$pageDesc = 'राष्ट्रभक्ति और सांस्कृतिक गीतों का संग्रह — एकल एवं सांघिक गीत, बोल एवं संदर्भ सहित।';
$pageCanonical = 'https://sanghasthan.yuktaa.com/geet/';
require_once __DIR__ . '/../includes/public_header.php';

$items = [];
try {
    $stmt = $pdo->query("SELECT g.*, sh.name as shakha_name FROM geet g LEFT JOIN shakhas sh ON g.shakha_id = sh.id ORDER BY g.geet_date DESC, g.created_at DESC");
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}
?>

<div class="public-header">
    <h1>🎵 गीत</h1>
    <p>राष्ट्रभक्ति और सांस्कृतिक गीतों का संग्रह — एकल एवं सांघिक गीत</p>
    <?php if (count($items)): ?>
        <span class="public-count">कुल <?php echo count($items); ?> गीत</span>
    <?php endif; ?>
</div>

<?php if (empty($items)): ?>
    <div class="content-empty">
        <div class="empty-icon">🎵</div>
        <p>अभी तक कोई गीत प्रकाशित नहीं हुआ है।</p>
        <p style="font-size:0.85rem;margin-top:8px;">मुख्य शिक्षक शाखा पैनल से गीत जोड़ सकते हैं।</p>
    </div>
<?php else: ?>
    <?php foreach ($items as $item): ?>
        <div class="content-item">
            <div class="content-item-title">
                <?php echo htmlspecialchars($item['title']); ?>
                <?php if (!empty($item['geet_type'])): ?>
                    <span class="content-item-type"><?php echo $item['geet_type'] === 'Ekal' ? 'एकल' : 'सांघिक'; ?></span>
                <?php endif; ?>
            </div>
            <?php if (!empty($item['lyrics'])): ?>
                <div class="content-item-lyrics"><?php echo nl2br(htmlspecialchars($item['lyrics'])); ?></div>
            <?php endif; ?>
            <?php if (!empty($item['meaning_or_context'])): ?>
                <div class="content-item-meaning" style="margin-top:12px;">
                    <span class="label">संदर्भ / अर्थ —</span>
                    <?php echo nl2br(htmlspecialchars($item['meaning_or_context'])); ?>
                </div>
            <?php endif; ?>
            <div class="content-item-meta">
                <span>📅 <?php echo date('d M Y', strtotime($item['geet_date'])); ?></span>
                <?php if (!empty($item['shakha_name'])): ?>
                    <span>🚩 <?php echo htmlspecialchars($item['shakha_name']); ?></span>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/public_footer.php'; ?>
