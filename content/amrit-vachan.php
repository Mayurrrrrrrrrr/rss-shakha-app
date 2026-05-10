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
    $currentUrl = "https://sanghasthan.yuktaa.com/amrit-vachan/#item-" . $item['id'];
    $shareText = "💎 *अमृत वचन* 💎\n\n\"" . $item['content'] . "\"\n\n— " . ($item['author'] ?? 'प्रेरक विचार') . "\n\nविस्तार से पढ़ें: " . $currentUrl . "\n\n🚩 *संघस्थान*";
    $waLink = "https://api.whatsapp.com/send?text=" . urlencode($shareText);
    
    $contentHtml .= '<div style="margin-top:30px; text-align:center;"><a href="'.$waLink.'" target="_blank" class="share-btn-wa" style="background:#25D366; color:white; text-decoration:none; padding:12px 24px; border-radius:30px; display:inline-flex; align-items:center; gap:10px; font-weight:700; box-shadow: 0 4px 12px rgba(37,211,102,0.3);"><svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M12.012 2c-5.508 0-9.988 4.48-9.988 9.988 0 1.76.459 3.413 1.26 4.852l-1.34 4.896 5.01-1.316c1.408.766 3.013 1.204 4.718 1.204 5.508 0 9.988-4.48 9.988-9.988s-4.48-9.988-9.988-9.988zm6.596 14.152c-.273.766-1.353 1.433-2.222 1.543-.591.074-1.363.132-3.961-.933-3.322-1.363-5.464-4.738-5.63-4.956-.165-.219-1.339-1.782-1.339-3.411 0-1.63.847-2.43 1.15-2.76.303-.33.666-.412.889-.412.222 0 .444.004.639.013.199.008.468-.076.734.568.273.659.932 2.274 1.012 2.438.079.164.133.356.024.573-.109.219-.164.356-.328.547-.164.192-.345.426-.492.573-.165.164-.338.344-.145.679.193.336.858 1.413 1.838 2.285.98 0.872 1.808 1.144 2.138 1.309.33.164.52.14.714-.079.192-.219.824-.961 1.042-1.285.219-.328.437-.273.738-.164.301.109 1.913.902 2.24 1.066.328.164.547.245.628.383.082.138.082.802-.191 1.568z"/></svg> साझा करें</a></div>';
    return $contentHtml;
}
?>
<style>
    .av-header {
        text-align: center;
        padding: 60px 20px 40px;
        background: linear-gradient(to bottom, var(--saffron-glow), transparent);
    }
    .av-title {
        font-size: clamp(2rem, 4vw, 3rem);
        color: var(--saffron);
        font-weight: 800;
        margin-bottom: 10px;
    }
    .av-subtitle {
        color: var(--ink-light);
        font-size: 1.1rem;
    }
    .av-masonry {
        column-count: 3;
        column-gap: 24px;
        padding: 20px 4vw 60px;
        max-width: 1400px;
        margin: 0 auto;
    }
    @media (max-width: 1024px) { .av-masonry { column-count: 2; } }
    @media (max-width: 600px) { .av-masonry { column-count: 1; } }

    .av-card {
        break-inside: avoid;
        background: #fff;
        border-radius: 20px;
        padding: 30px;
        margin-bottom: 24px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.03);
        border: 1px solid rgba(255,107,0,0.1);
        position: relative;
        overflow: hidden;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .av-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 40px rgba(255,107,0,0.1);
    }
    .av-quote-mark {
        position: absolute;
        top: -10px;
        left: 20px;
        font-size: 8rem;
        color: rgba(255,107,0,0.05);
        font-family: serif;
        line-height: 1;
        pointer-events: none;
    }
    .av-content {
        font-family: 'Noto Sans Devanagari', sans-serif;
        font-size: 1.25rem;
        line-height: 1.7;
        color: var(--ink);
        position: relative;
        z-index: 1;
        margin-bottom: 20px;
    }
    .av-author {
        text-align: right;
        font-weight: 700;
        color: var(--saffron);
        font-size: 1.05rem;
        margin-bottom: 15px;
    }
    .av-meta {
        font-size: 0.85rem;
        color: var(--text-muted);
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-top: 1px solid rgba(0,0,0,0.05);
        padding-top: 15px;
    }
    .av-share-btn {
        background: rgba(37,211,102,0.1);
        color: #25D366;
        border: none;
        padding: 8px 12px;
        border-radius: 20px;
        text-decoration: none;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 0.85rem;
        transition: all 0.2s;
    }
    .av-share-btn:hover {
        background: #25D366;
        color: white;
    }
</style>

<div class="av-header">
    <h1 class="av-title">💎 अमृत वचन</h1>
    <p class="av-subtitle">प्रमुख विचारकों एवं मनीषियों द्वारा प्रेषित प्रेरणादायक सुविचार</p>
</div>

<?php if (empty($items)): ?>
    <div style="text-align:center; padding: 100px 20px; color: var(--text-muted);">
        <p>अभी तक कोई अमृत वचन प्रकाशित नहीं हुआ है।</p>
    </div>
<?php else: ?>
    <div class="av-masonry">
        <?php foreach ($items as $item): ?>
            <div class="av-card" id="item-<?php echo $item['id']; ?>">
                <div class="av-quote-mark">"</div>
                <div class="av-content">
                    <?php echo nl2br(htmlspecialchars($item['content'])); ?>
                </div>
                
                <?php if (!empty($item['author'])): ?>
                    <div class="av-author">— <?php echo htmlspecialchars($item['author']); ?></div>
                <?php endif; ?>
                
                <div class="av-meta">
                    <div>
                        📅 <?php echo date('d M Y', strtotime($item['vachan_date'])); ?>
                        <?php if (!empty($item['shakha_name'])): ?>
                            <br>🚩 <?php echo htmlspecialchars($item['shakha_name']); ?>
                        <?php endif; ?>
                    </div>
                    <?php
                    $currentUrl = "https://sanghasthan.yuktaa.com/content/index.php?page=amrit-vachan#item-" . $item['id'];
                    $shareText = "💎 *अमृत वचन* 💎\n\n\"" . $item['content'] . "\"\n\n— " . ($item['author'] ?? 'प्रेरक विचार') . "\n\nविस्तार से पढ़ें: " . $currentUrl . "\n\n🚩 *संघस्थान*";
                    $waLink = "https://api.whatsapp.com/send?text=" . urlencode($shareText);
                    ?>
                    <a href="<?php echo $waLink; ?>" target="_blank" class="av-share-btn">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><path d="M12.012 2c-5.508 0-9.988 4.48-9.988 9.988 0 1.76.459 3.413 1.26 4.852l-1.34 4.896 5.01-1.316c1.408.766 3.013 1.204 4.718 1.204 5.508 0 9.988-4.48 9.988-9.988s-4.48-9.988-9.988-9.988zm6.596 14.152c-.273.766-1.353 1.433-2.222 1.543-.591.074-1.363.132-3.961-.933-3.322-1.363-5.464-4.738-5.63-4.956-.165-.219-1.339-1.782-1.339-3.411 0-1.63.847-2.43 1.15-2.76.303-.33.666-.412.889-.412.222 0 .444.004.639.013.199.008.468-.076.734.568.273.659.932 2.274 1.012 2.438.079.164.133.356.024.573-.109.219-.164.356-.328.547-.164.192-.345.426-.492.573-.165.164-.338.344-.145.679.193.336.858 1.413 1.838 2.285.98 0.872 1.808 1.144 2.138 1.309.33.164.52.14.714-.079.192-.219.824-.961 1.042-1.285.219-.328.437-.273.738-.164.301.109 1.913.902 2.24 1.066.328.164.547.245.628.383.082.138.082.802-.191 1.568z"/></svg> Share
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/public_footer.php'; ?>
