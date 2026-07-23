<?php

namespace App\Core;

/**
 * ImageGenerator — Generates beautiful daily creative cards using HTML + wkhtmltoimage.
 * 
 * HTML rendering ensures flawless Devanagari typography (complex text layout)
 * with modern CSS styling, which PHP GD cannot do.
 */
class ImageGenerator
{
    private $width = 1080;
    private $height = 1920;

    /**
     * Generate the daily creative card.
     *
     * @param array $panchang  Panchang data row from panchang_data table
     * @param array $subhashit Subhashit row (sanskrit_text, hindi_meaning)
     * @param string $logoPath Absolute path to shakha logo
     * @param string $shakhaName Name of the shakha
     * @return string Absolute path to the generated image
     */
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
        // --quality 95 --width 1080 --disable-smart-width
        $cmd = sprintf(
            'wkhtmltoimage --quality 95 --width %d --disable-smart-width %s %s',
            $this->width,
            escapeshellarg($htmlPath),
            escapeshellarg($outputPath)
        );
        
        // Execute the command
        $output = [];
        $returnVar = 0;
        exec($cmd . ' 2>&1', $output, $returnVar);

        // Clean up temp HTML
        if (file_exists($htmlPath)) {
            unlink($htmlPath);
        }

        if ($returnVar !== 0) {
            throw new \Exception("Failed to generate image via wkhtmltoimage. Output: " . implode("\n", $output));
        }

        return $outputPath;
    }

    private function buildHtml(array $panchang, ?array $subhashit, string $logoPath, string $shakhaName): string
    {
        // Convert logo to base64 so wkhtmltoimage doesn't have path resolution issues
        $logoBase64 = '';
        if (file_exists($logoPath)) {
            $ext = pathinfo($logoPath, PATHINFO_EXTENSION);
            $logoData = file_get_contents($logoPath);
            $logoBase64 = 'data:image/' . $ext . ';base64,' . base64_encode($logoData);
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
            $utsavHtml = '<div class="utsav">🎉 ' . htmlspecialchars($panchang['utsav']) . '</div>';
        }

        $subhashitHtml = '';
        if ($subhashit) {
            $sanskrit = nl2br(htmlspecialchars($subhashit['sanskrit_text'] ?? ''));
            $hindi = nl2br(htmlspecialchars($subhashit['hindi_meaning'] ?? ''));
            $subhashitHtml = "
                <div class='section-title'>📜 आज का सुभाषित</div>
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
    padding: 0;
    background: linear-gradient(145deg, #1A0A02, #4A1A05, #1A0A02);
    color: #fff;
    font-family: 'Noto Sans Devanagari', sans-serif;
    position: relative;
    overflow: hidden;
}
.saffron-bar {
    height: 16px;
    background: #FF6700;
    width: 100%;
}
.container {
    padding: 60px 80px;
    height: calc(1920px - 16px);
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
    box-shadow: 0 0 50px rgba(255, 103, 0, 0.4);
    background: #fff;
}
.shakha-name {
    font-family: 'Yatra One', cursive;
    font-size: 72px;
    color: #FF8800;
    margin: 30px 0 10px;
    text-shadow: 3px 3px 15px rgba(0,0,0,0.8);
    line-height: 1.2;
}
.subtitle {
    font-size: 32px;
    color: #FFDDAA;
    opacity: 0.8;
}
.date-section {
    text-align: center;
    margin: 50px 0;
    padding: 30px;
    background: rgba(255, 103, 0, 0.1);
    border-radius: 20px;
    border: 2px solid rgba(255, 103, 0, 0.2);
}
.date-day { font-size: 64px; font-weight: 800; color: #FFCC00; text-shadow: 2px 2px 5px rgba(0,0,0,0.5); }
.date-greg { font-size: 42px; color: #FFF3E0; margin-top: 15px; font-weight: 600; }
.date-samvat { font-size: 32px; color: #FF9933; margin-top: 15px; font-weight: 600;}
.utsav {
    font-size: 54px;
    color: #FFEB3B;
    font-weight: 800;
    margin-top: 40px;
    text-align: center;
    text-shadow: 0 0 25px rgba(255, 235, 59, 0.6);
}
.section-title {
    text-align: center;
    font-size: 42px;
    font-weight: 800;
    color: #FF6700;
    margin: 40px 0 25px;
    text-transform: uppercase;
    letter-spacing: 2px;
}
.card {
    background: rgba(0, 0, 0, 0.4);
    border: 2px solid rgba(255, 103, 0, 0.3);
    border-radius: 20px;
    padding: 40px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.5);
}
.panchang-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px 40px;
}
.panchang-row {
    display: flex;
    font-size: 34px;
    border-bottom: 1px dashed rgba(255, 255, 255, 0.1);
    padding-bottom: 15px;
}
.panchang-label {
    width: 45%;
    color: #FF9933;
    font-weight: 700;
}
.panchang-val {
    width: 55%;
    color: #FFF3E0;
    font-weight: 600;
}
.subhashit-card {
    text-align: center;
    margin-bottom: auto;
}
.sanskrit {
    font-size: 38px;
    color: #FFCC00;
    font-weight: 800;
    line-height: 1.6;
    margin-bottom: 25px;
}
.hindi {
    font-size: 32px;
    color: #FFF3E0;
    line-height: 1.5;
    font-weight: 400;
}
.footer {
    text-align: center;
    margin-top: 50px;
    padding-bottom: 40px;
    font-size: 32px;
    color: #FF8800;
    font-weight: 700;
}
</style>
</head>
<body>
    <div class="saffron-bar"></div>
    <div class="container">
        
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

        <div class="section-title">🕉️ आज का पंचांग</div>
        <div class="card">
            <div class="panchang-grid">
                {$panchangItemsHtml}
            </div>
        </div>

        {$subhashitHtml}

        <div class="footer">
            🚩 हर हर महादेव | भारत माता की जय 🚩
        </div>
    </div>
</body>
</html>
HTML;
    }
}
