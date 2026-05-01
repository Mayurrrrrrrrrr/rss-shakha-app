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
    </div>
<?php else: ?>
    <div class="list-container">
        <?php foreach ($items as $item): 
            $contentHtml = '<div class="content-item-title" style="font-size:1.5rem; margin-bottom:20px;">' . htmlspecialchars($item['title']) . '</div>';
            if (!empty($item['geet_type'])) {
                $contentHtml .= '<div style="margin-bottom:16px;"><span class="content-item-type">' . ($item['geet_type'] === 'Ekal' ? 'एकल' : 'सांघिक') . '</span></div>';
            }
            if (!empty($item['lyrics'])) {
                $contentHtml .= '<div class="content-item-lyrics">' . nl2br(htmlspecialchars($item['lyrics'])) . '</div>';
            }
            if (!empty($item['meaning_or_context'])) {
                $contentHtml .= '<div class="content-item-meaning" style="margin-top:20px;"><span class="label">संदर्भ / अर्थ —</span>' . nl2br(htmlspecialchars($item['meaning_or_context'])) . '</div>';
            }
            
            $meta = '📅 ' . date('d M Y', strtotime($item['geet_date']));
            if (!empty($item['shakha_name'])) $meta .= ' | 🚩 ' . htmlspecialchars($item['shakha_name']);
            $contentHtml .= '<div class="content-item-meta" style="margin-top:20px;">' . $meta . '</div>';

            // WhatsApp Share
            $shareText = "🎵 *" . $item['title'] . "*\n\n" . mb_substr($item['lyrics'], 0, 100) . "...\n\nसौजन्य: संघस्थान";
            $waLink = "https://api.whatsapp.com/send?text=" . urlencode($shareText);
            $contentHtml .= '<div style="margin-top:30px; text-align:center;"><a href="'.$waLink.'" target="_blank" class="share-btn-wa" style="background:#25D366; color:white; text-decoration:none; padding:8px 16px; border-radius:20px; display:inline-flex; align-items:center; gap:8px; font-size:0.9rem;"><svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M12.012 2c-5.508 0-9.988 4.48-9.988 9.988 0 1.76.459 3.413 1.26 4.852l-1.34 4.896 5.01-1.316c1.408.766 3.013 1.204 4.718 1.204 5.508 0 9.988-4.48 9.988-9.988s-4.48-9.988-9.988-9.988zm6.596 14.152c-.273.766-1.353 1.433-2.222 1.543-.591.074-1.363.132-3.961-.933-3.322-1.363-5.464-4.738-5.63-4.956-.165-.219-1.339-1.782-1.339-3.411 0-1.63.847-2.43 1.15-2.76.303-.33.666-.412.889-.412.222 0 .444.004.639.013.199.008.468-.076.734.568.273.659.932 2.274 1.012 2.438.079.164.133.356.024.573-.109.219-.164.356-.328.547-.164.192-.345.426-.492.573-.165.164-.338.344-.145.679.193.336.858 1.413 1.838 2.285.98 0.872 1.808 1.144 2.138 1.309.33.164.52.14.714-.079.192-.219.824-.961 1.042-1.285.219-.328.437-.273.738-.164.301.109 1.913.902 2.24 1.066.328.164.547.245.628.383.082.138.082.802-.191 1.568z"/></svg> शेयर करें</a></div>';
        ?>
            <div class="list-item" onclick="openSidePanel('गीत', `<?php echo addslashes($contentHtml); ?>`)">
                <div class="list-item-title"><?php echo htmlspecialchars($item['title']); ?></div>
                <div class="list-item-arrow"> बोल एवं अर्थ देखें →</div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/public_footer.php'; ?>
