<?php

declare(strict_types=1);

namespace ChaseCrawford\Ratings\Elo;

use Closure;

final readonly class CallableK implements KFactor
{
    /** @var Closure(EloRating, int): int */
    private Closure $fn;

    /**
     * @param Closure(EloRating, int): int $fn
     *                                         Receives (rating, matchesPlayed); must return an int K-factor
     */
    public function __construct(Closure $fn)
    {
        $this->fn = $fn;
    }

    public function for(EloRating $rating, int $matchesPlayed): int
    {
        return ($this->fn)($rating, $matchesPlayed);
    }
}
