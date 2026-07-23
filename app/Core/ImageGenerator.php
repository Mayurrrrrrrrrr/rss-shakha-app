<?php

namespace App\Core;

/**
 * ImageGenerator — Generates beautiful daily creative cards using PHP GD.
 * 
 * Creates a saffron-themed card with:
 * - Shakha logo
 * - Panchang highlights (Tithi, Nakshatra, Sunrise/Sunset, etc.)
 * - A Subhashit (Sanskrit text + Hindi meaning)
 */
class ImageGenerator
{
    private $width = 1080;
    private $height = 1920;
    private $fontBold;
    private $fontRegular;

    // Saffron theme colors (will be allocated on image)
    private $img;
    private $colors = [];

    public function __construct()
    {
        $this->fontBold = BASE_PATH . '/assets/fonts/NotoSansDevanagari-Bold.ttf';
        $this->fontRegular = BASE_PATH . '/assets/fonts/NotoSansDevanagari-Regular.ttf';
    }

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
        $this->img = imagecreatetruecolor($this->width, $this->height);
        $this->allocateColors();
        $this->drawBackground();
        
        $y = 60; // Current vertical position

        // === HEADER: Logo + Shakha Name ===
        $y = $this->drawHeader($logoPath, $shakhaName, $y);

        // === DATE SECTION ===
        $y = $this->drawDateSection($panchang, $y);

        // === DIVIDER ===
        $y = $this->drawDivider($y);

        // === PANCHANG SECTION ===
        $y = $this->drawPanchangSection($panchang, $y);

        // === DIVIDER ===
        $y = $this->drawDivider($y);

        // === SUBHASHIT SECTION ===
        if ($subhashit) {
            $y = $this->drawSubhashitSection($subhashit, $y);
        }

        // === FOOTER ===
        $this->drawFooter();

        // Save the image
        $date = $panchang['panchang_date'] ?? date('Y-m-d');
        $outputDir = BASE_PATH . '/storage/creatives';
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }
        $outputPath = $outputDir . '/daily_' . $date . '.jpg';
        
        imagejpeg($this->img, $outputPath, 92);
        imagedestroy($this->img);

        return $outputPath;
    }

    private function allocateColors()
    {
        $this->colors = [
            'bg_dark'       => imagecolorallocate($this->img, 30, 15, 5),       // Deep brown-black
            'bg_card'       => imagecolorallocate($this->img, 45, 22, 8),       // Dark card bg
            'saffron'       => imagecolorallocate($this->img, 255, 103, 0),     // Bhagwa/Saffron
            'saffron_light' => imagecolorallocate($this->img, 255, 153, 51),    // Light saffron
            'gold'          => imagecolorallocate($this->img, 255, 204, 0),     // Gold
            'white'         => imagecolorallocate($this->img, 255, 255, 255),
            'cream'         => imagecolorallocate($this->img, 255, 243, 224),   // Warm cream
            'text_light'    => imagecolorallocate($this->img, 200, 180, 160),   // Muted light text
            'text_muted'    => imagecolorallocate($this->img, 150, 130, 110),   // Muted text
            'orange_dark'   => imagecolorallocate($this->img, 180, 70, 0),      // Dark orange
            'border'        => imagecolorallocate($this->img, 80, 40, 15),      // Card border
        ];
    }

    private function drawBackground()
    {
        // Gradient background: dark brown to deeper brown
        for ($i = 0; $i < $this->height; $i++) {
            $r = (int)(30 + ($i / $this->height) * 10);
            $g = (int)(15 + ($i / $this->height) * 5);
            $b = (int)(5 + ($i / $this->height) * 3);
            $color = imagecolorallocate($this->img, min($r, 255), min($g, 255), min($b, 255));
            imageline($this->img, 0, $i, $this->width, $i, $color);
        }

        // Decorative saffron top border (8px)
        imagefilledrectangle($this->img, 0, 0, $this->width, 8, $this->colors['saffron']);
    }

    private function drawHeader(string $logoPath, string $shakhaName, int $y): int
    {
        $y += 30;

        // Draw logo if exists
        if (file_exists($logoPath)) {
            $ext = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));
            $logo = null;

            if ($ext === 'jpg' || $ext === 'jpeg') {
                $logo = @imagecreatefromjpeg($logoPath);
            } elseif ($ext === 'png') {
                $logo = @imagecreatefrompng($logoPath);
            } elseif ($ext === 'webp') {
                $logo = @imagecreatefromwebp($logoPath);
            }

            if ($logo) {
                $logoSize = 120;
                $logoX = ($this->width - $logoSize) / 2;
                $srcW = imagesx($logo);
                $srcH = imagesy($logo);
                imagecopyresampled($this->img, $logo, (int)$logoX, $y, 0, 0, $logoSize, $logoSize, $srcW, $srcH);
                imagedestroy($logo);
                $y += $logoSize + 20;
            }
        }

        // Shakha name
        $y = $this->drawCenteredText($shakhaName, $this->fontBold, 32, $this->colors['saffron'], $y);
        $y += 10;

        // "संघस्थान" subtitle
        $y = $this->drawCenteredText('संघस्थान — दैनिक प्रेरणा', $this->fontRegular, 20, $this->colors['text_muted'], $y);
        $y += 10;

        return $y;
    }

    private function drawDateSection(array $panchang, int $y): int
    {
        $y += 20;

        // Gregorian date
        $date = $panchang['panchang_date'] ?? date('Y-m-d');
        $dateObj = new \DateTime($date);
        $dayNames = ['रविवार', 'सोमवार', 'मंगलवार', 'बुधवार', 'गुरुवार', 'शुक्रवार', 'शनिवार'];
        $monthNames = ['', 'जनवरी', 'फ़रवरी', 'मार्च', 'अप्रैल', 'मई', 'जून', 'जुलाई', 'अगस्त', 'सितम्बर', 'अक्तूबर', 'नवम्बर', 'दिसम्बर'];
        
        $dayName = $dayNames[(int)$dateObj->format('w')];
        $gregorianDate = $dateObj->format('d') . ' ' . $monthNames[(int)$dateObj->format('n')] . ' ' . $dateObj->format('Y');
        
        $y = $this->drawCenteredText($dayName, $this->fontBold, 28, $this->colors['gold'], $y);
        $y += 8;
        $y = $this->drawCenteredText($gregorianDate, $this->fontRegular, 24, $this->colors['cream'], $y);
        $y += 5;

        // Vikram Samvat
        if (!empty($panchang['vikram_samvat'])) {
            $vikramText = 'विक्रम संवत् ' . $panchang['vikram_samvat'];
            if (!empty($panchang['vikram_month'])) {
                $vikramText .= ' | ' . $panchang['vikram_month'];
            }
            $y = $this->drawCenteredText($vikramText, $this->fontRegular, 18, $this->colors['saffron_light'], $y);
        }

        // Utsav (festival) — highlighted
        if (!empty($panchang['utsav'])) {
            $y += 15;
            $y = $this->drawCenteredText('🎉 ' . $panchang['utsav'], $this->fontBold, 24, $this->colors['gold'], $y);
        }

        $y += 10;
        return $y;
    }

    private function drawPanchangSection(array $panchang, int $y): int
    {
        $y += 15;
        $y = $this->drawCenteredText('🕉️ आज का पंचांग', $this->fontBold, 26, $this->colors['saffron'], $y);
        $y += 20;

        // Card background
        $cardX = 60;
        $cardW = $this->width - 120;
        $cardTop = $y;

        $items = [];
        if (!empty($panchang['tithi']))     $items[] = ['तिथि', $panchang['tithi'] . (!empty($panchang['paksha']) ? ' (' . $panchang['paksha'] . ')' : '')];
        if (!empty($panchang['nakshatra'])) $items[] = ['नक्षत्र', $panchang['nakshatra']];
        if (!empty($panchang['yoga']) && $panchang['yoga'] !== '—')   $items[] = ['योग', $panchang['yoga']];
        if (!empty($panchang['karana']) && $panchang['karana'] !== '—') $items[] = ['करण', $panchang['karana']];
        if (!empty($panchang['chandra_rashi'])) $items[] = ['चन्द्र राशि', $panchang['chandra_rashi']];
        if (!empty($panchang['sunrise']))   $items[] = ['सूर्योदय', $panchang['sunrise']];
        if (!empty($panchang['sunset']))    $items[] = ['सूर्यास्त', $panchang['sunset']];
        if (!empty($panchang['rahukaal']) && $panchang['rahukaal'] !== '—') $items[] = ['राहुकाल', $panchang['rahukaal']];

        $cardH = count($items) * 48 + 30;

        // Draw card background
        $cardBg = imagecolorallocatealpha($this->img, 60, 30, 10, 40);
        imagefilledrectangle($this->img, $cardX, $cardTop, $cardX + $cardW, $cardTop + $cardH, $cardBg);
        imagerectangle($this->img, $cardX, $cardTop, $cardX + $cardW, $cardTop + $cardH, $this->colors['border']);

        $y = $cardTop + 15;
        foreach ($items as $item) {
            $labelBox = imagettfbbox(18, 0, $this->fontBold, $item[0] . ':');
            imagettftext($this->img, 18, 0, $cardX + 30, $y + 20, $this->colors['saffron_light'], $this->fontBold, $item[0] . ':');
            imagettftext($this->img, 18, 0, $cardX + 200, $y + 20, $this->colors['cream'], $this->fontRegular, $item[1]);
            $y += 48;
        }

        $y += 20;
        return $y;
    }

    private function drawSubhashitSection(array $subhashit, int $y): int
    {
        $y += 15;
        $y = $this->drawCenteredText('📜 आज का सुभाषित', $this->fontBold, 26, $this->colors['saffron'], $y);
        $y += 20;

        // Card background
        $cardX = 60;
        $cardW = $this->width - 120;

        // Sanskrit text (wrapped)
        $sanskritText = $subhashit['sanskrit_text'] ?? '';
        $sanskritLines = $this->wrapText($sanskritText, $this->fontBold, 20, $cardW - 60);
        
        // Hindi meaning (wrapped)
        $hindiText = $subhashit['hindi_meaning'] ?? '';
        $hindiLines = $this->wrapText($hindiText, $this->fontRegular, 17, $cardW - 60);

        $cardH = (count($sanskritLines) * 36) + 30 + (count($hindiLines) * 30) + 40;
        $cardTop = $y;

        $cardBg = imagecolorallocatealpha($this->img, 60, 30, 10, 40);
        imagefilledrectangle($this->img, $cardX, $cardTop, $cardX + $cardW, $cardTop + $cardH, $cardBg);
        imagerectangle($this->img, $cardX, $cardTop, $cardX + $cardW, $cardTop + $cardH, $this->colors['border']);

        $y = $cardTop + 25;

        // Sanskrit text
        foreach ($sanskritLines as $line) {
            $y = $this->drawCenteredText($line, $this->fontBold, 20, $this->colors['gold'], $y);
            $y += 8;
        }

        $y += 15;

        // Hindi meaning
        foreach ($hindiLines as $line) {
            $y = $this->drawCenteredText($line, $this->fontRegular, 17, $this->colors['cream'], $y);
            $y += 6;
        }

        $y += 15;
        return $y;
    }

    private function drawFooter()
    {
        $footerY = $this->height - 50;
        imagefilledrectangle($this->img, 0, $this->height - 8, $this->width, $this->height, $this->colors['saffron']);
        $this->drawCenteredText('🚩 हर हर महादेव | भारत माता की जय 🚩', $this->fontRegular, 16, $this->colors['text_muted'], $footerY - 15);
    }

    private function drawDivider(int $y): int
    {
        $y += 10;
        $dashLen = 8;
        $gap = 6;
        $startX = 100;
        $endX = $this->width - 100;
        
        for ($x = $startX; $x < $endX; $x += $dashLen + $gap) {
            imageline($this->img, $x, $y, min($x + $dashLen, $endX), $y, $this->colors['border']);
        }
        
        $y += 10;
        return $y;
    }

    private function drawCenteredText(string $text, string $font, int $size, $color, int $y): int
    {
        $bbox = imagettfbbox($size, 0, $font, $text);
        $textWidth = abs($bbox[2] - $bbox[0]);
        $x = ($this->width - $textWidth) / 2;
        imagettftext($this->img, $size, 0, (int)$x, $y + $size, $color, $font, $text);
        return $y + $size;
    }

    /**
     * Wrap text to fit within a given pixel width.
     */
    private function wrapText(string $text, string $font, int $size, int $maxWidth): array
    {
        $words = explode(' ', $text);
        $lines = [];
        $currentLine = '';

        foreach ($words as $word) {
            $testLine = $currentLine ? $currentLine . ' ' . $word : $word;
            $bbox = imagettfbbox($size, 0, $font, $testLine);
            $testWidth = abs($bbox[2] - $bbox[0]);

            if ($testWidth > $maxWidth && $currentLine !== '') {
                $lines[] = $currentLine;
                $currentLine = $word;
            } else {
                $currentLine = $testLine;
            }
        }
        if ($currentLine !== '') {
            $lines[] = $currentLine;
        }

        return $lines;
    }
}
