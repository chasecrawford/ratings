<?php

declare(strict_types=1);

namespace ChaseCrawford\Ratings\Elo;

interface KFactor
{
    public function for(EloRating $rating, int $matchesPlayed): int;
}
