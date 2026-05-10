<?php
$pageTitle = 'व्यक्तित्व (Vyaktitv) — संघस्थान';
$pageDesc = 'राष्ट्रीय स्वयंसेवक संघ के प्रमुख व्यक्तित्व एवं उनके प्रेरणादायी जीवन का संकलन।';
require_once __DIR__ . '/../includes/public_header.php';

// Fetch all personalities
$stmt = $pdo->query("SELECT * FROM personalities ORDER BY display_order ASC, id ASC");
$personalities = $stmt->fetchAll();
?>

<style>
    .vy-container { max-width: 1000px; margin: 40px auto; padding: 0 20px; font-family: 'Noto Sans Devanagari', sans-serif; }
    .vy-header { text-align: center; margin-bottom: 50px; }
    .vy-title { font-size: clamp(2.5rem, 5vw, 3.5rem); font-weight: 900; color: #D83100; margin-bottom: 10px; text-shadow: 2px 2px 0 rgba(255,107,0,0.1); }
    .vy-subtitle { font-size: 1.2rem; color: #665A54; font-weight: 500; }
    .vy-ornament { width: 150px; height: 4px; background: linear-gradient(to right, transparent, #FF6B00, transparent); margin: 20px auto; border-radius: 2px; }
    
    .vy-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 25px; }
    .vy-card { 
        background: #fff; border-radius: 24px; padding: 25px; 
        box-shadow: 0 10px 30px rgba(0,0,0,0.05); 
        border: 1px solid #f0f0f0; 
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); 
        cursor: pointer; position: relative; overflow: hidden;
        display: flex; flex-direction: column;
    }
    .vy-card:hover { 
        transform: translateY(-8px); 
        box-shadow: 0 25px 50px rgba(216,49,0,0.1); 
        border-color: #FF6B00; 
    }
    
    .vy-name { font-size: 1.6rem; font-weight: 800; color: #1a1a1a; margin-bottom: 8px; line-height: 1.3; }
    .vy-period { 
        font-size: 0.9rem; color: #D83100; font-weight: 700; 
        margin-bottom: 15px; display: inline-block; padding: 4px 12px; 
        background: rgba(216,49,0,0.08); border-radius: 50px; 
    }
    
    .vy-img-container {
        width: 100%; height: 200px; border-radius: 16px; overflow: hidden;
        margin-bottom: 20px; display: none; background: #f9f9f9;
        border: 1px solid #eee;
    }
    .vy-img-container img { width: 100%; height: 100%; object-fit: cover; filter: grayscale(20%); transition: all 0.5s ease; }
    .vy-card.expanded .vy-img-container { display: block; }
    .vy-card.expanded .vy-img-container img { filter: grayscale(0%); }

    .vy-desc-short { font-size: 1.05rem; color: #444; line-height: 1.7; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
    .vy-desc-full { font-size: 1.05rem; color: #444; line-height: 1.7; display: none; margin-top: 15px; border-top: 1px dashed #ddd; padding-top: 15px; }
    
    .vy-card.expanded .vy-desc-short { display: none; }
    .vy-card.expanded .vy-desc-full { display: block; }
    .vy-card.expanded { grid-column: 1 / -1; }
    
    @media (max-width: 768px) {
        .vy-card.expanded { grid-column: auto; }
    }

    .expand-btn { 
        margin-top: 20px; align-self: flex-start; color: #FF6B00; 
        font-weight: 700; font-size: 0.95rem; display: flex; align-items: center; gap: 5px; 
    }
    .expand-btn svg { transition: transform 0.3s ease; }
    .vy-card.expanded .expand-btn svg { transform: rotate(180deg); }
    .vy-card.expanded .expand-btn span::after { content: ' कम दिखाएं'; }
    .vy-card:not(.expanded) .expand-btn span::after { content: ' पूरा पढ़ें'; }
    
    .vy-share-btn {
        margin-top: 15px; background: #25D366; color: white; border: none;
        padding: 8px 16px; border-radius: 50px; font-weight: bold; cursor: pointer;
        display: flex; align-items: center; gap: 8px; font-size: 0.9rem;
        transition: transform 0.2s; align-self: flex-end;
    }
    .vy-share-btn:hover { transform: scale(1.05); background: #128C7E; }
    .vy-card:not(.expanded) .vy-share-btn { display: none; }

    /* Fallback Icon for missing images */
    .img-fallback { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 4rem; background: #fff5f0; color: #ffab91; }
</style>

<div class="vy-container">
    <div class="vy-header">
        <h1 class="vy-title">व्यक्तित्व</h1>
        <div class="vy-ornament"></div>
        <p class="vy-subtitle">राष्ट्रीय स्वयंसेवक संघ के निर्माण एवं विस्तार के आधार स्तंभ</p>
    </div>

    <div class="vy-grid">
        <?php foreach ($personalities as $p): ?>
            <div class="vy-card" onclick="this.classList.toggle('expanded')">
                <div class="vy-img-container">
                    <?php 
                    $imgPath = $p['image_path'];
                    if (!empty($imgPath) && strpos($imgPath, '/assets') === 0) {
                        $imgPath = '..' . $imgPath;
                    }
                    ?>
                    <?php if (!empty($imgPath)): ?>
                        <img src="<?php echo htmlspecialchars($imgPath); ?>" alt="<?php echo htmlspecialchars($p['name']); ?>" loading="lazy">
                    <?php else: ?>
                        <div class="img-fallback">🚩</div>
                    <?php endif; ?>
                </div>

                <div class="vy-name"><?php echo htmlspecialchars($p['name']); ?></div>
                <?php if ($p['title']): ?>
                    <div class="vy-period"><?php echo htmlspecialchars($p['title']); ?></div>
                <?php endif; ?>
                
                <div class="vy-desc-short"><?php echo nl2br(htmlspecialchars($p['description'])); ?></div>
                <div class="vy-desc-full"><?php echo nl2br(htmlspecialchars($p['description'])); ?></div>
                
                <div class="expand-btn">
                    <span>विस्तार से</span>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>
                </div>

                <button class="vy-share-btn" onclick="event.stopPropagation(); sharePersonality('<?php echo addslashes(htmlspecialchars($p['name'])); ?>', '<?php echo addslashes(htmlspecialchars($p['title'])); ?>', '<?php echo addslashes(preg_replace('/\s+/', ' ', htmlspecialchars($p['description']))); ?>')">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12.012 2c-5.506 0-9.989 4.478-9.99 9.984a9.964 9.964 0 001.333 4.993L2 22l5.233-1.371a9.944 9.944 0 004.777 1.21h.005c5.506 0 9.989-4.478 9.99-9.984 0-2.669-1.037-5.176-2.927-7.067A9.925 9.925 0 0012.012 2z"/></svg>
                    शेयर करें
                </button>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
    document.querySelectorAll('.vy-card').forEach(card => {
        card.addEventListener('click', () => {
            if (card.classList.contains('expanded')) {
                setTimeout(() => {
                    card.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }, 100);
            }
        });
    });

    function sharePersonality(name, title, desc) {
        const text = `🚩 *व्यक्तित्व - ${name}* 🚩\n\n${title ? '*' + title + '*\n' : ''}\n${desc}\n\nसराहनीय जानकारी के लिए देखें: ${window.location.href}`;
        const url = `https://wa.me/?text=${encodeURIComponent(text)}`;
        window.open(url, '_blank');
    }
</script>

<?php require_once __DIR__ . '/../includes/public_footer.php'; ?>
