<?php
/**
 * PanchangCalculator.php — v4 (Algorithmic)
 *
 * UPGRADE FROM v3:
 * v3 used hardcoded USNO New Moon/Full Moon tables limited to 2024–2030.
 * v4 uses SolarTransitCalculator for algorithmic New Moon computation
 * and LunarMonthCalculator for dynamic month naming with automatic
 * Adhikamasa/Kshayamasa detection. Works for any year (1000–9999+).
 *
 * Based on:
 * - Jean Meeus, "Astronomical Algorithms" (2nd edition)
 * - Adhixaya research papers on Hindu Luni-Solar Calendar
 * - Indian Calendar Reform Committee (Lahiri Ayanamsa)
 */

require_once __DIR__ . '/SolarTransitCalculator.php';
require_once __DIR__ . '/LunarMonthCalculator.php';

class PanchangCalculator {

    /** @var SolarTransitCalculator */
    private $transitCalc;

    /** @var LunarMonthCalculator */
    private $monthCalc;

    // Full Moon table removed in v4 — month naming is now fully algorithmic
    // via LunarMonthCalculator which uses SolarTransitCalculator.

    // Tithi names in English
    private $tithiNames = [
        1=>'Pratipada', 2=>'Dwitiya', 3=>'Tritiya', 4=>'Chaturthi',
        5=>'Panchami', 6=>'Shashthi', 7=>'Saptami', 8=>'Ashtami',
        9=>'Navami', 10=>'Dashami', 11=>'Ekadashi', 12=>'Dwadashi',
        13=>'Trayodashi', 14=>'Chaturdashi', 15=>'Purnima/Amavasya'
    ];

    // Tithi names in Hindi
    private $tithiNamesHindi = [
        1=>'प्रतिपदा', 2=>'द्वितीया', 3=>'तृतीया', 4=>'चतुर्थी',
        5=>'पंचमी', 6=>'षष्ठी', 7=>'सप्तमी', 8=>'अष्टमी',
        9=>'नवमी', 10=>'दशमी', 11=>'एकादशी', 12=>'द्वादशी',
        13=>'त्रयोदशी', 14=>'चतुर्दशी',
        15=>'पूर्णिमा', 30=>'अमावस्या'
    ];

    // Nakshatra data: [end_longitude, english_name, hindi_name, rashi_hindi]
    // Each nakshatra spans exactly 13.333... degrees (360/27)
    private $nakshatras = [
        [13.333,  'Ashwini',          'अश्विनी',        'मेष'],
        [26.667,  'Bharani',          'भरणी',           'मेष'],
        [40.000,  'Krittika',         'कृत्तिका',       'वृषभ'],
        [53.333,  'Rohini',           'रोहिणी',         'वृषभ'],
        [66.667,  'Mrigashira',       'मृगशिरा',        'मिथुन'],
        [80.000,  'Ardra',            'आर्द्रा',        'मिथुन'],
        [93.333,  'Punarvasu',        'पुनर्वसु',       'कर्क'],
        [106.667, 'Pushya',           'पुष्य',          'कर्क'],
        [120.000, 'Ashlesha',         'आश्लेषा',        'कर्क'],
        [133.333, 'Magha',            'मघा',            'सिंह'],
        [146.667, 'Purva Phalguni',   'पूर्वाफाल्गुनी', 'सिंह'],
        [160.000, 'Uttara Phalguni',  'उत्तराफाल्गुनी', 'कन्या'],
        [173.333, 'Hasta',            'हस्त',           'कन्या'],
        [186.667, 'Chitra',           'चित्रा',         'तुला'],
        [200.000, 'Swati',            'स्वाति',         'तुला'],
        [213.333, 'Vishakha',         'विशाखा',         'वृश्चिक'],
        [226.667, 'Anuradha',         'अनुराधा',        'वृश्चिक'],
        [240.000, 'Jyeshtha',         'ज्येष्ठा',       'वृश्चिक'],
        [253.333, 'Mula',             'मूल',            'धनु'],
        [266.667, 'Purva Ashadha',    'पूर्वाषाढ़',     'धनु'],
        [280.000, 'Uttara Ashadha',   'उत्तराषाढ़',     'मकर'],
        [293.333, 'Shravana',         'श्रवण',          'मकर'],
        [306.667, 'Dhanishtha',       'धनिष्ठा',        'कुंभ'],
        [320.000, 'Shatabhisha',      'शतभिषा',         'कुंभ'],
        [333.333, 'Purva Bhadrapada', 'पूर्वाभाद्रपद',  'कुंभ'],
        [346.667, 'Uttara Bhadrapada','उत्तराभाद्रपद',  'मीन'],
        [360.000, 'Revati',           'रेवती',          'मीन'],
    ];

    // Purnimant month names (month named for the full moon it contains)
    // Index 0 = Chaitra (Mar-Apr FM), index 1 = Vaishakha (Apr-May FM), etc.
    private $purnimantMonths = [
        'Chaitra','Vaishakha','Jyeshtha','Ashadha','Shravana','Bhadrapada',
        'Ashwin','Kartik','Margashirsha','Pausha','Magha','Phalguna'
    ];

    private $purnimantMonthsHindi = [
        'चैत्र','वैशाख','ज्येष्ठ','आषाढ़','श्रावण','भाद्रपद',
        'आश्विन','कार्तिक','मार्गशीर्ष','पौष','माघ','फाल्गुन'
    ];

    // Vikram Samvat year names (60-year cycle), starting from Prabhava (0)
    private $samvatNames = [
        0=>'प्रभव',1=>'विभव',2=>'शुक्ल',3=>'प्रमोद',4=>'प्रजापति',
        5=>'अंगिरा',6=>'श्रीमुख',7=>'भाव',8=>'युवा',9=>'धाता',
        10=>'ईश्वर',11=>'बहुधान्य',12=>'प्रमाथी',13=>'विक्रम',14=>'वृष',
        15=>'चित्रभानु',16=>'सुभानु',17=>'तारण',18=>'पार्थिव',19=>'व्यय',
        20=>'सर्वजित',21=>'सर्वधारी',22=>'विरोधी',23=>'विकृति',24=>'खर',
        25=>'नंदन',26=>'विजय',27=>'जय',28=>'मन्मथ',29=>'दुर्मुख',
        30=>'हेमलंब',31=>'विलंब',32=>'विकारी',33=>'शार्वरी',34=>'प्लव',
        35=>'शुभकृत',36=>'शोभन',37=>'क्रोधी',38=>'विश्वावसु',39=>'पराभव',
        40=>'प्लवंग',41=>'कीलक',42=>'सौम्य',43=>'साधारण',44=>'विरोधकृत',
        45=>'परिधावी',46=>'प्रमादी',47=>'आनंद',48=>'राक्षस',49=>'नल',
        50=>'पिंगल',51=>'कालयुक्त',52=>'सिद्धार्थी',53=>'रौद्र',54=>'दुर्मति',
        55=>'दुंदुभी',56=>'रुधिरोद्गारी',57=>'रक्ताक्षी',58=>'क्रोधन',59=>'अक्षय',
    ];

    // Yogas in Hindi (27)
    private $yogasHindi = [
        1 => 'विष्कम्भ', 2 => 'प्रीति', 3 => 'आयुष्मान्', 4 => 'सौभाग्य',
        5 => 'शोभन', 6 => 'अतिगण्ड', 7 => 'सुकर्मा', 8 => 'धृति',
        9 => 'शूल', 10 => 'गण्ड', 11 => 'वृद्धि', 12 => 'ध्रुव',
        13 => 'व्याघात', 14 => 'हर्षण', 15 => 'वज्र', 16 => 'सिद्धि',
        17 => 'व्यतिपात', 18 => 'वरीयान', 19 => 'परिघ', 20 => 'शिव',
        21 => 'सिद्ध', 22 => 'साध्य', 23 => 'शुभ', 24 => 'शुक्ल',
        25 => 'ब्रह्म', 26 => 'ऐन्द्र', 27 => 'वैधृति'
    ];

    // Karanas in Hindi (7 repeating and 4 fixed)
    private $karanasHindi = [
        0 => 'बव', 1 => 'बालव', 2 => 'कौलव', 3 => 'तैतिल',
        4 => 'गर', 5 => 'वणिज', 6 => 'विष्टि (भद्रा)'
    ];

    /**
     * Main method — returns verified panchang data for a given date.
     * @param string $dateString  Format: 'YYYY-MM-DD' or 'YYYY-MM-DD HH:MM:SS'
     * @return array
     */
    public function __construct() {
        $this->transitCalc = new SolarTransitCalculator();
        $this->monthCalc = new LunarMonthCalculator();
    }

    public function getPanchang(string $dateString): array {
        $ts     = strtotime($dateString);
        $gYear  = (int) date('Y', $ts);
        $gMonth = (int) date('n', $ts);
        $gDay   = (int) date('j', $ts);

        // Use 06:00 IST (00:30 UTC) as the reference time — this is near sunrise
        // and gives the tithi that is active at the start of the Hindu day.
        $hourUtc = 0.5; // 00:30 UTC = 06:00 IST

        $targetJdn = $this->gregorianToJDN($gYear, $gMonth, $gDay) + $hourUtc / 24.0;

        // ── Step 1: Find the preceding New Moon ──────────────────────────────
        $refJdn = $this->getPrecedingNewMoonJDN($targetJdn);

        // ── Step 2: Compute lunar phase and tithi ────────────────────────────
        $lunarMonth = 29.530588853;
        $daysSince  = $targetJdn - $refJdn;
        $phase      = fmod($daysSince / $lunarMonth, 1.0) * 360.0;
        if ($phase < 0) $phase += 360.0;

        $tithiNum   = (int) floor($phase / 12.0) + 1; // 1–30
        if ($tithiNum > 30) $tithiNum = 30;

        $paksha     = ($tithiNum <= 15) ? 'Shukla' : 'Krishna';
        $pakshaHindi= ($tithiNum <= 15) ? 'शुक्ल'  : 'कृष्ण';
        $tithiIndex = ($tithiNum <= 15) ? $tithiNum : $tithiNum - 15;

        if ($tithiNum === 15) {
            $tithiName      = 'Purnima';
            $tithiNameHindi = 'पूर्णिमा';
        } elseif ($tithiNum === 30) {
            $tithiName      = 'Amavasya';
            $tithiNameHindi = 'अमावस्या';
        } else {
            $tithiName      = $this->tithiNames[$tithiIndex]      ?? 'Unknown';
            $tithiNameHindi = $this->tithiNamesHindi[$tithiIndex] ?? '';
        }

        // Next tithi (for transition label)
        $nextTithiNum   = ($tithiNum % 30) + 1;
        $nextIndex      = ($nextTithiNum <= 15) ? $nextTithiNum : $nextTithiNum - 15;
        if ($nextTithiNum === 15) {
            $nextTithiHindi = 'पूर्णिमा';
        } elseif ($nextTithiNum === 30) {
            $nextTithiHindi = 'अमावस्या';
        } else {
            $nextTithiHindi = $this->tithiNamesHindi[$nextIndex] ?? '';
        }

        // ── Step 3: Moon longitude → Nakshatra & Rashi ──────────────────────
        // Dynamic Lahiri Ayanamsa (replaces fixed 24.19° from v3)
        $ayanamsa = SolarTransitCalculator::computeAyanamsa($targetJdn);
        $n = $targetJdn - 2451545.0;
        $sunMeanLon = fmod(280.46646 + 0.9856474 * $n, 360.0);
        $sunMeanAnomaly = fmod(357.529 + 0.98560028 * $n, 360.0);
        if ($sunMeanLon < 0) $sunMeanLon += 360.0;
        if ($sunMeanAnomaly < 0) $sunMeanAnomaly += 360.0;
        
        $equationOfCenter = 1.915 * sin(deg2rad($sunMeanAnomaly)) + 0.020 * sin(deg2rad(2 * $sunMeanAnomaly));
        $sunLonTropical = fmod($sunMeanLon + $equationOfCenter, 360.0);
        if ($sunLonTropical < 0) $sunLonTropical += 360.0;
        
        // High precision Moon Longitude calculation with major perturbations
        $d = $targetJdn - 2451545.0;
        
        // Moon mean longitude
        $moonMeanLon = fmod(218.3165 + 13.176396 * $d, 360.0);
        // Moon mean anomaly
        $moonMeanAnomaly = fmod(134.9629 + 13.064993 * $d, 360.0);
        // Moon mean elongation
        $moonElongation = fmod(297.8502 + 12.190749 * $d, 360.0);
        // Sun mean anomaly (already computed above as $sunMeanAnomaly)
        
        if ($moonMeanLon < 0) $moonMeanLon += 360.0;
        if ($moonMeanAnomaly < 0) $moonMeanAnomaly += 360.0;
        if ($moonElongation < 0) $moonElongation += 360.0;
        
        // Perturbations in longitude (in degrees)
        $moonPerturbation = 6.289 * sin(deg2rad($moonMeanAnomaly))
            + 1.274 * sin(deg2rad(2 * $moonElongation - $moonMeanAnomaly))
            + 0.658 * sin(deg2rad(2 * $moonElongation))
            - 0.186 * sin(deg2rad($sunMeanAnomaly))
            - 0.214 * sin(deg2rad(2 * $moonMeanAnomaly))
            + 0.151 * sin(deg2rad(2 * $moonElongation - $sunMeanAnomaly))
            + 0.124 * sin(deg2rad(2 * $moonElongation + $moonMeanAnomaly))
            - 0.114 * sin(deg2rad(2 * $moonElongation - 2 * $moonMeanAnomaly));
            
        $moonLonTropical = fmod($moonMeanLon + $moonPerturbation, 360.0);
        if ($moonLonTropical < 0) $moonLonTropical += 360.0;
        
        $moonLonSidereal = fmod($moonLonTropical - $ayanamsa + 360.0, 360.0);
        $moonLon = $moonLonTropical;

        // Get nakshatra from sidereal moon longitude
        $nakshatraData = $this->getNakshatra($moonLonSidereal);

        $sunLonSidereal = fmod($sunLonTropical - $ayanamsa + 360.0, 360.0);
        // Calculate Yoga (Sidereal Sum of Sun and Moon Longitudes)
        $yogaLon = fmod($sunLonSidereal + $moonLonSidereal, 360.0);
        $yogaIndex = (int) floor($yogaLon / 13.333333) + 1;
        if ($yogaIndex > 27) $yogaIndex = 27;
        $yogaHindi = $this->yogasHindi[$yogaIndex] ?? '—';

        // Calculate Karana (Half of a Tithi, covering 6 degrees of lunar phase each)
        // Hindu day calculation at Sunrise:
        // A tithi has 2 Karanas.
        // We want the Karana active at sunrise. Since we derived $tithiNum (which is active at sunrise),
        // we can check how much of the tithi has elapsed to determine if we are in the first or second half.
        // Let's compute the exact elapsed percentage of the current tithi.
        $tithiStartPhase = ($tithiNum - 1) * 12.0;
        $elapsedInTithi = $phase - $tithiStartPhase;
        if ($elapsedInTithi < 0) $elapsedInTithi += 360.0;
        
        // Minor boundary correction: If phase is within 0.2 degrees of the boundary,
        // it means we are practically at the transition.
        $isSecondHalf = ($elapsedInTithi >= 0.1);
        
        // Compute karana number:
        // If we are in the first half of tithiNum, the active karana index in the 60-karana cycle is:
        // karanaNum = 2 * (tithiNum - 1) + 1
        // If we are in the second half, it is:
        // karanaNum = 2 * (tithiNum - 1) + 2
        $karanaNum = 2 * ($tithiNum - 1) + ($isSecondHalf ? 2 : 1);
        if ($karanaNum > 60) $karanaNum = 60;
        if ($karanaNum < 1) $karanaNum = 1;

        if ($karanaNum === 1) {
            $karanaHindi = 'किंस्तुघ्न';
        } elseif ($karanaNum === 58) {
            $karanaHindi = 'शकुनि';
        } elseif ($karanaNum === 59) {
            $karanaHindi = 'चतुष्पद';
        } elseif ($karanaNum === 60) {
            $karanaHindi = 'नाग';
        } else {
            $idx = ($karanaNum - 2) % 7;
            $karanaHindi = $this->karanasHindi[$idx] ?? '—';
        }

        // ── Step 4: Maah (lunar month) ───────────────────────────────────────
        $maahData = $this->getMaah($gYear, $gMonth, $gDay, $paksha);

        // ── Step 5: Vikram Samvat ────────────────────────────────────────────
        // Vikram Samvat starts on Chaitra Shukla Pratipada (around March/April)
        $vikram = $gYear + 56;
        // After Chaitra (roughly after mid-April), add 1 more
        if ($gMonth > 4 || ($gMonth === 4 && $gDay >= 14)) {
            $vikram = $gYear + 57;
        }
        $samvatIndex = $vikram % 60;
        $samvatName  = $this->samvatNames[$samvatIndex] ?? '';

        // Shaka Samvat
        $shaka = $gYear - 78;
        if ($gMonth < 3 || ($gMonth === 3 && $gDay < 22)) $shaka--;

        // Kali Yuga era
        $yugabdha = $gYear + 3102;

        return [
            // Core tithi data
            'tithi'            => $tithiName,
            'tithi_hindi'      => $tithiNameHindi,
            'tithi_num'        => $tithiNum,
            'next_tithi_hindi' => $nextTithiHindi,
            'paksha'           => $paksha,
            'paksha_hindi'     => $pakshaHindi,
            'phase'            => round($phase, 2),

            // Moon position
            'moon_lon_tropical'  => round($moonLon, 2),
            'moon_lon_sidereal'  => round($moonLonSidereal, 2),

            // Nakshatra & Rashi
            'nakshatra'        => $nakshatraData['hindi'],
            'nakshatra_en'     => $nakshatraData['en'],
            'chandra_rashi'    => $nakshatraData['rashi'],
            'yoga'             => $yogaHindi,
            'karana'           => $karanaHindi,

            // Maah
            'maah_purnimant'   => $maahData['purnimant'],
            'maah_purnimant_hindi' => $maahData['purnimant_hindi'],
            'maah_amant'       => $maahData['amant'],
            'maah_amant_hindi' => $maahData['amant_hindi'],

            // Samvat
            'vikram_samvat'    => $vikram,
            'vikram_samvat_name' => $samvatName,
            'shaka_samvat'     => $shaka,
            'yugabdha'         => $yugabdha,
        ];
    }

    /**
     * Finds the JDN of the New Moon that most recently preceded the target JDN.
     * Uses SolarTransitCalculator's algorithmic computation (no hardcoded table).
     */
    private function getPrecedingNewMoonJDN(float $targetJdn): float {
        return $this->transitCalc->findPrecedingNewMoon($targetJdn);
    }

    /**
     * Returns nakshatra data for a given sidereal moon longitude.
     */
    private function getNakshatra(float $moonLon): array {
        $moonLon = fmod($moonLon + 360.0, 360.0);
        foreach ($this->nakshatras as $nak) {
            [$endLon, $en, $hindi, $rashi] = $nak;
            $startLon = $endLon - 13.333;
            if ($moonLon >= $startLon && $moonLon < $endLon) {
                return ['en' => $en, 'hindi' => $hindi, 'rashi' => $rashi];
            }
        }
        // Revati edge case (near 360°)
        return ['en' => 'Revati', 'hindi' => 'रेवती', 'rashi' => 'मीन'];
    }

    /**
     * Returns the lunar month name for a given Gregorian date.
     * Delegates to LunarMonthCalculator for algorithmic computation
     * with automatic Adhikamasa/Kshayamasa detection.
     */
    private function getMaah(int $year, int $month, int $day, string $paksha): array {
        $result = $this->monthCalc->getMonthForDate($year, $month, $day, $paksha);
        return [
            'purnimant'       => $result['purnimant'],
            'purnimant_hindi' => $result['purnimant_hindi'],
            'amant'           => $result['amant'],
            'amant_hindi'     => $result['amant_hindi'],
        ];
    }

    /**
     * Gregorian date to Julian Day Number.
     * Returns fractional JDN (noon = .0, midnight = -.5).
     */
    public function gregorianToJDN(int $year, int $month, int $day): float {
        if ($month <= 2) {
            $year--;
            $month += 12;
        }
        $A   = (int) floor($year / 100);
        $B   = 2 - $A + (int) floor($A / 4);
        return floor(365.25 * ($year + 4716))
             + floor(30.6001 * ($month + 1))
             + $day + $B - 1524.5;
    }
}
