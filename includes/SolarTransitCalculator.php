<?php
/**
 * SolarTransitCalculator.php
 * 
 * Core astronomical computation engine based on:
 * - Jean Meeus, "Astronomical Algorithms" (2nd edition)
 * - Adhixaya research papers on Hindu Luni-Solar Calendar
 * - Indian Calendar Reform Committee (Lahiri Ayanamsa)
 *
 * Provides algorithms to compute:
 * 1. New Moon (Amavasya) moments for any year
 * 2. Solar transit (Sankranti) moments for any year  
 * 3. Dynamic Lahiri Ayanamsa
 * 4. True Sun/Moon sidereal longitudes
 */
class SolarTransitCalculator {

    /**
     * Compute the Julian Day of a New Moon (Amavasya).
     *
     * Uses the algorithm from Meeus Ch. 49 (Phases of the Moon).
     * k = integer → New Moon, k + 0.5 → Full Moon
     * k = 0 corresponds to 2000 Jan 6 new moon.
     *
     * @param float $k  Lunation number (integer for new moon)
     * @return float    Julian Day of the new moon (corrected)
     */
    public function computeNewMoonJD(float $k): float {
        // T = centuries from J2000.0
        $T = $k / 1236.85;
        
        // Mean JDE of the phase
        $JDE = 2451550.09766 
             + 29.530588861 * $k
             + 0.00015437 * pow($T, 2)
             - 0.000000150 * pow($T, 3)
             + 0.00000000073 * pow($T, 4);
        
        // Sun's mean anomaly
        $M = 2.5534 + 29.10535670 * $k
           - 0.0000014 * pow($T, 2)
           - 0.00000011 * pow($T, 3);
        $M = deg2rad($this->normalizeDegrees($M));
        
        // Moon's mean anomaly  
        $Mp = 201.5643 + 385.81693528 * $k
            + 0.0107582 * pow($T, 2)
            + 0.00001238 * pow($T, 3)
            - 0.000000058 * pow($T, 4);
        $Mp = deg2rad($this->normalizeDegrees($Mp));
        
        // Moon's argument of latitude
        $F = 160.7108 + 390.67050284 * $k
           - 0.0016118 * pow($T, 2)
           - 0.00000227 * pow($T, 3)
           + 0.000000011 * pow($T, 4);
        $F = deg2rad($this->normalizeDegrees($F));
        
        // Longitude of ascending node
        $Omega = 124.7746 - 1.56375588 * $k
               + 0.0020672 * pow($T, 2)
               + 0.00000215 * pow($T, 3);
        $Omega = deg2rad($this->normalizeDegrees($Omega));
        
        // Correction terms for New Moon (from Meeus Table 49.A)
        $correction = 0.0;
        $correction += -0.40720 * sin($Mp);
        $correction +=  0.17241 * sin($M);    // Note: E factor omitted for simplicity
        $correction +=  0.01608 * sin(2 * $Mp);
        $correction +=  0.01039 * sin(2 * $F);
        $correction +=  0.00739 * sin($Mp - $M);
        $correction += -0.00514 * sin($Mp + $M);
        $correction +=  0.00208 * sin(2 * $M);
        $correction += -0.00111 * sin($Mp - 2 * $F);
        $correction += -0.00057 * sin($Mp + 2 * $F);
        $correction +=  0.00056 * sin(2 * $Mp + $M);
        $correction += -0.00042 * sin(3 * $Mp);
        $correction +=  0.00042 * sin($M + 2 * $F);
        $correction +=  0.00038 * sin($M - 2 * $F);
        $correction += -0.00024 * sin(2 * $Mp - $M);
        
        return $JDE + $correction;
    }

    /**
     * Find the lunation number k for the new moon nearest to a given JD.
     *
     * @param float $jd  Julian Day
     * @return int        Lunation number (k)
     */
    public function getLunationNumber(float $jd): int {
        // Approximate k from JD
        $year = 2000.0 + ($jd - 2451545.0) / 365.25;
        $k = ($year - 2000.0) * 12.3685;
        return (int) round($k);
    }

    /**
     * Find the Julian Day of the New Moon that precedes the given JD.
     *
     * @param float $targetJd  Target Julian Day
     * @return float           JD of preceding new moon
     */
    public function findPrecedingNewMoon(float $targetJd): float {
        $k = $this->getLunationNumber($targetJd);
        
        // Try k and k-1, pick the latest one before targetJd
        $jd = $this->computeNewMoonJD($k);
        if ($jd > $targetJd) {
            $k--;
            $jd = $this->computeNewMoonJD($k);
        }
        
        // Safety: ensure we actually found a preceding new moon
        while ($jd > $targetJd) {
            $k--;
            $jd = $this->computeNewMoonJD($k);
        }
        
        return $jd;
    }

    /**
     * Find the Julian Day of the New Moon that follows the given JD.
     *
     * @param float $targetJd  Target Julian Day
     * @return float           JD of following new moon
     */
    public function findFollowingNewMoon(float $targetJd): float {
        $k = $this->getLunationNumber($targetJd);
        
        $jd = $this->computeNewMoonJD($k);
        if ($jd <= $targetJd) {
            $k++;
            $jd = $this->computeNewMoonJD($k);
        }
        
        // Safety
        while ($jd <= $targetJd) {
            $k++;
            $jd = $this->computeNewMoonJD($k);
        }
        
        return $jd;
    }

    /**
     * Compute the dynamic Lahiri Ayanamsa for a given Julian Day.
     *
     * Based on the Indian Calendar Reform Committee value.
     * Reference: Lahiri Ayanamsa on 2000-01-01 (J2000.0) = 23.853 degrees
     * Annual precession rate: ~50.29 arc-seconds/year
     *
     * @param float $jd  Julian Day
     * @return float      Ayanamsa in degrees
     */
    public static function computeAyanamsa(float $jd): float {
        // T = Julian centuries from J2000.0
        $T = ($jd - 2451545.0) / 36525.0;
        
        // Lahiri Ayanamsa at J2000.0 = 23.853 degrees
        // Precession rate: 50.2877 arc-seconds per year = 50.2877/3600 degrees per year
        // = 50.2877 * 100 / 3600 degrees per century
        $ayanamsa = 23.853 + (50.2877 / 3600.0) * ($T * 100.0);
        
        return $ayanamsa;
    }

    /**
     * Compute the true tropical Sun longitude at a given Julian Day.
     * Uses the low-precision formula from Meeus.
     *
     * @param float $jd  Julian Day
     * @return float      Tropical Sun longitude in degrees (0-360)
     */
    public function computeTrueSunLongitude(float $jd): float {
        $n = $jd - 2451545.0;
        
        $L = fmod(280.46646 + 0.9856474 * $n, 360.0);
        $M = fmod(357.52911 + 0.98560028 * $n, 360.0);
        $L = $this->normalizeDegrees($L);
        $M = $this->normalizeDegrees($M);
        
        $C = 1.9146 * sin(deg2rad($M)) 
           + 0.0200 * sin(deg2rad(2 * $M))
           + 0.0003 * sin(deg2rad(3 * $M));
        
        $sunLon = $this->normalizeDegrees($L + $C);
        return $sunLon;
    }

    /**
     * Compute the true tropical Moon longitude at a given Julian Day.
     * Uses the simplified formula with 8 major perturbation terms.
     *
     * @param float $jd  Julian Day
     * @return float      Tropical Moon longitude in degrees (0-360)
     */
    public function computeTrueMoonLongitude(float $jd): float {
        $d = $jd - 2451545.0;
        
        $moonMeanLon = fmod(218.3165 + 13.176396 * $d, 360.0);
        $moonMeanAnomaly = fmod(134.9629 + 13.064993 * $d, 360.0);
        $moonElongation = fmod(297.8502 + 12.190749 * $d, 360.0);
        $sunMeanAnomaly = fmod(357.52911 + 0.98560028 * $d, 360.0);
        
        $moonMeanLon = $this->normalizeDegrees($moonMeanLon);
        $moonMeanAnomaly = $this->normalizeDegrees($moonMeanAnomaly);
        $moonElongation = $this->normalizeDegrees($moonElongation);
        $sunMeanAnomaly = $this->normalizeDegrees($sunMeanAnomaly);
        
        $Mm = deg2rad($moonMeanAnomaly);
        $D = deg2rad($moonElongation);
        $Ms = deg2rad($sunMeanAnomaly);
        
        $perturbation = 6.289 * sin($Mm)
            + 1.274 * sin(2 * $D - $Mm)
            + 0.658 * sin(2 * $D)
            - 0.186 * sin($Ms)
            - 0.214 * sin(2 * $Mm)
            + 0.151 * sin(2 * $D - $Ms)
            + 0.124 * sin(2 * $D + $Mm)
            - 0.114 * sin(2 * $D - 2 * $Mm);
        
        return $this->normalizeDegrees($moonMeanLon + $perturbation);
    }

    /**
     * Compute the sidereal Sun longitude (applying Lahiri Ayanamsa).
     *
     * @param float $jd  Julian Day
     * @return float      Sidereal Sun longitude in degrees (0-360)
     */
    public function computeSiderealSunLongitude(float $jd): float {
        $tropical = $this->computeTrueSunLongitude($jd);
        $ayanamsa = self::computeAyanamsa($jd);
        return $this->normalizeDegrees($tropical - $ayanamsa);
    }

    /**
     * Find the Julian Day when the sidereal Sun longitude crosses a target value.
     * Uses Newton-Raphson iteration.
     *
     * @param float $estimateJd   Initial estimate JD
     * @param float $targetLon    Target sidereal longitude (degrees)
     * @param int   $maxIter      Maximum iterations (default 50)
     * @return float               Converged Julian Day
     */
    public function findSolarTransit(float $estimateJd, float $targetLon, int $maxIter = 50): float {
        $jd = $estimateJd;
        $sunDailyMotion = 360.0 / 365.25; // ~0.9856 degrees/day
        
        for ($i = 0; $i < $maxIter; $i++) {
            $currentLon = $this->computeSiderealSunLongitude($jd);
            
            // Compute angular difference (handle wrap-around)
            $diff = $targetLon - $currentLon;
            if ($diff > 180.0) $diff -= 360.0;
            if ($diff < -180.0) $diff += 360.0;
            
            if (abs($diff) < 0.001) { // converged to ~1.4 minutes
                break;
            }
            
            $jd += $diff / $sunDailyMotion;
        }
        
        return $jd;
    }

    /**
     * Find all 12 Sankranti (solar transit) moments for a given year.
     * Returns the JD when the Sun enters each of the 12 rashis.
     *
     * Rashi indices: 0=Mesha(Aries), 1=Vrishabha(Taurus), ... 11=Mina(Pisces)
     * Boundary longitudes: Mesha starts at 0°, Vrishabha at 30°, etc.
     *
     * @param int $year  Gregorian year
     * @return float[]   Array of 12 JD values, indexed 0-11 by rashi
     */
    public function findAllSankrantis(int $year): array {
        $sankrantis = [];
        
        // The Sun roughly enters Mesha (0°) around April 14
        // For each rashi, estimate the JD and then refine
        for ($rashi = 0; $rashi < 12; $rashi++) {
            $targetLon = $rashi * 30.0;
            
            // Estimate: Sun moves ~30.44 days per rashi
            // Mesha transit is around April 14 (JD for that date)
            $baseJd = $this->gregorianToJDN($year, 4, 14);
            $estimateJd = $baseJd + ($rashi * 30.44);
            
            // If estimate goes past December, it may be in next year
            // The findSolarTransit will converge correctly regardless
            $sankrantis[$rashi] = $this->findSolarTransit($estimateJd, $targetLon);
        }
        
        return $sankrantis;
    }

    /**
     * Find all Sankrantis that fall between two Julian Days.
     * Returns array of ['rashi' => int, 'jd' => float]
     *
     * @param float $jdStart  Start JD (exclusive-ish, but we check > start)
     * @param float $jdEnd    End JD (exclusive)
     * @return array          Array of sankranti events
     */
    public function findSankrantisBetween(float $jdStart, float $jdEnd): array {
        $results = [];
        
        // Determine which year(s) to check
        $startYear = $this->jdToGregorianYear($jdStart);
        $endYear = $this->jdToGregorianYear($jdEnd);
        
        for ($year = $startYear - 1; $year <= $endYear + 1; $year++) {
            $sankrantis = $this->findAllSankrantis($year);
            foreach ($sankrantis as $rashi => $jd) {
                if ($jd > $jdStart && $jd <= $jdEnd) {
                    $results[] = ['rashi' => $rashi, 'jd' => $jd];
                }
            }
        }
        
        // Sort by JD
        usort($results, function($a, $b) {
            return $a['jd'] <=> $b['jd'];
        });
        
        return $results;
    }

    /**
     * Gregorian date to Julian Day Number.
     * Same formula as PanchangCalculator::gregorianToJDN()
     *
     * @param int $year
     * @param int $month
     * @param int $day
     * @return float
     */
    public function gregorianToJDN(int $year, int $month, int $day): float {
        if ($month <= 2) {
            $year--;
            $month += 12;
        }
        $A = (int) floor($year / 100);
        $B = 2 - $A + (int) floor($A / 4);
        return floor(365.25 * ($year + 4716))
             + floor(30.6001 * ($month + 1))
             + $day + $B - 1524.5;
    }

    /**
     * Extract approximate Gregorian year from a Julian Day.
     *
     * @param float $jd  Julian Day
     * @return int        Approximate Gregorian year
     */
    public function jdToGregorianYear(float $jd): int {
        return (int) round(2000.0 + ($jd - 2451545.0) / 365.25);
    }

    /**
     * Normalize angle to 0-360 range.
     *
     * @param float $degrees  Angle in degrees
     * @return float           Normalized angle (0-360)
     */
    private function normalizeDegrees(float $degrees): float {
        $d = fmod($degrees, 360.0);
        if ($d < 0) $d += 360.0;
        return $d;
    }
}
