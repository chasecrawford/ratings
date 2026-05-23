<?php

declare(strict_types=1);

namespace ChaseCrawford\Ratings\Rpi;

use ChaseCrawford\Ratings\Common\Exception\InvalidConfigurationException;

final readonly class Weights
{
    private const SUM_TOLERANCE = 1e-9;

    public function __construct(
        public float $own = 0.25,
        public float $opponents = 0.50,
        public float $opponentsOpponents = 0.25,
    ) {
        if ($own < 0.0 || $opponents < 0.0 || $opponentsOpponents < 0.0) {
            throw new InvalidConfigurationException('Weights must be non-negative.');
        }
        $sum = $own + $opponents + $opponentsOpponents;
        if (abs($sum - 1.0) > self::SUM_TOLERANCE) {
            throw new InvalidConfigurationException("Weights must sum to 1.0; got {$sum}.");
        }
    }

    public static function classic(): self
    {
        return new self(0.25, 0.50, 0.25);
    }
}
