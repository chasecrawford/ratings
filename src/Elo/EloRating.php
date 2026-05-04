<?php

declare(strict_types=1);

namespace ChaseCrawford\Ratings\Elo;

use ChaseCrawford\Ratings\Common\Exception\InvalidRatingException;

final readonly class EloRating
{
    public function __construct(public float $value)
    {
        if (is_nan($value)) {
            throw new InvalidRatingException('Elo rating cannot be NaN.');
        }
        if (is_infinite($value)) {
            throw new InvalidRatingException('Elo rating cannot be infinite.');
        }
    }
}
