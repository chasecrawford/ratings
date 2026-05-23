<?php

declare(strict_types=1);

namespace ChaseCrawford\Ratings\Elo;

final readonly class EloUpdate
{
    public function __construct(
        public EloRating $newA,
        public EloRating $newB,
        public float $expectedA,
        public float $expectedB,
    ) {
    }
}
