<?php
/**
 * PanchangCalculator.php — Fixed v3
 *
 * ROOT CAUSE OF ALL WRONG TITHI OUTPUT:
 * The old calculator used refJDN = 2461178.2 (May 17, 2026) as the New Moon reference.
 * The ACTUAL New Moon is May 16, 2026 at 20:01 UTC.
 * This 1-day error shifted every tithi calculation by ~1-2 tithis.
 *
 * This version uses a full USNO-sourced New Moon table for 2024–2030,
 * finds the correct preceding new moon for any given date, and computes
 * tithi, paksha, nakshatra, rashi, and maah accurately.
 *
 * VERIFIED: May 11, 2026 → Krishna Navami ✓
 *           May 12, 2026 → Krishna Dashami ✓  (transitioning near Purnima in Amant)
 */

class PanchangCalculator {

    // ─── USNO New Moon reference table (UTC times) ───────────────────────────
    // Format: [year, month, day, hour_utc, minute_utc]
    // Source: US Naval Observatory (https://aa.usno.navy.mil/data/MoonPhases)
    private $newMoons = [
        // 2024
        [2024,1,11,11,57],[2024,2,9,22,59],[2024,3,10,9,0],[2024,4,8,18,20],
        [2024,5,8,3,22],[2024,6,6,12,38],[2024,7,5,22,57],[2024,8,4,11,13],
        [2024,9,3,1,56],[2024,10,2,18,49],[2024,11,1,12,47],[2024,12,1,6,21],
        [2024,12,30,22,27],
        // 2025
        [2025,1,29,12,36],[2025,2,28,0,44],[2025,3,29,10,58],[2025,4,27,19,31],
        [2025,5,27,3,2],[2025,6,25,10,31],[2025,7,24,19,11],[2025,8,23,6,7],
        [2025,9,21,19,54],[2025,10,21,12,25],[2025,11,20,6,47],[2025,12,20,1,43],
        // 2026
        [2026,1,18,19,52],[2026,2,17,12,1],[2026,3,19,1,23],[2026,4,17,11,52],
        [2026,5,16,20,1], // ← KEY FIX: was wrongly set to May 17 in old code
        [2026,6,15,2,54],[2026,7,14,9,44],[2026,8,12,17,36],
        [2026,9,11,3,27],[2026,10,10,16,50],[2026,11,9,9,4],[2026,12,9,3,52],
        // 2027
        [2027,1,7,23,25],[2027,2,6,18,0],[2027,3,8,11,0],[2027,4,7,2,52],
        [2027,5,6,17,1],[2027,6,5,5,25],[2027,7,4,15,57],[2027,8,2,0,45],
        [2027,8,31,8,42],[2027,9,29,16,35],[2027,10,29,1,35],[2027,11,27,12,25],
        [2027,12,27,1,52],
        // 2028
        [2028,1,25,18,13],[2028,2,24,12,27],[2028,3,25,6,0],[2028,4,23,22,47],
        [2028,5,23,13,55],[2028,6,22,3,56],[2028,7,21,16,38],[2028,8,20,4,44],
        [2028,9,18,16,47],[2028,10,18,5,57],[2028,11,16,20,19],[2028,12,16,11,7],
        // 2029
        [2029,1,15,2,27],[2029,2,13,18,31],[2029,3,15,11,1],[2029,4,14,3,23],
        [2029,5,13,19,8],[2029,6,12,9,52],[2029,7,11,23,51],[2029,8,10,13,55],
        [2029,9,9,3,45],[2029,10,8,17,16],[2029,11,7,6,22],[2029,12,6,19,52],
        // 2030
        [2030,1,5,9,49],[2030,2,4,0,7],[2030,3,5,14,36],[2030,4,4,5,15],
        [2030,5,3,19,12],[2030,6,2,8,21],[2030,7,1,20,35],[2030,7,31,8,11],
        [2030,8,29,19,43],[2030,9,28,7,54],[2030,10,27,21,36],[2030,11,26,13,47],
        [2030,12,26,8,32],
    ];

    // ─── Full Moon table (USNO) for maah calculation ────────────────────────
    // Format: [year, month, day]
    private $fullMoons = [
        [2024,1,25],[2024,2,24],[2024,3,25],[2024,4,23],[2024,5,23],[2024,6,22],
        [2024,7,21],[2024,8,19],[2024,9,18],[2024,10,17],[2024,11,15],[2024,12,15],
        [2025,1,13],[2025,2,12],[2025,3,14],[2025,4,13],[2025,5,12],[2025,6,11],
        [2025,7,10],[2025,8,9],[2025,9,7],[2025,10,7],[2025,11,5],[2025,12,4],
        [2026,1,3],[2026,2,1],[2026,3,3],[2026,4,2],[2026,5,1],
        [2026,5,31], // second full moon in May 2026 (Blue Moon)
        [2026,6,29],[2026,7,29],[2026,8,28],[2026,9,26],[2026,10,26],
        [2026,11,24],[2026,12,24],
        [2027,1,22],[2027,2,20],[2027,3,22],[2027,4,20],[2027,5,20],[2027,6,19],
        [2027,7,18],[2027,8,17],[2027,9,15],[2027,10,15],[2027,11,13],[2027,12,13],
        [2028,1,12],[2028,2,10],[2028,3,11],[2028,4,10],[2028,5,9],[2028,6,8],
        [2028,7,7],[2028,8,5],[2028,9,4],[2028,10,3],[2028,11,2],[2028,12,1],
        [2028,12,31],
        [2029,1,30],[2029,2,28],[2029,3,30],[2029,4,28],[2029,5,28],[2029,6,26],
        [2029,7,25],[2029,8,24],[2029,9,22],[2029,10,22],[2029,11,20],[2029,12,20],
        [2030,1,18],[2030,2,17],[2030,3,19],[2030,4,17],[2030,5,17],[2030,6,15],
        [2030,7,15],[2030,8,13],[2030,9,12],[2030,10,11],[2030,11,10],[2030,12,9],
    ];

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
        $ayanamsa = 24.19;
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
        $karanaNum = (int) floor($phase / 6.0) + 1;
        if ($karanaNum > 60) $karanaNum = 60;
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
     */
    private function getPrecedingNewMoonJDN(float $targetJdn): float {
        $best = null;
        foreach ($this->newMoons as $nm) {
            [$y, $mo, $d, $h, $mi] = $nm;
            // Convert UTC to JDN
            $nmJdn = $this->gregorianToJDN($y, $mo, $d) + ($h + $mi / 60.0) / 24.0;
            if ($nmJdn <= $targetJdn) {
                if ($best === null || $nmJdn > $best) {
                    $best = $nmJdn;
                }
            }
        }
        // Fallback: if date is outside table range, use approximation
        if ($best === null) {
            $lunarMonth = 29.530588853;
            $knownNm    = 2461177.334; // May 16, 2026 20:01 UTC
            $cycles     = floor(($targetJdn - $knownNm) / $lunarMonth);
            $best       = $knownNm + $cycles * $lunarMonth;
        }
        return $best;
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
     * Purnimant: month named for the Full Moon it contains (starts on Krishna Paksha).
     * Amant: month named for the New Moon that ends it (starts on Shukla Paksha).
     */
    private function getMaah(int $year, int $month, int $day, string $paksha): array {
        // High-precision table for 2026 Amant months (starts day after Amavasya)
        $amant2026 = [
            '2025-12-21' => ['Pausha', 'पौष'],
            '2026-01-19' => ['Magha', 'माघ'],
            '2026-02-18' => ['Phalguna', 'फाल्गुन'],
            '2026-03-20' => ['Chaitra', 'चैत्र'],
            '2026-04-18' => ['Vaishakha', 'वैशाख'],
            '2026-05-17' => ['Adhik Jyeshtha', 'अधिक ज्येष्ठ'],
            '2026-06-16' => ['Nija Jyeshtha', 'निज ज्येष्ठ'],
            '2026-07-15' => ['Ashadha', 'आषाढ़'],
            '2026-08-13' => ['Shravana', 'श्रावण'],
            '2026-09-12' => ['Bhadrapada', 'भाद्रपद'],
            '2026-10-11' => ['Ashwin', 'आश्विन'],
            '2026-11-10' => ['Kartik', 'कार्तिक'],
            '2026-12-10' => ['Margashirsha', 'मार्गशीर्ष'],
            '2027-01-08' => ['Pausha', 'पौष']
        ];

        $currentDate = sprintf('%04d-%02d-%02d', $year, $month, $day);
        
        if ($year === 2026 || ($year === 2025 && $month === 12) || ($year === 2027 && $month === 1)) {
            $currentAmant = ['Vaishakha', 'वैशाख'];
            $nextAmant = ['Jyeshtha', 'ज्येष्ठ'];
            
            $dates = array_keys($amant2026);
            for ($i = 0; $i < count($dates) - 1; $i++) {
                if ($currentDate >= $dates[$i] && $currentDate < $dates[$i+1]) {
                    $currentAmant = $amant2026[$dates[$i]];
                    $nextAmant = $amant2026[$dates[$i+1]];
                    break;
                }
            }

            $amantEn = $currentAmant[0];
            $amantHi = $currentAmant[1];
            
            // Purnimant month is the same as Amant in Shukla Paksha, 
            // but advances to the next month during Krishna Paksha.
            if ($paksha === 'Krishna') {
                $purnimantEn = $nextAmant[0];
                $purnimantHi = $nextAmant[1];
            } else {
                $purnimantEn = $amantEn;
                $purnimantHi = $amantHi;
            }

            return [
                'purnimant'       => $purnimantEn,
                'purnimant_hindi' => $purnimantHi,
                'amant'           => $amantEn,
                'amant_hindi'     => $amantHi,
            ];
        }

        // Fallback for other years (approximate)
        $nextFmIndex = -1;
        foreach ($this->fullMoons as $i => $fm) {
            [$fy, $fm2, $fd] = $fm;
            if ($fy > $year || ($fy === $year && $fm2 > $month) || ($fy === $year && $fm2 === $month && $fd >= $day)) {
                $nextFmIndex = $i;
                break;
            }
        }

        if ($nextFmIndex === -1) {
            return ['purnimant'=>'Vaishakha','purnimant_hindi'=>'वैशाख','amant'=>'Vaishakha','amant_hindi'=>'वैशाख'];
        }

        $fm      = $this->fullMoons[$nextFmIndex];
        $fmMonth = $fm[1];

        $gregorianToPurnimant = [
            1=>'Pausha',2=>'Magha',3=>'Phalguna',4=>'Chaitra',5=>'Vaishakha',6=>'Jyeshtha',
            7=>'Ashadha',8=>'Shravana',9=>'Bhadrapada',10=>'Ashwin',11=>'Kartik',12=>'Margashirsha',
        ];
        $hindiMap = [
            'Pausha'=>'पौष','Magha'=>'माघ','Phalguna'=>'फाल्गुन','Chaitra'=>'चैत्र',
            'Vaishakha'=>'वैशाख','Jyeshtha'=>'ज्येष्ठ','Ashadha'=>'आषाढ़',
            'Shravana'=>'श्रावण','Bhadrapada'=>'भाद्रपद','Ashwin'=>'आश्विन',
            'Kartik'=>'कार्तिक','Margashirsha'=>'मार्गशीर्ष',
        ];

        $purnimant = $gregorianToPurnimant[$fmMonth] ?? 'Vaishakha';
        $purnimantHindi = $hindiMap[$purnimant] ?? $purnimant;

        return [
            'purnimant'       => $purnimant,
            'purnimant_hindi' => $purnimantHindi,
            'amant'           => $purnimant,
            'amant_hindi'     => $purnimantHindi,
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
