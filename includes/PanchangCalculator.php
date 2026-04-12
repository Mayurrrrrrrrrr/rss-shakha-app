<?php
/**
 * Panchang Calculator - Tithi in Vikram Samvat & Shak Samvat
 * Pure PHP - No external API needed
 * Based on Surya Siddhanta approximation
 */

class PanchangCalculator {

    // ─── Tithi names ───────────────────────────────────────────────
    private $tithiNames = [
        1=>'Pratipada', 2=>'Dwitiya', 3=>'Tritiya', 4=>'Chaturthi',
        5=>'Panchami', 6=>'Shashthi', 7=>'Saptami', 8=>'Ashtami',
        9=>'Navami', 10=>'Dashami', 11=>'Ekadashi', 12=>'Dwadashi',
        13=>'Trayodashi', 14=>'Chaturdashi', 15=>'Purnima/Amavasya'
    ];

    // ─── Month names (starting from Chaitra) ───────────────────────
    private $maahNames = [
        0=>'Chaitra', 1=>'Vaishakha', 2=>'Jyeshtha', 3=>'Ashadha',
        4=>'Shravana', 5=>'Bhadrapada', 6=>'Ashwin', 7=>'Kartik',
        8=>'Margashirsha', 9=>'Pausha', 10=>'Magha', 11=>'Phalguna'
    ];

    // ─── Special Tithi activities (what to do) ─────────────────────
    private $tithiActivity = [
        1  => 'Auspicious for new beginnings, starting journeys, and important work.',
        2  => 'Good for building, agriculture, and artistic work.',
        3  => 'Excellent for education, learning, and starting new ventures.',
        4  => 'Avoid major new work. Suitable for devotion and prayers.',
        5  => 'Very auspicious. Good for medicine, healing, and spiritual practices.',
        6  => 'Good for debate, courage-related work, and valor.',
        7  => 'Good for travel, vehicles, and agricultural work.',
        8  => 'Avoid auspicious events. Suitable for surgery or intense physical work.',
        9  => 'Good for destruction of enemies, legal matters, and fierce activities.',
        10 => 'Excellent for all auspicious works, trade, and travel.',
        11 => 'Most auspicious. Best for fasting (Ekadashi Vrat), spiritual practices.',
        12 => 'Good for religious acts, donations, and helping others.',
        13 => 'Very auspicious for all activities, especially fine arts.',
        14 => 'Avoid new beginnings. Suitable for Shiva worship and tantric practices.',
        15 => 'Full Moon (Purnima): Highly auspicious. New Moon (Amavasya): Ancestor worship, avoid new work.'
    ];

    /**
     * Convert Gregorian date to Julian Day Number
     */
    public function gregorianToJDN($year, $month, $day) {
        $a = intval((14 - $month) / 12);
        $y = $year + 4800 - $a;
        $m = $month + 12 * $a - 3;
        $jdn = $day + intval((153 * $m + 2) / 5) + 365 * $y
               + intval($y / 4) - intval($y / 100) + intval($y / 400) - 32045;
        return $jdn;
    }

    /**
     * Get Sun's approximate longitude (degrees) for a JDN
     * Using mean longitude approximation
     */
    private function getSunLongitude($jdn) {
        // Days since J2000.0 (Jan 1.5, 2000)
        $n = $jdn - 2451545.0;
        // Mean longitude of Sun (degrees)
        $L = fmod(280.46646 + 0.9856474 * $n, 360);
        // Mean anomaly
        $g = fmod(357.52911 + 0.9856003 * $n, 360);
        $gRad = deg2rad($g);
        // Equation of center
        $C = (1.914602 - 0.004817 * ($n / 36525)) * sin($gRad)
           + 0.019993 * sin(2 * $gRad)
           + 0.000289 * sin(3 * $gRad);
        // True longitude
        $sunLon = fmod($L + $C + 360, 360);
        return $sunLon;
    }

    /**
     * Get Moon's approximate longitude (degrees) for a JDN
     */
    private function getMoonLongitude($jdn) {
        $n = $jdn - 2451545.0;
        // Moon's mean longitude
        $L0 = fmod(218.3165 + 13.17639648 * $n, 360);
        // Moon's mean anomaly
        $M  = fmod(134.9634 + 13.06499295 * $n, 360);
        // Moon's argument of latitude
        $F  = fmod(93.2721 + 13.22935020 * $n, 360);
        // Sun's mean anomaly
        $Ms = fmod(357.5291 + 0.98560028 * $n, 360);

        $MRad  = deg2rad($M);
        $FRad  = deg2rad($F);
        $MsRad = deg2rad($Ms);
        $L0Rad = deg2rad($L0);

        // Simplified correction terms
        $correction = 6.289 * sin($MRad)
                    - 1.274 * sin(2 * $L0Rad - $MRad)
                    + 0.658 * sin(2 * $L0Rad)
                    - 0.214 * sin(2 * $MRad)
                    - 0.186 * sin($MsRad)
                    - 0.114 * sin(2 * $FRad)
                    + 0.059 * sin(2 * $L0Rad - 2 * $MsRad)
                    + 0.057 * sin(2 * $L0Rad - $MRad - $MsRad);

        $moonLon = fmod($L0 + $correction + 360, 360);
        return $moonLon;
    }

    /**
     * Calculate Tithi from Sun and Moon longitudes
     */
    public function calculateTithi($jdn) {
        $sunLon  = $this->getSunLongitude($jdn);
        $moonLon = $this->getMoonLongitude($jdn);

        $diff = fmod($moonLon - $sunLon + 360, 360);
        $tithiNum = intval($diff / 12) + 1; // 1 to 30

        $paksha = ($tithiNum <= 15) ? 'Shukla' : 'Krishna';

        // Normalize to 1-15 for name lookup
        $tithiIndex = ($tithiNum <= 15) ? $tithiNum : $tithiNum - 15;

        if ($tithiNum == 15) {
            $tithiName = 'Purnima';
        } elseif ($tithiNum == 30) {
            $tithiName = 'Amavasya';
        } else {
            $tithiName = $this->tithiNames[$tithiIndex] ?? 'Unknown';
        }

        return [
            'tithi_num'   => $tithiNum,
            'tithi_index' => $tithiIndex,
            'tithi_name'  => $tithiName,
            'paksha'      => $paksha,
            'sun_lon'     => round($sunLon, 4),
            'moon_lon'    => round($moonLon, 4),
            'diff'        => round($diff, 4),
        ];
    }

    /**
     * Calculate Vikram Samvat, Shak Samvat, Yugabdha, Maah
     */
    public function calculateSamvat($gYear, $gMonth, $gDay, $tithiNum) {
        if ($gMonth >= 4) {
            $vikramSamvat = $gYear + 57;
        } elseif ($gMonth == 3 && $gDay >= 15) {
            $vikramSamvat = $gYear + 57;
        } else {
            $vikramSamvat = $gYear + 56;
        }

        $shakSamvat = $vikramSamvat - 135;
        $yugabdha = $vikramSamvat + 3044; 

        $dayOfYear = date('z', mktime(0, 0, 0, $gMonth, $gDay, $gYear));
        $lunarOffset = ($dayOfYear - 80 + 366) % 366;
        $maahIndex = intval($lunarOffset / 30.44) % 12;
        $maahName  = $this->maahNames[$maahIndex];

        return [
            'vikram_samvat' => $vikramSamvat,
            'shak_samvat'   => $shakSamvat,
            'yugabdha'      => $yugabdha,
            'maah'          => $maahName,
        ];
    }

    public function getPanchang($dateString) {
        $ts    = strtotime($dateString);
        $gYear = (int) date('Y', $ts);
        $gMonth= (int) date('n', $ts);
        $gDay  = (int) date('j', $ts);

        $jdn = $this->gregorianToJDN($gYear, $gMonth, $gDay);
        $tithi = $this->calculateTithi($jdn);
        $samvat = $this->calculateSamvat($gYear, $gMonth, $gDay, $tithi['tithi_num']);

        return [
            'gregorian_date' => date('d F Y', $ts),
            'day_of_week'    => date('l', $ts),
            'yugabdha'       => $samvat['yugabdha'],
            'vikram_samvat'  => $samvat['vikram_samvat'],
            'shak_samvat'    => $samvat['shak_samvat'],
            'maah'           => $samvat['maah'],
            'paksha'         => $tithi['paksha'],
            'tithi'          => $tithi['tithi_name'],
            'tithi_num'      => $tithi['tithi_num'],
        ];
    }
}
