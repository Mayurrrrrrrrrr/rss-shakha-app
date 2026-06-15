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

        // Save to cache
        $stmtInsert = $pdo->prepare("
            INSERT IGNORE INTO panchang_data 
            (panchang_date, tithi, paksha, nakshatra, chandra_rashi, vikram_month, vikram_samvat, shaka_samvat, yugabdha, sunrise, sunset, utsav)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmtInsert->execute([
            $date, $tithi, $paksha, $nakshatra, '', $vikramMonth, 
            $vikramSamvat ?: null, $shakaSamvat ?: null, $yugabdha ?: null, 
            $sunrise, $sunset, $utsav
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
            'nakshatra' => $nakshatra,
            'sunrise' => $sunrise,
            'sunset' => $sunset
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
        return [
            'tithi' => $dbRow['tithi'],
            'paksha' => $dbRow['paksha'],
            'vikram_month' => $dbRow['vikram_month'],
            'shaka_month' => $dbRow['vikram_month'],
            'vikram_samvat' => $dbRow['vikram_samvat'],
            'shaka_samvat' => $dbRow['shaka_samvat'],
            'yugabdha' => $dbRow['yugabdha'],
            'utsav' => $dbRow['utsav'] ?? '',
            'nakshatra' => $dbRow['nakshatra'] ?? '-',
            'sunrise' => $dbRow['sunrise'] ?? '06:00 AM',
            'sunset' => $dbRow['sunset'] ?? '06:30 PM'
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
