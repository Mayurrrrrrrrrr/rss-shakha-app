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

function getSubhashitHtml($item) {
    $contentHtml = '';
    if (!empty($item['sanskrit_text'])) {
        $contentHtml .= '<div class="content-item-sanskrit">' . nl2br(htmlspecialchars($item['sanskrit_text'])) . '</div>';
    }
    if (!empty($item['hindi_meaning'])) {
        $contentHtml .= '<div class="content-item-meaning"><span class="label">हिंदी अर्थ —</span>' . nl2br(htmlspecialchars($item['hindi_meaning'])) . '</div>';
    }
    
    // Shabdarth
    if (!empty($item['shabdarth'])) {
        $shabdarth = json_decode($item['shabdarth'], true);
        if ($shabdarth && is_array($shabdarth) && count($shabdarth)) {
            $contentHtml .= '<div style="margin-top:20px;"><span class="label" style="font-weight:700; color:var(--saffron); font-size:0.8rem;">शब्दार्थ:</span><table class="shabdarth-table"><tbody>';
            foreach ($shabdarth as $s) {
                $contentHtml .= '<tr><td>' . htmlspecialchars($s['word'] ?? '') . '</td><td>' . htmlspecialchars($s['meaning'] ?? '') . '</td></tr>';
            }
            $contentHtml .= '</tbody></table></div>';
        }
    }
    
    $meta = '📅 ' . date('d M Y', strtotime($item['subhashit_date']));
    if (!empty($item['panchang_text'])) $meta .= ' | 🕉️ ' . htmlspecialchars($item['panchang_text']);
    if (!empty($item['shakha_name'])) $meta .= ' | 🚩 ' . htmlspecialchars($item['shakha_name']);
    
    $contentHtml .= '<div class="content-item-meta" style="margin-top:24px;">' . $meta . '</div>';
    
    // WhatsApp Share Format
    $shareText = "✨ *आज का सुभाषित* ✨\n\n" . $item['sanskrit_text'] . "\n\n*अर्थ:* " . $item['hindi_meaning'] . "\n\n🚩 *संघस्थान* — संस्कृति एवं संस्कार";
    $waLink = "https://api.whatsapp.com/send?text=" . urlencode($shareText);
    
    $contentHtml .= '<div style="margin-top:30px; text-align:center;"><a href="'.$waLink.'" target="_blank" class="share-btn-wa" style="background:#25D366; color:white; text-decoration:none; padding:12px 24px; border-radius:30px; display:inline-flex; align-items:center; gap:10px; font-weight:700; box-shadow: 0 4px 12px rgba(37,211,102,0.3);"><svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M12.012 2c-5.508 0-9.988 4.48-9.988 9.988 0 1.76.459 3.413 1.26 4.852l-1.34 4.896 5.01-1.316c1.408.766 3.013 1.204 4.718 1.204 5.508 0 9.988-4.48 9.988-9.988s-4.48-9.988-9.988-9.988zm6.596 14.152c-.273.766-1.353 1.433-2.222 1.543-.591.074-1.363.132-3.961-.933-3.322-1.363-5.464-4.738-5.63-4.956-.165-.219-1.339-1.782-1.339-3.411 0-1.63.847-2.43 1.15-2.76.303-.33.666-.412.889-.412.222 0 .444.004.639.013.199.008.468-.076.734.568.273.659.932 2.274 1.012 2.438.079.164.133.356.024.573-.109.219-.164.356-.328.547-.164.192-.345.426-.492.573-.165.164-.338.344-.145.679.193.336.858 1.413 1.838 2.285.98 0.872 1.808 1.144 2.138 1.309.33.164.52.14.714-.079.192-.219.824-.961 1.042-1.285.219-.328.437-.273.738-.164.301.109 1.913.902 2.24 1.066.328.164.547.245.628.383.082.138.082.802-.191 1.568z"/></svg> व्हाट्सएप पर साझा करें</a></div>';
    return $contentHtml;
}
?>

<div class="public-header">
    <h1>✨ सुभाषित</h1>
    <p>संस्कृत के अमूल्य सूक्तियों का संग्रह — जीवन के हर पहलू पर मार्गदर्शन</p>
</div>

<?php if (empty($items)): ?>
    <div class="content-empty">
        <div class="empty-icon">✨</div>
        <p>अभी तक कोई सुभाषित प्रकाशित नहीं हुआ है।</p>
    </div>
<?php else: ?>
    <?php 
        $latest = array_shift($items); 
        $latestHtml = getSubhashitHtml($latest);
    ?>
    
    <!-- Latest Subhashit -->
    <div class="featured-item">
        <div class="featured-badge">नवीनतम</div>
        <div class="featured-content">
            <?php echo nl2br(htmlspecialchars($latest['sanskrit_text'])); ?>
        </div>
        <div style="text-align:center;">
            <button class="detail-link-btn" style="display:inline-block; width:auto; padding:10px 30px;" onclick="openSidePanel('सुभाषित', `<?php echo addslashes($latestHtml); ?>`)">विस्तार से देखें एवं साझा करें</button>
        </div>
    </div>

    <!-- Archive Grid -->
    <h3 style="margin-bottom:20px; color:var(--ink-muted); font-size:1.1rem; border-bottom:1px solid var(--border-warm); padding-bottom:8px;">पूर्व सुभाषित</h3>
    <div class="archive-grid">
        <?php foreach ($items as $index => $item): 
            $title = !empty($item['sanskrit_text']) ? mb_substr($item['sanskrit_text'], 0, 60) . '...' : 'सुभाषित';
            $itemHtml = getSubhashitHtml($item);
        ?>
            <div class="grid-item" onclick="openSidePanel('सुभाषित', `<?php echo addslashes($itemHtml); ?>`)">
                <div class="grid-item-title"><?php echo htmlspecialchars($title); ?></div>
                <div class="grid-item-meta">
                    📅 <?php echo date('d M Y', strtotime($item['subhashit_date'])); ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/public_footer.php'; ?>
