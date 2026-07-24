<?php

namespace App\Core;

class AmritVachanImageGenerator
{
    private $width = 1080;
    private $height = 1920;

    private $footers = [
        "नित्य शाखा का संकल्प लें, संगठित समाज का निर्माण करें।",
        "शाखा से जुड़े हर दिन, संगठित बने अपना जन-जन।",
        "शाखा का नियमित अभ्यास, संगठित समाज का विश्वास।",
        "शाखा का हो नित्य प्रवास, संगठित हो अपना समाज।",
        "प्रतिदिन शाखा, सशक्त समाज की दिशा।",
        "नित्य शाखा का करें सम्मान, संगठित बने हिंदू समाज महान।",
        "नित्य शाखा में आएँ हम, संगठित समाज बनाएँ हम।",
        "शाखा जाएँ रोज़-रोज़, समाज बने सशक्त रोज़।",
        "नियमित शाखा, उज्ज्वल भविष्य; संगठित समाज, सर्वोत्तम दृष्टि।",
        "हर दिन शाखा की ओर बढ़ें, संगठित समाज की नींव गढ़ें।",
        "नित्य शाखा — संगठित समाज की आधारशिला।",
        "शाखा से संगठन, संगठन से शक्ति।",
        "शाखा का संस्कार, समाज का उत्थान।",
        "शाखा की ओर एक कदम, संगठित समाज की ओर हर कदम।",
        "नियमित शाखा, संगठित समाज।",
        "शाखा का प्रत्येक दिन, राष्ट्र निर्माण का प्रत्येक क्षण।",
        "नित्य शाखा का अभ्यास, राष्ट्रसेवा का विश्वास।",
        "शाखा से संस्कार, संस्कार से संगठन।",
        "आज शाखा, कल सशक्त समाज।",
        "नियमित शाखा, समर्पित जीवन, संगठित समाज।"
    ];

    public function generate(array $panchang, array $amritVachan, string $logoPath, string $shakhaName): string
    {
        $date = $panchang['panchang_date'] ?? date('Y-m-d');
        
        $outputDir = BASE_PATH . '/storage/creatives';
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }
        $outputPath = $outputDir . '/amritvachan_' . $date . '.jpg';
        $htmlPath = $outputDir . '/temp_amritvachan_' . $date . '.html';

        $html = $this->buildHtml($panchang, $amritVachan, $logoPath, $shakhaName);
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
        switch ($name) {
            case 'flag':
                return '<svg width="50" height="50" viewBox="0 0 100 100" style="vertical-align: middle; margin: 0 10px;">
                    <rect x="20" y="10" width="6" height="85" fill="#8B4513" rx="3"/>
                    <path d="M 26 15 L 95 35 L 55 50 L 95 70 L 26 80 Z" fill="#FF6700"/>
                </svg>';
            case 'om':
                return '<svg width="40" height="40" viewBox="0 0 100 100" style="vertical-align: middle; margin-right: 15px;">
                    <text x="50" y="70" font-family="Noto Sans Devanagari" font-size="60" font-weight="bold" fill="#FF6700" text-anchor="middle">ॐ</text>
                </svg>';
            case 'quotes':
                return '<svg width="60" height="60" viewBox="0 0 24 24" fill="#FF6700" style="opacity: 0.2; position: absolute; top: -10px; left: -10px;">
                    <path d="M14.017 21v-7.391c0-5.714 4.026-9.609 9.983-9.609v3.004c-3.155 0-4.991 1.956-4.991 4.996h4.991v9h-9.983zm-14.017 0v-7.391c0-5.714 4.026-9.609 9.983-9.609v3.004c-3.155 0-4.991 1.956-4.991 4.996h4.991v9h-9.983z"/>
                </svg>';
        }
        return '';
    }

    private function buildHtml(array $panchang, array $amritVachan, string $logoPath, string $shakhaName): string
    {
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

        $dateObj = new \DateTime($panchang['panchang_date'] ?? 'now');
        $dayNames = ['रविवार', 'सोमवार', 'मंगलवार', 'बुधवार', 'गुरुवार', 'शुक्रवार', 'शनिवार'];
        $monthNames = ['', 'जनवरी', 'फ़रवरी', 'मार्च', 'अप्रैल', 'मई', 'जून', 'जुलाई', 'अगस्त', 'सितम्बर', 'अक्तूबर', 'नवम्बर', 'दिसम्बर'];
        
        $dayName = $dayNames[(int)$dateObj->format('w')];
        $gregorianDate = $dateObj->format('d') . ' ' . $monthNames[(int)$dateObj->format('n')] . ' ' . $dateObj->format('Y');
        
        $vikramText = '';
        if (!empty($panchang['vikram_samvat'])) {
            $vikramText = 'विक्रम संवत् ' . $panchang['vikram_samvat'];
        }

        $tithiText = '';
        if (!empty($panchang['tithi'])) {
            $tithiText = $panchang['tithi'] . (!empty($panchang['paksha']) ? ' (' . $panchang['paksha'] . ')' : '');
        }

        $content = nl2br(htmlspecialchars($amritVachan['content'] ?? ''));
        $author = htmlspecialchars($amritVachan['author'] ?? '');
        $authorHtml = $author ? "<div class='author'>— {$author}</div>" : "";

        $footerMsg = $this->footers[array_rand($this->footers)];

        $flagIcon = $this->getSvgIcon('flag');
        $omIcon = $this->getSvgIcon('om');
        $quotesIcon = $this->getSvgIcon('quotes');

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
    width: 180px;
    height: 180px;
    object-fit: contain;
    border-radius: 50%;
    border: 6px solid #FF6700;
    box-shadow: 0 10px 25px rgba(255, 103, 0, 0.2);
    background: #fff;
}
.shakha-name {
    font-family: 'Yatra One', cursive;
    font-size: 60px;
    color: #D35400;
    margin: 20px 0 10px;
    line-height: 1.3;
}
.subtitle {
    font-size: 32px;
    color: #555555;
    font-weight: 600;
}
.date-section {
    text-align: center;
    margin: 25px 0;
    padding: 20px;
    background: #FFF3E0;
    border-radius: 20px;
    border: 2px solid #FFB74D;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
}
.date-row {
    display: flex;
    justify-content: space-around;
    align-items: center;
}
.date-day { font-size: 45px; font-weight: 800; color: #E65100; }
.date-greg { font-size: 40px; color: #424242; font-weight: 700; }
.date-samvat { font-size: 36px; color: #D84315; margin-top: 15px; font-weight: 600;}
.date-tithi { font-size: 36px; color: #D84315; margin-top: 5px; font-weight: 700;}
.section-title {
    text-align: center;
    font-size: 46px;
    font-weight: 800;
    color: #E65100;
    margin: 40px 0 25px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.amrit-card {
    position: relative;
    background: #FFFFFF;
    border: 4px solid #FFCC80;
    border-radius: 20px;
    padding: 50px;
    box-shadow: 0 15px 35px rgba(255, 152, 0, 0.15);
    flex-grow: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
}
.vachan-content {
    font-size: 48px;
    color: #BF360C;
    font-weight: 700;
    line-height: 1.6;
    text-align: center;
    margin-bottom: 30px;
    z-index: 2;
    position: relative;
}
.author {
    font-size: 36px;
    color: #555555;
    text-align: right;
    font-weight: 800;
    margin-top: 20px;
}
.random-footer {
    text-align: center;
    margin-top: 30px;
    font-size: 34px;
    color: #FFFFFF;
    background: #FF6700;
    padding: 15px 30px;
    border-radius: 15px;
    font-weight: 700;
    box-shadow: 0 5px 15px rgba(255, 103, 0, 0.3);
}
.footer {
    text-align: center;
    margin-top: 25px;
    padding-bottom: 10px;
    font-size: 32px;
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
                <div class="subtitle">अमृत वचन</div>
            </div>

            <div class="date-section">
                <div class="date-row">
                    <div class="date-day">{$dayName}</div>
                    <div class="date-greg">{$gregorianDate}</div>
                </div>
                <div class="date-samvat">{$vikramText}</div>
                <div class="date-tithi">{$tithiText}</div>
            </div>

            <div class="section-title">{$omIcon} आज का अमृत वचन</div>
            
            <div class="amrit-card">
                {$quotesIcon}
                <div class="vachan-content">
                    "{$content}"
                </div>
                {$authorHtml}
            </div>

            <div class="random-footer">
                {$footerMsg}
            </div>

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
