<?php

declare(strict_types=1);

namespace ChaseCrawford\Ratings\Glicko;

use ChaseCrawford\Ratings\Common\Outcome;

final readonly class PeriodMatch
{
    public function __construct(
        public GlickoRating $opponent,
        public Outcome $outcome,
    ) {
    }
}
