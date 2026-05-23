<?php

declare(strict_types=1);

namespace ChaseCrawford\Ratings\Elo;

use ChaseCrawford\Ratings\Common\Outcome;

final class EloCalculator
{
    public function __construct(
        private readonly KFactor $kFactor = new ConstantK(15),
    ) {
    }

    public function rate(
        EloRating $a,
        EloRating $b,
        Outcome $outcomeForA,
        int $matchesPlayedA = 0,
        int $matchesPlayedB = 0,
    ): EloUpdate {
        $expectedA = $this->expected($a->value, $b->value);
        $expectedB = 1.0 - $expectedA;

        $scoreA = $outcomeForA->score();
        $scoreB = $outcomeForA->inverse()->score();

        $kA = $this->kFactor->for($a, $matchesPlayedA);
        $kB = $this->kFactor->for($b, $matchesPlayedB);

        return new EloUpdate(
            newA: new EloRating($a->value + $kA * ($scoreA - $expectedA)),
            newB: new EloRating($b->value + $kB * ($scoreB - $expectedB)),
            expectedA: $expectedA,
            expectedB: $expectedB,
        );
    }

    private function expected(float $ratingA, float $ratingB): float
    {
        return 1.0 / (1.0 + 10.0 ** (($ratingB - $ratingA) / 400.0));
    }
}
