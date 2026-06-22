<?php
require_once __DIR__ . '/PanchangCalculator.php';

class PanchangHelper {
    /**
     * Get Panchang data for a specific date, fetching from cache or calculating and caching.
     */
    public static function getForDate($pdo, $date, $shakhaId = null) {
        // First check MySQL cache
        $stmt = $pdo->prepare("SELECT * FROM panchang_data WHERE panchang_date = ?");
        $stmt->execute([$date]);
        $cached = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($cached) {
            return self::formatResult($cached);
        }

        // Not cached, calculate it
        $calc = new PanchangCalculator();
        $result = $calc->getPanchang($date);

        // Map calculator output to table structure
        $tithi = $result['tithi_hindi'] ?? '';
        $paksha = $result['paksha_hindi'] ?? '';
        if ($paksha === 'शुक्ल' || $paksha === 'कृष्ण') {
            $paksha .= ' पक्ष';
        }
        $nakshatra = $result['nakshatra'] ?? '-';
        $vikramMonth = $result['maah_purnimant_hindi'] ?? '';
        $vikramSamvat = preg_replace('/\D/', '', (string)($result['vikram_samvat'] ?? ''));
        $shakaSamvat = preg_replace('/\D/', '', (string)($result['shaka_samvat'] ?? ''));
        $yugabdha = preg_replace('/\D/', '', (string)($result['yugabdha'] ?? ''));

        // Calculate real sunrise/sunset based on shakha location if available
        $sunrise = '06:00 AM';
        $sunset = '06:30 PM';
        
        if ($shakhaId) {
            // Default coords (e.g. Mumbai)
            $lat = 19.0760;
            $lon = 72.8777;
            
            // Try to find city and approx coords or if we add coords to shakhas table later
            $stmtShakha = $pdo->prepare("SELECT city_name FROM shakhas WHERE id = ?");
            $stmtShakha->execute([$shakhaId]);
            $city = $stmtShakha->fetchColumn();
            
            if ($city) {
                $cityCoords = self::getCityCoordinates($city);
                $lat = $cityCoords['lat'];
                $lon = $cityCoords['lon'];
            }
            
            $sunTimes = date_sun_info(strtotime($date), $lat, $lon);
            if ($sunTimes) {
                // Adjust for IST (+5:30)
                $sunriseTime = $sunTimes['sunrise'] + (5.5 * 3600);
                $sunsetTime = $sunTimes['sunset'] + (5.5 * 3600);
                $sunrise = gmdate('h:i A', $sunriseTime);
                $sunset = gmdate('h:i A', $sunsetTime);
            }
        }

        // Try to get AI utsav from cache as a bonus (optional, no fetching here)
        $utsav = '';
        if ($shakhaId) {
            $cacheKey = "shakha_{$shakhaId}_{$date}";
            $stmtAi = $pdo->prepare("SELECT response_json FROM ai_content_cache WHERE content_type='panchang' AND content_key=?");
            $stmtAi->execute([$cacheKey]);
            $cachedAiJson = $stmtAi->fetchColumn();
            if ($cachedAiJson) {
                $aiPanchang = json_decode($cachedAiJson, true);
                if (isset($aiPanchang['vrat_tyohar']) && $aiPanchang['vrat_tyohar'] !== 'null') {
                    $utsav = $aiPanchang['vrat_tyohar'];
                }
            }
        }

        // Extra Calculations (Yoga, Karana, Rahukaal, Chandra Times, Shubh Muhurt)
        $yoga = $result['yoga'] ?? '—';
        $karana = $result['karana'] ?? '—';
        $chandraRashi = $result['chandra_rashi'] ?? '—';

        $sunriseTs = strtotime("$date $sunrise");
        $sunsetTs = strtotime("$date $sunset");
        $dayOfWeek = (int) date('w', strtotime($date));
        $dayLength = $sunsetTs - $sunriseTs;

        // Rahukaal Calculation
        $rahuParts = [
            0 => 8, // Sunday
            1 => 2, // Monday
            2 => 7, // Tuesday
            3 => 5, // Wednesday
            4 => 6, // Thursday
            5 => 4, // Friday
            6 => 3  // Saturday
        ];
        $partLength = $dayLength / 8.0;
        $partNumber = $rahuParts[$dayOfWeek] ?? 8;
        $rahuStartTs = $sunriseTs + ($partNumber - 1) * $partLength;
        $rahuEndTs = $sunriseTs + $partNumber * $partLength;
        $rahukaal = date('h:i A', $rahuStartTs) . ' से ' . date('h:i A', $rahuEndTs);

        // Chandra Times Calculation (Moonrise / Moonset)
        $tithiNum = $result['tithi_num'] ?? 15;
        $tithiShift = $tithiNum % 30;
        $shiftMinutes = $tithiShift * 48.8;
        $moonriseTs = $sunriseTs + ($shiftMinutes * 60);
        $moonsetTs = $sunsetTs + ($shiftMinutes * 60);
        $chandraUdaya = date('h:i A', $moonriseTs);
        $chandraAsta = date('h:i A', $moonsetTs);

        // Shubh Muhurt Calculations
        // Abhijit
        $midday = $sunriseTs + ($dayLength / 2);
        $muhurtLength = $dayLength / 15.0;
        $abhijitStart = $midday - ($muhurtLength / 2);
        $abhijitEnd = $midday + ($muhurtLength / 2);
        $abhijit = date('h:i A', $abhijitStart) . ' से ' . date('h:i A', $abhijitEnd);

        // Vijay
        $vijayStart = $sunriseTs + (10 * $muhurtLength);
        $vijayEnd = $sunriseTs + (11 * $muhurtLength);
        $vijay = date('h:i A', $vijayStart) . ' से ' . date('h:i A', $vijayEnd);

        // Amrit Kaal
        // Amrit Kaal begins at a specific ghati position within the duration of the ruling Nakshatra.
        // It always lasts for 4 ghatis (96 minutes).
        // Since we calculate daily panchang at sunrise, we check which nakshatras are active during this 24-hour day.
        // For simplicity and high approximation under Surya Siddhanta:
        // We find the Nakshatra active at sunrise. Amrit Kaal starts after a specific fraction of the Nakshatra's duration.
        // Ghati offsets from Nakshatra start:
        $nakshatraAmritGhatiOffsets = [
            'अश्विनी' => 42, 'भरणी' => 24, 'कृत्तिका' => 30, 'रोहिणी' => 40,
            'मृगशिरा' => 14, 'आर्द्रा' => 11, 'पुनर्वसु' => 30, 'पुष्य' => 20,
            'आश्लेषा' => 32, 'मघा' => 30, 'पूर्वाफाल्गुनी' => 20, 'उत्तराफाल्गुनी' => 18,
            'हस्त' => 22, 'चित्रा' => 20, 'स्वाति' => 14, 'विशाखा' => 14,
            'अनुराधा' => 10, 'ज्येष्ठा' => 14, 'मूल' => 20, 'पूर्वाषाढ़' => 24,
            'उत्तराषाढ़' => 20, 'श्रवण' => 10, 'धनिष्ठा' => 10, 'शतभिषा' => 18,
            'पूर्वाभाद्रपद' => 16, 'उत्तराभाद्रपद' => 24, 'रेवती' => 30
        ];

        // The duration of a Nakshatra is typically around 60 ghatis (24 hours), but it varies.
        // A Nakshatra ends when moon longitude crosses a multiple of 13.3333 degrees.
        // Moon travels roughly 13.176 degrees per 24 hours (0.549 degrees per hour).
        $currentMoonLon = $result['moon_lon_sidereal'] ?? 0.0;
        $nakshatraIndex = (int) floor($currentMoonLon / 13.333333);
        $nextNakshatraBoundary = ($nakshatraIndex + 1) * 13.333333;
        $degreesToBoundary = $nextNakshatraBoundary - $currentMoonLon;
        if ($degreesToBoundary < 0) $degreesToBoundary += 360.0;
        
        $hoursToTransition = $degreesToBoundary / 0.549; // approx hours from sunrise to transition
        $nakshatraEndTs = $sunriseTs + ($hoursToTransition * 3600);
        $nakshatraStartTs = $nakshatraEndTs - (24 * 3600); // Approximate preceding start

        // Amrit Kaal starting ghati for this Nakshatra (out of 60 ghatis)
        $startGhati = $nakshatraAmritGhatiOffsets[$nakshatra] ?? 20;
        
        // Convert ghati to time (1 ghati = 24 minutes)
        // Offset from Nakshatra start = (startGhati / 60) * Nakshatra Duration
        $nakshatraDuration = $nakshatraEndTs - $nakshatraStartTs;
        $amritStartOffset = ($startGhati / 60.0) * $nakshatraDuration;
        
        $amritStart = $nakshatraStartTs + $amritStartOffset;
        $amritEnd = $amritStart + (96 * 60); // Amrit Kaal always lasts 4 ghatis = 96 minutes

        // If the calculated Amrit Kaal ends before sunrise, it belongs to the previous day/night.
        // If it starts after the next Nakshatra is active, or if it falls into a timeframe where we should
        // calculate it for the NEXT Nakshatra starting during this Hindu day:
        // Let's check if there is a Nakshatra transition during the day (before next sunrise).
        $nextDaySunriseTs = $sunriseTs + (24 * 3600);
        
        if ($amritEnd < $sunriseTs && $nakshatraEndTs < $nextDaySunriseTs) {
            // The current Nakshatra's Amrit Kaal is already in the past.
            // Calculate for the NEXT Nakshatra which starts today at $nakshatraEndTs.
            $nextNakshatraIndex = ($nakshatraIndex + 1) % 27;
            $nextNakshatraData = $calc->getPanchang(date('Y-m-d H:i:s', $nakshatraEndTs + 3600)); // Get next day details
            $nextNakshatraName = $nextNakshatraData['nakshatra'] ?? '';
            
            $nextStartGhati = $nakshatraAmritGhatiOffsets[$nextNakshatraName] ?? 20;
            // Approximate next Nakshatra duration as 24 hours
            $nextNakshatraDuration = 24 * 3600;
            $nextAmritStartOffset = ($nextStartGhati / 60.0) * $nextNakshatraDuration;
            
            $amritStart = $nakshatraEndTs + $nextAmritStartOffset;
            $amritEnd = $amritStart + (96 * 60);
        }

        $amritKaal = date('h:i A', $amritStart) . ' से ' . date('h:i A', $amritEnd);

        // Ravi Yoga
        $raviMap = [
            0 => ['हस्त', 'चित्रा', 'स्वाति', 'विशाखा', 'अनुराधा', 'ज्येष्ठा', 'मूल'],
            1 => ['पूर्वाषाढ़', 'उत्तराषाढ़', 'श्रवण', 'धनिष्ठा', 'शतभिषा', 'पूर्वाभाद्रपद'],
            2 => ['उत्तराभाद्रपद', 'रेवती', 'अश्विनी', 'भरणी', 'कृत्तिका'],
            3 => ['रोहिणी', 'मृगशिरा', 'आर्द्रा', 'पुनर्वसु', 'पुष्य'],
            4 => ['आश्लेषा', 'मघा', 'पूर्वाफाल्गुनी', 'उत्तराफाल्गुनी'],
            5 => ['उत्तराफाल्गुनी', 'हस्त', 'चित्रा', 'स्वाति', 'विशाखा'],
            6 => ['विशाखा', 'अनुराधा', 'ज्येष्ठा', 'मूल', 'पूर्वाषाढ़']
        ];
        $hasRaviYoga = in_array($nakshatra, $raviMap[$dayOfWeek] ?? []);
        $raviYoga = $hasRaviYoga ? 'सूर्योदय से अगले दिन सूर्योदय तक' : 'null';

        // Sarvarth Siddhi Yoga
        $siddhiMap = [
            0 => ['हस्त', 'मूल', 'उत्तराषाढ़', 'उत्तराभाद्रपद', 'उत्तराफाल्गुनी', 'पुष्य', 'अश्विनी'],
            1 => ['श्रवण', 'रोहिणी', 'मृगशिरा', 'पुष्य', 'अनुराधा'],
            2 => ['अश्विनी', 'उत्तराभाद्रपद', 'कृत्तिका', 'अनुराधा'],
            3 => ['रोहिणी', 'अनुराधा', 'हस्त', 'कृत्तिका', 'मृगशिरा'],
            4 => ['रेवती', 'अनुराधा', 'पुष्य', 'पुनर्वसु', 'हस्त'],
            5 => ['रेवती', 'अनुराधा', 'हस्त', 'श्रवण', 'पुनर्वसु'],
            6 => ['रोहिणी', 'श्रवण', 'स्वाति']
        ];
        $hasSiddhi = in_array($nakshatra, $siddhiMap[$dayOfWeek] ?? []);
        $sarvarthSiddhi = $hasSiddhi ? 'सूर्योदय से अगले दिन सूर्योदय तक' : 'null';

        $shubhMuhurt = [
            'abhijit' => $abhijit,
            'amrit_kaal' => $amritKaal,
            'vijay' => $vijay,
            'ravi_yoga' => $raviYoga,
            'sarvarth_siddhi' => $sarvarthSiddhi
        ];
        $shubhMuhurtJson = json_encode($shubhMuhurt, JSON_UNESCAPED_UNICODE);

        // Save to cache
        $stmtInsert = $pdo->prepare("
            INSERT IGNORE INTO panchang_data 
            (panchang_date, tithi, paksha, nakshatra, chandra_rashi, vikram_month, vikram_samvat, shaka_samvat, yugabdha, sunrise, sunset, utsav, yoga, karana, rahukaal, chandra_udaya, chandra_asta, shubh_muhurt)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmtInsert->execute([
            $date, $tithi, $paksha, $nakshatra, $chandraRashi, $vikramMonth, 
            $vikramSamvat ?: null, $shakaSamvat ?: null, $yugabdha ?: null, 
            $sunrise, $sunset, $utsav, $yoga, $karana, $rahukaal, $chandraUdaya, $chandraAsta, $shubhMuhurtJson
        ]);

        return [
            'tithi' => $tithi,
            'paksha' => $paksha,
            'vikram_month' => $vikramMonth,
            'shaka_month' => $vikramMonth,
            'vikram_samvat' => $vikramSamvat,
            'shaka_samvat' => $shakaSamvat,
            'yugabdha' => $yugabdha,
            'utsav' => $utsav,
            'vrat_tyohar' => $utsav,
            'nakshatra' => $nakshatra,
            'sunrise' => $sunrise,
            'sunset' => $sunset,
            'chandra_rashi' => $chandraRashi,
            'yoga' => $yoga,
            'karana' => $karana,
            'rahukaal' => $rahukaal,
            'chandra_udaya' => $chandraUdaya,
            'chandra_asta' => $chandraAsta,
            'shubh_muhurt' => $shubhMuhurt
        ];
    }

    /**
     * Get Panchang data for a full month
     */
    public static function getForMonth($pdo, $year, $month, $shakhaId = null) {
        $daysInMonth = (int)(new DateTime("$year-$month-01"))->format('t');
        $list = [];
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
            $panchang = self::getForDate($pdo, $date, $shakhaId);
            $panchang['date'] = $date;
            $list[] = $panchang;
        }
        return $list;
    }

    private static function formatResult($dbRow) {
        $shubhMuhurt = [];
        if (!empty($dbRow['shubh_muhurt'])) {
            $shubhMuhurt = json_decode($dbRow['shubh_muhurt'], true);
        }
        return [
            'tithi' => $dbRow['tithi'],
            'paksha' => $dbRow['paksha'],
            'vikram_month' => $dbRow['vikram_month'],
            'shaka_month' => $dbRow['vikram_month'],
            'vikram_samvat' => $dbRow['vikram_samvat'],
            'shaka_samvat' => $dbRow['shaka_samvat'],
            'yugabdha' => $dbRow['yugabdha'],
            'utsav' => $dbRow['utsav'] ?? '',
            'vrat_tyohar' => $dbRow['utsav'] ?? '',
            'nakshatra' => $dbRow['nakshatra'] ?? '-',
            'sunrise' => $dbRow['sunrise'] ?? '06:00 AM',
            'sunset' => $dbRow['sunset'] ?? '06:30 PM',
            'chandra_rashi' => $dbRow['chandra_rashi'] ?? '—',
            'yoga' => $dbRow['yoga'] ?? '—',
            'karana' => $dbRow['karana'] ?? '—',
            'rahukaal' => $dbRow['rahukaal'] ?? '—',
            'chandra_udaya' => $dbRow['chandra_udaya'] ?? '—',
            'chandra_asta' => $dbRow['chandra_asta'] ?? '—',
            'shubh_muhurt' => $shubhMuhurt
        ];
    }

    /**
     * Helper to get approx coordinates for major Indian cities
     */
    private static function getCityCoordinates($city) {
        $city = strtolower(trim($city));
        $cities = [
            'mumbai' => ['lat' => 19.0760, 'lon' => 72.8777],
            'delhi' => ['lat' => 28.7041, 'lon' => 77.1025],
            'bengaluru' => ['lat' => 12.9716, 'lon' => 77.5946],
            'hyderabad' => ['lat' => 17.3850, 'lon' => 78.4867],
            'ahmedabad' => ['lat' => 23.0225, 'lon' => 72.5714],
            'chennai' => ['lat' => 13.0827, 'lon' => 80.2707],
            'kolkata' => ['lat' => 22.5726, 'lon' => 88.3639],
            'surat' => ['lat' => 21.1702, 'lon' => 72.8311],
            'pune' => ['lat' => 18.5204, 'lon' => 73.8567],
            'jaipur' => ['lat' => 26.9124, 'lon' => 75.7873],
            'lucknow' => ['lat' => 26.8467, 'lon' => 80.9462],
            'kanpur' => ['lat' => 26.4499, 'lon' => 80.3319],
            'nagpur' => ['lat' => 21.1458, 'lon' => 79.0882],
            'indore' => ['lat' => 22.7196, 'lon' => 75.8577],
            'thane' => ['lat' => 19.2183, 'lon' => 72.9781],
            'bhopal' => ['lat' => 23.2599, 'lon' => 77.4126],
            // Default center of India
            'default' => ['lat' => 20.5937, 'lon' => 78.9629]
        ];

        // English-Hindi mappings
        $hindiMap = [
            'मुम्बई' => 'mumbai',
            'मुंबई' => 'mumbai',
            'दिल्ली' => 'delhi',
            'बेंगलुरु' => 'bengaluru',
            'हैदराबाद' => 'hyderabad',
            'अहमदाबाद' => 'ahmedabad',
            'चेन्नई' => 'chennai',
            'कोलकाता' => 'kolkata',
            'सूरत' => 'surat',
            'पुणे' => 'pune',
            'जयपुर' => 'jaipur',
            'लखनऊ' => 'lucknow',
            'कानपुर' => 'kanpur',
            'नागपुर' => 'nagpur',
            'इंदौर' => 'indore',
            'ठाणे' => 'thane',
            'भोपाल' => 'bhopal'
        ];

        if (isset($hindiMap[$city])) {
            return $cities[$hindiMap[$city]];
        }
        
        foreach ($cities as $k => $v) {
            if (strpos($city, $k) !== false) {
                return $v;
            }
        }

        return $cities['default'];
    }
}
