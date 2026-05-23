<?php

declare(strict_types=1);

namespace ChaseCrawford\Ratings\Elo;

final readonly class ConstantK implements KFactor
{
    public function __construct(public int $k = 15)
    {
    }

    public function for(EloRating $rating, int $matchesPlayed): int
    {
        return $this->k;
    }
}
