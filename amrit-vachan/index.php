<?php
$pageTitle = 'अमृत वचन — प्रेरक विचार | संघस्थान';
$pageDesc = 'प्रमुख विचारकों एवं मुख्य शिक्षकों द्वारा प्रेषित प्रेरणादायक अमृत वचन एवं सुविचार।';
$pageCanonical = 'https://sanghasthan.yuktaa.com/amrit-vachan/';
require_once __DIR__ . '/../includes/public_header.php';

$items = [];
try {
    $stmt = $pdo->query("SELECT a.*, sh.name as shakha_name FROM amrit_vachan a LEFT JOIN shakhas sh ON a.shakha_id = sh.id ORDER BY a.vachan_date DESC, a.created_at DESC");
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}
?>

<div class="public-header">
    <h1>💎 अमृत वचन</h1>
    <p>मुख्य शिक्षकों एवं विचारकों द्वारा प्रेषित प्रेरणादायक विचार</p>
    <?php if (count($items)): ?>
        <span class="public-count">कुल <?php echo count($items); ?> अमृत वचन</span>
    <?php endif; ?>
</div>

<?php if (empty($items)): ?>
    <div class="content-empty">
        <div class="empty-icon">💎</div>
        <p>अभी तक कोई अमृत वचन प्रकाशित नहीं हुआ है।</p>
        <p style="font-size:0.85rem;margin-top:8px;">मुख्य शिक्षक शाखा पैनल से अमृत वचन जोड़ सकते हैं।</p>
    </div>
<?php else: ?>
    <?php foreach ($items as $item): ?>
        <div class="content-item">
            <div class="content-item-sanskrit" style="font-family: 'Noto Sans Devanagari', sans-serif; color: var(--ink); background: rgba(255, 109, 0, 0.03); border-left-color: var(--saffron);">
                <?php echo nl2br(htmlspecialchars($item['content'])); ?>
            </div>
            <?php if (!empty($item['author'])): ?>
                <div style="text-align: right; margin-top: -8px; margin-bottom: 16px; font-weight: 700; color: var(--saffron); font-size: 0.95rem;">
                    — <?php echo htmlspecialchars($item['author']); ?>
                </div>
            <?php endif; ?>
            <div class="content-item-meta">
                <span>📅 <?php echo date('d M Y', strtotime($item['vachan_date'])); ?></span>
                <?php if (!empty($item['shakha_name'])): ?>
                    <span>🚩 <?php echo htmlspecialchars($item['shakha_name']); ?></span>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/public_footer.php'; ?>
