<?php
/**
 * LunarMonthCalculator.php
 *
 * Determines the Hindu lunar month name for any Gregorian date.
 * Implements the Adhikamasa (intercalary month) and Kshayamasa (suppressed month)
 * detection algorithms from the Adhixaya research papers.
 *
 * Algorithm:
 * 1. Find the New Moon (Ni) preceding the given date
 * 2. Find the New Moon (Ni+1) following the given date  
 * 3. Find all Sankrantis (solar transits) between Ni and Ni+1
 * 4. Apply naming rules:
 *    - 1 Sankranti → Normal month (named by rashi the Sun enters)
 *    - 0 Sankrantis → Adhikamasa (named with 'अधिक' prefix of next month)
 *    - 2 Sankrantis → Kshayamasa (named by first rashi; second month is suppressed)
 */

require_once __DIR__ . '/SolarTransitCalculator.php';

class LunarMonthCalculator {

    private SolarTransitCalculator $transit;

    /**
     * Rashi index (0-11) to Amant month name mapping.
     * When the Sun enters a rashi during a lunar month, that lunar month
     * is named after this mapping.
     *
     * 0 = Mesha (Aries, ~Apr 14) → Vaishakha
     * 1 = Vrishabha (Taurus, ~May 15) → Jyeshtha
     * ... etc.
     */
    private array $rashiToMonthEn = [
        0  => 'Chaitra',
        1  => 'Vaishakha',
        2  => 'Jyeshtha',
        3  => 'Ashadha',
        4  => 'Shravana',
        5  => 'Bhadrapada',
        6  => 'Ashwin',
        7  => 'Kartik',
        8  => 'Margashirsha',
        9  => 'Pausha',
        10 => 'Magha',
        11 => 'Phalguna',
    ];

    private array $rashiToMonthHi = [
        0  => 'चैत्र',
        1  => 'वैशाख',
        2  => 'ज्येष्ठ',
        3  => 'आषाढ़',
        4  => 'श्रावण',
        5  => 'भाद्रपद',
        6  => 'आश्विन',
        7  => 'कार्तिक',
        8  => 'मार्गशीर्ष',
        9  => 'पौष',
        10 => 'माघ',
        11 => 'फाल्गुन',
    ];

    /**
     * Month order for Purnimant conversion.
     * The "next" month in the cycle, used when converting from Amant to Purnimant
     * during Krishna Paksha.
     */
    private array $nextMonthEn = [
        'Chaitra'      => 'Vaishakha',
        'Vaishakha'    => 'Jyeshtha',
        'Jyeshtha'     => 'Ashadha',
        'Ashadha'      => 'Shravana',
        'Shravana'     => 'Bhadrapada',
        'Bhadrapada'   => 'Ashwin',
        'Ashwin'       => 'Kartik',
        'Kartik'       => 'Margashirsha',
        'Margashirsha' => 'Pausha',
        'Pausha'       => 'Magha',
        'Magha'        => 'Phalguna',
        'Phalguna'     => 'Chaitra',
    ];

    private array $nextMonthHi = [
        'चैत्र'       => 'वैशाख',
        'वैशाख'       => 'ज्येष्ठ',
        'ज्येष्ठ'      => 'आषाढ़',
        'आषाढ़'       => 'श्रावण',
        'श्रावण'       => 'भाद्रपद',
        'भाद्रपद'     => 'आश्विन',
        'आश्विन'      => 'कार्तिक',
        'कार्तिक'     => 'मार्गशीर्ष',
        'मार्गशीर्ष'   => 'पौष',
        'पौष'         => 'माघ',
        'माघ'         => 'फाल्गुन',
        'फाल्गुन'     => 'चैत्र',
    ];

    public function __construct() {
        $this->transit = new SolarTransitCalculator();
    }

    /**
     * Get the lunar month data for a given Gregorian date.
     *
     * @param int    $year    Gregorian year
     * @param int    $month   Gregorian month
     * @param int    $day     Gregorian day
     * @param string $paksha  'Shukla' or 'Krishna'
     * @return array [
     *   'amant'            => English Amant month name (possibly with 'Adhik ' prefix),
     *   'amant_hindi'      => Hindi Amant month name (possibly with 'अधिक ' prefix),
     *   'purnimant'        => English Purnimant month name,
     *   'purnimant_hindi'  => Hindi Purnimant month name,
     *   'is_adhika'        => bool,
     *   'is_kshaya'        => bool,
     * ]
     */
    public function getMonthForDate(int $year, int $month, int $day, string $paksha): array {
        // Convert date to JD at 00:30 UTC (06:00 IST, near sunrise)
        $targetJd = $this->transit->gregorianToJDN($year, $month, $day) + 0.5 / 24.0;

        // Step 1: Find the preceding New Moon (Ni)
        $niJd = $this->transit->findPrecedingNewMoon($targetJd);

        // Step 2: Find the following New Moon (Ni+1)
        $ni1Jd = $this->transit->findFollowingNewMoon($targetJd);

        // Step 3: Find all Sankrantis between Ni and Ni+1
        $sankrantis = $this->transit->findSankrantisBetween($niJd, $ni1Jd);
        $count = count($sankrantis);

        // Step 4: Apply naming rules
        $isAdhika = false;
        $isKshaya = false;
        $amantEn = '';
        $amantHi = '';

        if ($count === 1) {
            // Normal month: named by the rashi the Sun enters
            $rashi = $sankrantis[0]['rashi'];
            $amantEn = $this->rashiToMonthEn[$rashi];
            $amantHi = $this->rashiToMonthHi[$rashi];
        } elseif ($count === 0) {
            // Adhikamasa: no Sankranti in this lunar month
            // Name is 'Adhik' + the name of the NEXT normal month
            // To find the next normal month, we look at the Sankranti in the NEXT lunar month
            $ni2Jd = $this->transit->findFollowingNewMoon($ni1Jd + 1);
            $nextSankrantis = $this->transit->findSankrantisBetween($ni1Jd, $ni2Jd);
            
            if (!empty($nextSankrantis)) {
                $nextRashi = $nextSankrantis[0]['rashi'];
                $amantEn = 'Adhik ' . $this->rashiToMonthEn[$nextRashi];
                $amantHi = 'अधिक ' . $this->rashiToMonthHi[$nextRashi];
            } else {
                // Extremely rare: two consecutive Adhikamasa months
                // Fallback: use Sun's current rashi position to estimate
                $sunLon = $this->transit->computeSiderealSunLongitude($targetJd);
                $currentRashi = (int) floor($sunLon / 30.0);
                $amantEn = 'Adhik ' . $this->rashiToMonthEn[$currentRashi];
                $amantHi = 'अधिक ' . $this->rashiToMonthHi[$currentRashi];
            }
            $isAdhika = true;
        } elseif ($count >= 2) {
            // Kshayamasa: two Sankrantis in one lunar month
            // Named after the first Sankranti's rashi; second month is suppressed
            $rashi = $sankrantis[0]['rashi'];
            $amantEn = $this->rashiToMonthEn[$rashi];
            $amantHi = $this->rashiToMonthHi[$rashi];
            $isKshaya = true;
        }

        // Step 5: Compute Purnimant month name
        // In Shukla Paksha: Purnimant = same as Amant
        // In Krishna Paksha: Purnimant = next month name
        $purnimantEn = $amantEn;
        $purnimantHi = $amantHi;

        if ($paksha === 'Krishna') {
            // For Adhika months, strip the 'Adhik ' prefix before looking up next month
            $baseEn = $isAdhika ? str_replace('Adhik ', '', $amantEn) : $amantEn;
            $baseHi = $isAdhika ? str_replace('अधिक ', '', $amantHi) : $amantHi;
            
            $nextEn = $this->nextMonthEn[$baseEn] ?? $amantEn;
            $nextHi = $this->nextMonthHi[$baseHi] ?? $amantHi;
            
            // If the Amant month is Adhika, Purnimant during Krishna is the Adhika version of next
            $purnimantEn = $isAdhika ? 'Adhik ' . $nextEn : $nextEn;
            $purnimantHi = $isAdhika ? 'अधिक ' . $nextHi : $nextHi;
        }

        return [
            'amant'            => $amantEn,
            'amant_hindi'      => $amantHi,
            'purnimant'        => $purnimantEn,
            'purnimant_hindi'  => $purnimantHi,
            'is_adhika'        => $isAdhika,
            'is_kshaya'        => $isKshaya,
        ];
    }
}
