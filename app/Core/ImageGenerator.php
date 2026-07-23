<?php

namespace App\Core;

/**
 * ImageGenerator — Generates beautiful daily creative cards using HTML + wkhtmltoimage.
 * 
 * HTML rendering ensures flawless Devanagari typography (complex text layout)
 * with modern CSS styling.
 */
class ImageGenerator
{
    private $width = 1080;
    private $height = 1920;

    public function generate(array $panchang, ?array $subhashit, string $logoPath, string $shakhaName): string
    {
        $date = $panchang['panchang_date'] ?? date('Y-m-d');
        
        $outputDir = BASE_PATH . '/storage/creatives';
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }
        $outputPath = $outputDir . '/daily_' . $date . '.jpg';
        $htmlPath = $outputDir . '/temp_' . $date . '.html';

        $html = $this->buildHtml($panchang, $subhashit, $logoPath, $shakhaName);
        file_put_contents($htmlPath, $html);

        // Call wkhtmltoimage
        $cmd = sprintf(
            'wkhtmltoimage --quality 95 --width %d --disable-smart-width %s %s',
            $this->width,
            escapeshellarg($htmlPath),
            escapeshellarg($outputPath)
        );
        
        $output = [];
        $returnVar = 0;
        exec($cmd . ' 2>&1', $output, $returnVar);

        if (file_exists($htmlPath)) {
            unlink($htmlPath);
        }

        if ($returnVar !== 0) {
            throw new \Exception("Failed to generate image via wkhtmltoimage. Output: " . implode("\n", $output));
        }

        return $outputPath;
    }

    private function getSvgIcon(string $name): string
    {
        $color = '#FF6700';
        switch ($name) {
            case 'flag':
                // RSS style Bhagwa Dhwaj
                return '<svg width="50" height="50" viewBox="0 0 100 100" style="vertical-align: middle; margin: 0 10px;">
                    <!-- Pole -->
                    <rect x="20" y="10" width="6" height="85" fill="#8B4513" rx="3"/>
                    <!-- Flag body -->
                    <path d="M 26 15 L 95 35 L 55 50 L 95 70 L 26 80 Z" fill="#FF6700"/>
                </svg>';
            case 'om':
                return '<svg width="40" height="40" viewBox="0 0 100 100" style="vertical-align: middle; margin-right: 15px;">
                    <text x="50" y="70" font-family="Noto Sans Devanagari" font-size="60" font-weight="bold" fill="#FF6700" text-anchor="middle">ॐ</text>
                </svg>';
            case 'scroll':
                return '<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#FF6700" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 15px;">
                    <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"/>
                </svg>';
        }
        return '';
    }

    private function buildHtml(array $panchang, ?array $subhashit, string $logoPath, string $shakhaName): string
    {
        // Fix for logo mime type and fallback
        $logoBase64 = '';
        if (!file_exists($logoPath)) {
            $logoPath = BASE_PATH . '/assets/images/logo.png';
        }
        
        if (file_exists($logoPath)) {
            $ext = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));
            if ($ext === 'svg') {
                $mime = 'image/svg+xml';
            } elseif ($ext === 'jpg' || $ext === 'jpeg') {
                $mime = 'image/jpeg';
            } else {
                $mime = 'image/png';
            }
            $logoData = file_get_contents($logoPath);
            $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode($logoData);
        }

        // Date formatting
        $dateObj = new \DateTime($panchang['panchang_date'] ?? 'now');
        $dayNames = ['रविवार', 'सोमवार', 'मंगलवार', 'बुधवार', 'गुरुवार', 'शुक्रवार', 'शनिवार'];
        $monthNames = ['', 'जनवरी', 'फ़रवरी', 'मार्च', 'अप्रैल', 'मई', 'जून', 'जुलाई', 'अगस्त', 'सितम्बर', 'अक्तूबर', 'नवम्बर', 'दिसम्बर'];
        
        $dayName = $dayNames[(int)$dateObj->format('w')];
        $gregorianDate = $dateObj->format('d') . ' ' . $monthNames[(int)$dateObj->format('n')] . ' ' . $dateObj->format('Y');
        
        $vikramText = '';
        if (!empty($panchang['vikram_samvat'])) {
            $vikramText = 'विक्रम संवत् ' . $panchang['vikram_samvat'];
            if (!empty($panchang['vikram_month'])) {
                $vikramText .= ' | ' . $panchang['vikram_month'];
            }
        }

        $utsavHtml = '';
        if (!empty($panchang['utsav'])) {
            $utsavHtml = '<div class="utsav">' . $this->getSvgIcon('flag') . htmlspecialchars($panchang['utsav']) . $this->getSvgIcon('flag') . '</div>';
        }

        $subhashitHtml = '';
        if ($subhashit) {
            $sanskrit = nl2br(htmlspecialchars($subhashit['sanskrit_text'] ?? ''));
            $hindi = nl2br(htmlspecialchars($subhashit['hindi_meaning'] ?? ''));
            $subhashitHtml = "
                <div class='section-title'>{$this->getSvgIcon('scroll')} आज का सुभाषित</div>
                <div class='card subhashit-card'>
                    <div class='sanskrit'>{$sanskrit}</div>
                    <div class='hindi'>{$hindi}</div>
                </div>
            ";
        }

        $panchangItemsHtml = '';
        $items = [];
        if (!empty($panchang['tithi']))     $items['तिथि'] = $panchang['tithi'] . (!empty($panchang['paksha']) ? ' (' . $panchang['paksha'] . ')' : '');
        if (!empty($panchang['nakshatra'])) $items['नक्षत्र'] = $panchang['nakshatra'];
        if (!empty($panchang['yoga']) && $panchang['yoga'] !== '—')   $items['योग'] = $panchang['yoga'];
        if (!empty($panchang['karana']) && $panchang['karana'] !== '—') $items['करण'] = $panchang['karana'];
        if (!empty($panchang['chandra_rashi'])) $items['चन्द्र राशि'] = $panchang['chandra_rashi'];
        if (!empty($panchang['sunrise']))   $items['सूर्योदय'] = $panchang['sunrise'];
        if (!empty($panchang['sunset']))    $items['सूर्यास्त'] = $panchang['sunset'];
        
        foreach ($items as $label => $val) {
            $panchangItemsHtml .= "
                <div class='panchang-row'>
                    <div class='panchang-label'>{$label}:</div>
                    <div class='panchang-val'>{$val}</div>
                </div>
            ";
        }

        $flagIcon = $this->getSvgIcon('flag');
        $omIcon = $this->getSvgIcon('om');

        return <<<HTML
<!DOCTYPE html>
<html lang="hi">
<head>
<meta charset="UTF-8">
<style>
@import url('https://fonts.googleapis.com/css2?family=Noto+Sans+Devanagari:wght@400;600;700;800&family=Yatra+One&display=swap');
* { box-sizing: border-box; }
body {
    width: 1080px;
    height: 1920px;
    margin: 0;
    padding: 25px;
    background: #FFF8F0;
    color: #333333;
    font-family: 'Noto Sans Devanagari', sans-serif;
}
.outer-wrapper {
    width: 1030px;
    height: 1870px;
    border: 12px solid #FFCC00;
    border-radius: 40px;
    padding: 20px;
    background: #FFFFFF;
}
.inner-wrapper {
    width: 966px;
    height: 1806px;
    border: 6px solid #FF6700;
    border-radius: 25px;
    background: radial-gradient(circle at center, #FFFFFF 0%, #FFF8F0 100%);
    padding: 50px 70px;
    display: flex;
    flex-direction: column;
}
.header { text-align: center; }
.logo {
    width: 220px;
    height: 220px;
    object-fit: contain;
    border-radius: 50%;
    border: 6px solid #FF6700;
    box-shadow: 0 10px 25px rgba(255, 103, 0, 0.2);
    background: #fff;
}
.shakha-name {
    font-family: 'Yatra One', cursive;
    font-size: 72px;
    color: #D35400;
    margin: 30px 0 10px;
    line-height: 1.3;
}
.subtitle {
    font-size: 36px;
    color: #555555;
    font-weight: 600;
}
.date-section {
    text-align: center;
    margin: 35px 0;
    padding: 25px;
    background: #FFF3E0;
    border-radius: 20px;
    border: 2px solid #FFB74D;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
}
.date-day { font-size: 60px; font-weight: 800; color: #E65100; }
.date-greg { font-size: 40px; color: #424242; margin-top: 15px; font-weight: 700; }
.date-samvat { font-size: 32px; color: #D84315; margin-top: 15px; font-weight: 600;}
.utsav {
    font-size: 46px;
    color: #C62828;
    font-weight: 800;
    margin-top: 15px;
    text-align: center;
    line-height: 1.4;
}
.section-title {
    text-align: center;
    font-size: 42px;
    font-weight: 800;
    color: #E65100;
    margin: 25px 0 15px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.card {
    background: #FFFFFF;
    border: 3px solid #FFCC80;
    border-radius: 20px;
    padding: 30px 40px;
    box-shadow: 0 10px 30px rgba(255, 152, 0, 0.1);
}
.panchang-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px 40px;
}
.panchang-row {
    display: flex;
    font-size: 32px;
    border-bottom: 2px dotted #FFE0B2;
    padding-bottom: 12px;
    line-height: 1.4;
}
.panchang-label {
    width: 45%;
    color: #D84315;
    font-weight: 800;
}
.panchang-val {
    width: 55%;
    color: #424242;
    font-weight: 700;
}
.subhashit-card {
    text-align: center;
    margin-bottom: auto;
    background: #FFF8E1;
    border-color: #FFCA28;
}
.sanskrit {
    font-size: 36px;
    color: #BF360C;
    font-weight: 800;
    line-height: 1.8;
    margin-bottom: 25px;
}
.hindi {
    font-size: 30px;
    color: #424242;
    line-height: 1.8;
    font-weight: 600;
}
.footer {
    text-align: center;
    margin-top: 30px;
    padding-bottom: 10px;
    font-size: 36px;
    color: #E65100;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>
</head>
<body>
    <div class="outer-wrapper">
        <div class="inner-wrapper">
            <div class="header">
                <img src="{$logoBase64}" class="logo" />
                <div class="shakha-name">{$shakhaName}</div>
                <div class="subtitle">दैनिक पंचांग एवं सुभाषित</div>
            </div>

            <div class="date-section">
                <div class="date-day">{$dayName}</div>
                <div class="date-greg">{$gregorianDate}</div>
                <div class="date-samvat">{$vikramText}</div>
            </div>

            {$utsavHtml}

            <div class="section-title">{$omIcon} आज का पंचांग</div>
            <div class="card">
                <div class="panchang-grid">
                    {$panchangItemsHtml}
                </div>
            </div>

            {$subhashitHtml}

            <div class="footer">
                {$flagIcon} जय श्री राम &nbsp;|&nbsp; भारत माता की जय {$flagIcon}
            </div>
        </div>
    </div>
</body>
</html>
HTML;
    }
}
