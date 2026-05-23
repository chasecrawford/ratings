<?php

declare(strict_types=1);

namespace ChaseCrawford\Ratings\Glicko;

use ChaseCrawford\Ratings\Common\Exception\InvalidRatingException;

final readonly class GlickoRating
{
    public function __construct(
        public float $rating = 1500.0,
        public float $deviation = 350.0,
        public float $volatility = 0.06,
    ) {
        if (is_nan($rating) || is_infinite($rating)) {
            throw new InvalidRatingException('Glicko rating must be a finite number.');
        }
        if (is_nan($deviation) || is_infinite($deviation) || $deviation <= 0.0) {
            throw new InvalidRatingException('Glicko deviation must be a finite positive number.');
        }
        if (is_nan($volatility) || is_infinite($volatility) || $volatility <= 0.0) {
            throw new InvalidRatingException('Glicko volatility must be a finite positive number.');
        }
    }
}
