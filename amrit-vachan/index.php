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

function getVachanHtml($item) {
    $contentHtml = '<div class="content-item-sanskrit" style="font-family: \'Noto Sans Devanagari\', sans-serif; color: var(--ink); background: rgba(255, 109, 0, 0.03); border-left-color: var(--saffron); font-size: 1.3rem; line-height: 1.8;">' . nl2br(htmlspecialchars($item['content'])) . '</div>';
    if (!empty($item['author'])) {
        $contentHtml .= '<div style="text-align: right; margin-top: -8px; margin-bottom: 20px; font-weight: 700; color: var(--saffron); font-size: 1.1rem;">— ' . htmlspecialchars($item['author']) . '</div>';
    }
    
    $meta = '📅 ' . date('d M Y', strtotime($item['vachan_date']));
    if (!empty($item['shakha_name'])) $meta .= ' | 🚩 ' . htmlspecialchars($item['shakha_name']);
    $contentHtml .= '<div class="content-item-meta" style="margin-top:24px;">' . $meta . '</div>';

    // WhatsApp Share Format
    $shareText = "💎 *अमृत वचन* 💎\n\n\"" . $item['content'] . "\"\n\n— " . ($item['author'] ?? 'प्रेरक विचार') . "\n\n🚩 *संघस्थान* — सुविचार एवं मार्गदर्शन";
    $waLink = "https://api.whatsapp.com/send?text=" . urlencode($shareText);
    
    $contentHtml .= '<div style="margin-top:30px; text-align:center;"><a href="'.$waLink.'" target="_blank" class="share-btn-wa" style="background:#25D366; color:white; text-decoration:none; padding:12px 24px; border-radius:30px; display:inline-flex; align-items:center; gap:10px; font-weight:700; box-shadow: 0 4px 12px rgba(37,211,102,0.3);"><svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M12.012 2c-5.508 0-9.988 4.48-9.988 9.988 0 1.76.459 3.413 1.26 4.852l-1.34 4.896 5.01-1.316c1.408.766 3.013 1.204 4.718 1.204 5.508 0 9.988-4.48 9.988-9.988s-4.48-9.988-9.988-9.988zm6.596 14.152c-.273.766-1.353 1.433-2.222 1.543-.591.074-1.363.132-3.961-.933-3.322-1.363-5.464-4.738-5.63-4.956-.165-.219-1.339-1.782-1.339-3.411 0-1.63.847-2.43 1.15-2.76.303-.33.666-.412.889-.412.222 0 .444.004.639.013.199.008.468-.076.734.568.273.659.932 2.274 1.012 2.438.079.164.133.356.024.573-.109.219-.164.356-.328.547-.164.192-.345.426-.492.573-.165.164-.338.344-.145.679.193.336.858 1.413 1.838 2.285.98 0.872 1.808 1.144 2.138 1.309.33.164.52.14.714-.079.192-.219.824-.961 1.042-1.285.219-.328.437-.273.738-.164.301.109 1.913.902 2.24 1.066.328.164.547.245.628.383.082.138.082.802-.191 1.568z"/></svg> व्हाट्सएप पर साझा करें</a></div>';
    return $contentHtml;
}
?>

<div class="public-header">
    <h1>💎 अमृत वचन</h1>
    <p>मुख्य शिक्षकों एवं विचारकों द्वारा प्रेषित प्रेरणादायक विचार</p>
</div>

<?php if (empty($items)): ?>
    <div class="content-empty">
        <div class="empty-icon">💎</div>
        <p>अभी तक कोई अमृत वचन प्रकाशित नहीं हुआ है।</p>
    </div>
<?php else: ?>
    <?php 
        $latest = array_shift($items); 
        $latestHtml = getVachanHtml($latest);
    ?>
    
    <div class="featured-item" style="border-left:5px solid var(--saffron); border-right:none; border-top:none; border-bottom:none; background:white;">
        <div class="featured-badge" style="background:var(--ink);">नया सुविचार</div>
        <div class="featured-content" style="text-align:left; font-family:'Noto Sans Devanagari', sans-serif; font-style:italic;">
            "<?php echo nl2br(htmlspecialchars($latest['content'])); ?>"
        </div>
        <div style="text-align:right; font-weight:700; color:var(--saffron); margin-bottom:20px;">— <?php echo htmlspecialchars($latest['author'] ?? 'अज्ञात'); ?></div>
        <div style="text-align:center;">
            <button class="detail-link-btn" style="display:inline-block; width:auto; padding:10px 30px;" onclick="openSidePanel('अमृत वचन', `<?php echo addslashes($latestHtml); ?>`)">विस्तार से देखें एवं साझा करें</button>
        </div>
    </div>

    <h3 style="margin-bottom:20px; color:var(--ink-muted); font-size:1.1rem; border-bottom:1px solid var(--border-warm); padding-bottom:8px;">अन्य सुविचार</h3>
    <div class="archive-grid">
        <?php foreach ($items as $item): 
            $title = mb_substr($item['content'], 0, 80) . '...';
            $itemHtml = getVachanHtml($item);
        ?>
            <div class="grid-item" onclick="openSidePanel('अमृत वचन', `<?php echo addslashes($itemHtml); ?>`)">
                <div class="grid-item-title" style="font-family:'Noto Sans Devanagari', sans-serif; font-style:italic;">"<?php echo htmlspecialchars($title); ?>"</div>
                <div class="grid-item-meta">
                    📅 <?php echo date('d M Y', strtotime($item['vachan_date'])); ?>
                    <?php if (!empty($item['author'])): ?> | ✍️ <?php echo htmlspecialchars($item['author']); ?><?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/public_footer.php'; ?>
