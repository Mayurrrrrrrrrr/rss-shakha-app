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
    </div>
<?php else: ?>
    <div class="list-container">
        <?php foreach ($items as $index => $item): 
            $title = !empty($item['sanskrit_text']) ? mb_substr($item['sanskrit_text'], 0, 40) . '...' : 'सुभाषित ' . ($index + 1);
            $contentHtml = '';
            
            // Generate content for side panel
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
                    $contentHtml .= '<table class="shabdarth-table"><thead><tr><th>शब्द</th><th>अर्थ</th></tr></thead><tbody>';
                    foreach ($shabdarth as $s) {
                        $contentHtml .= '<tr><td>' . htmlspecialchars($s['word'] ?? '') . '</td><td>' . htmlspecialchars($s['meaning'] ?? '') . '</td></tr>';
                    }
                    $contentHtml .= '</tbody></table>';
                }
            }
            
            $meta = '📅 ' . date('d M Y', strtotime($item['subhashit_date']));
            if (!empty($item['panchang_text'])) $meta .= ' | 🕉️ ' . htmlspecialchars($item['panchang_text']);
            if (!empty($item['shakha_name'])) $meta .= ' | 🚩 ' . htmlspecialchars($item['shakha_name']);
            
            $contentHtml .= '<div class="content-item-meta" style="margin-top:20px;">' . $meta . '</div>';
            
            // Share on WhatsApp option
            $currentUrl = "https://sanghasthan.yuktaa.com/subhashit/";
            $shareText = "✨ *सुभाषित*\n\n" . $item['sanskrit_text'] . "\n\n*अर्थ:* " . $item['hindi_meaning'] . "\n\nसौजन्य: संघस्थान";
            $waLink = "https://api.whatsapp.com/send?text=" . urlencode($shareText);
            
            $contentHtml .= '<div style="margin-top:30px; text-align:center;"><a href="'.$waLink.'" target="_blank" class="share-btn-wa" style="background:#25D366; color:white; text-decoration:none; padding:8px 16px; border-radius:20px; display:inline-flex; align-items:center; gap:8px; font-size:0.9rem;"><svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M12.012 2c-5.508 0-9.988 4.48-9.988 9.988 0 1.76.459 3.413 1.26 4.852l-1.34 4.896 5.01-1.316c1.408.766 3.013 1.204 4.718 1.204 5.508 0 9.988-4.48 9.988-9.988s-4.48-9.988-9.988-9.988zm6.596 14.152c-.273.766-1.353 1.433-2.222 1.543-.591.074-1.363.132-3.961-.933-3.322-1.363-5.464-4.738-5.63-4.956-.165-.219-1.339-1.782-1.339-3.411 0-1.63.847-2.43 1.15-2.76.303-.33.666-.412.889-.412.222 0 .444.004.639.013.199.008.468-.076.734.568.273.659.932 2.274 1.012 2.438.079.164.133.356.024.573-.109.219-.164.356-.328.547-.164.192-.345.426-.492.573-.165.164-.338.344-.145.679.193.336.858 1.413 1.838 2.285.98 0.872 1.808 1.144 2.138 1.309.33.164.52.14.714-.079.192-.219.824-.961 1.042-1.285.219-.328.437-.273.738-.164.301.109 1.913.902 2.24 1.066.328.164.547.245.628.383.082.138.082.802-.191 1.568z"/></svg> शेयर करें</a></div>';
        ?>
            <div class="list-item" onclick="openSidePanel('सुभाषित', `<?php echo addslashes($contentHtml); ?>`)">
                <div class="list-item-title"><?php echo htmlspecialchars($title); ?></div>
                <div class="list-item-arrow"> विस्तार से देखें →</div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/public_footer.php'; ?>
