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

function getGhoshnaHtml($item) {
    $contentHtml = '';
    if (!empty($item['slogan_sanskrit'])) {
        $contentHtml .= '<div class="content-item-sanskrit">' . nl2br(htmlspecialchars($item['slogan_sanskrit'])) . '</div>';
    }
    if (!empty($item['slogan_hindi'])) {
        $contentHtml .= '<div class="content-item-meaning"><span class="label">हिंदी उद्घोष —</span>' . nl2br(htmlspecialchars($item['slogan_hindi'])) . '</div>';
    }
    if (!empty($item['context'])) {
        $contentHtml .= '<div class="content-item-meaning" style="margin-top:24px; background:rgba(46,125,50,0.03);"><span class="label" style="color:var(--green-deep);">संदर्भ / भावार्थ —</span>' . nl2br(htmlspecialchars($item['context'])) . '</div>';
    }
    
    $meta = '📅 ' . date('d M Y', strtotime($item['ghoshna_date']));
    if (!empty($item['shakha_name'])) $meta .= ' | 🚩 ' . htmlspecialchars($item['shakha_name']);
    $contentHtml .= '<div class="content-item-meta" style="margin-top:24px;">' . $meta . '</div>';

    // WhatsApp Share Format
    $shareText = "🗣️ *" . ($item['slogan_sanskrit'] ?? 'घोषणा') . "*\n\n" . ($item['slogan_hindi'] ?? '') . "\n\n🚩 *संघस्थान*";
    $waLink = "https://api.whatsapp.com/send?text=" . urlencode($shareText);
    
    $contentHtml .= '<div style="margin-top:30px; text-align:center;"><a href="'.$waLink.'" target="_blank" class="share-btn-wa" style="background:#25D366; color:white; text-decoration:none; padding:12px 24px; border-radius:30px; display:inline-flex; align-items:center; gap:10px; font-weight:700; box-shadow: 0 4px 12px rgba(37,211,102,0.3);"><svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M12.012 2c-5.508 0-9.988 4.48-9.988 9.988 0 1.76.459 3.413 1.26 4.852l-1.34 4.896 5.01-1.316c1.408.766 3.013 1.204 4.718 1.204 5.508 0 9.988-4.48 9.988-9.988s-4.48-9.988-9.988-9.988zm6.596 14.152c-.273.766-1.353 1.433-2.222 1.543-.591.074-1.363.132-3.961-.933-3.322-1.363-5.464-4.738-5.63-4.956-.165-.219-1.339-1.782-1.339-3.411 0-1.63.847-2.43 1.15-2.76.303-.33.666-.412.889-.412.222 0 .444.004.639.013.199.008.468-.076.734.568.273.659.932 2.274 1.012 2.438.079.164.133.356.024.573-.109.219-.164.356-.328.547-.164.192-.345.426-.492.573-.165.164-.338.344-.145.679.193.336.858 1.413 1.838 2.285.98 0.872 1.808 1.144 2.138 1.309.33.164.52.14.714-.079.192-.219.824-.961 1.042-1.285.219-.328.437-.273.738-.164.301.109 1.913.902 2.24 1.066.328.164.547.245.628.383.082.138.082.802-.191 1.568z"/></svg> साझा करें</a></div>';
    return $contentHtml;
}
?>

<div class="public-header">
    <h1>🗣️ घोषणाएं</h1>
    <p>प्रेरणादायक उद्घोषों का संग्रह</p>
</div>

<?php if (empty($items)): ?>
    <div class="content-empty">
        <div class="empty-icon">🗣️</div>
        <p>अभी तक कोई घोषणा प्रकाशित नहीं हुआ है।</p>
    </div>
<?php else: ?>
    <div class="list-container">
        <?php foreach ($items as $index => $item): 
            $title = !empty($item['slogan_sanskrit']) ? mb_substr($item['slogan_sanskrit'], 0, 80) . '...' : 'घोषणा';
            $itemHtml = getGhoshnaHtml($item);
            $isLatest = ($index === 0);
        ?>
            <div class="list-item" style="<?php echo $isLatest ? 'border-left:4px solid var(--saffron); background:var(--saffron-glow);' : ''; ?>" 
                 onclick="openSidePanel('घोषणा', <?php echo htmlspecialchars(json_encode($itemHtml)); ?>)">
                <div class="list-item-title">
                    <?php if($isLatest): ?><span style="color:var(--saffron); font-size:0.7rem; display:block; margin-bottom:4px;">नवीनतम</span><?php endif; ?>
                    <?php echo htmlspecialchars($title); ?>
                </div>
                <div class="list-item-arrow"> विस्तार से देखें →</div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/public_footer.php'; ?>
