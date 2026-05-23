<?php

declare(strict_types=1);

namespace ChaseCrawford\Ratings\Glicko;

final class Glicko2Calculator
{
    private const SCALE = 173.7178;
    private const CONVERGENCE_TOLERANCE = 1e-6;
    private const MAX_ITERATIONS = 100;

    public function __construct(
        public readonly float $tau = 0.5,
    ) {
    }

    /**
     * @param PeriodMatch[] $matchesInPeriod
     */
    public function updatePlayer(GlickoRating $player, array $matchesInPeriod): GlickoRating
    {
        $mu = ($player->rating - 1500.0) / self::SCALE;
        $phi = $player->deviation / self::SCALE;
        $sigma = $player->volatility;

        if ([] === $matchesInPeriod) {
            $newPhi = sqrt($phi ** 2 + $sigma ** 2);

            return new GlickoRating(
                rating: $player->rating,
                deviation: self::SCALE * $newPhi,
                volatility: $sigma,
            );
        }

        $vInverse = 0.0;
        $deltaSum = 0.0;
        foreach ($matchesInPeriod as $match) {
            $oppMu = ($match->opponent->rating - 1500.0) / self::SCALE;
            $oppPhi = $match->opponent->deviation / self::SCALE;
            $g = $this->g($oppPhi);
            $e = $this->expected($mu, $oppMu, $g);
            $s = $match->outcome->score();

            $vInverse += $g ** 2 * $e * (1.0 - $e);
            $deltaSum += $g * ($s - $e);
        }
        $v = 1.0 / $vInverse;
        $delta = $v * $deltaSum;

        $newSigma = $this->newVolatility($sigma, $phi, $v, $delta);

        $phiStar = sqrt($phi ** 2 + $newSigma ** 2);
        $newPhi = 1.0 / sqrt(1.0 / $phiStar ** 2 + 1.0 / $v);

        $newMu = $mu + $newPhi ** 2 * $deltaSum;

        return new GlickoRating(
            rating: self::SCALE * $newMu + 1500.0,
            deviation: self::SCALE * $newPhi,
            volatility: $newSigma,
        );
    }

    private function g(float $phi): float
    {
        return 1.0 / sqrt(1.0 + 3.0 * $phi ** 2 / M_PI ** 2);
    }

    private function expected(float $mu, float $oppMu, float $g): float
    {
        return 1.0 / (1.0 + exp(-$g * ($mu - $oppMu)));
    }

    private function newVolatility(float $sigma, float $phi, float $v, float $delta): float
    {
        $a = log($sigma ** 2);
        $f = fn (float $x): float => (exp($x) * ($delta ** 2 - $phi ** 2 - $v - exp($x)))
            / (2.0 * ($phi ** 2 + $v + exp($x)) ** 2)
            - ($x - $a) / $this->tau ** 2;

        if ($delta ** 2 > $phi ** 2 + $v) {
            $b = log($delta ** 2 - $phi ** 2 - $v);
        } else {
            $k = 1;
            while ($f($a - $k * $this->tau) < 0.0) {
                ++$k;
            }
            $b = $a - $k * $this->tau;
        }

        $fa = $f($a);
        $fb = $f($b);

        for ($i = 0; $i < self::MAX_ITERATIONS; ++$i) {
            if (abs($b - $a) < self::CONVERGENCE_TOLERANCE) {
                break;
            }
            $c = $a + ($a - $b) * $fa / ($fb - $fa);
            $fc = $f($c);

            if ($fc * $fb <= 0.0) {
                $a = $b;
                $fa = $fb;
            } else {
                $fa /= 2.0;
            }
            $b = $c;
            $fb = $fc;
        }

        return exp($a / 2.0);
    }
}
