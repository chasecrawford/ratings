<?php

declare(strict_types=1);

namespace ChaseCrawford\Ratings\Elo;

/**
 * USCF tiered K-factor approximation.
 *
 * Provisional players (< 8 rated games): K = 40.
 * Established players: tier by current rating (32 / 24 / 16).
 */
final readonly class UscfK implements KFactor
{
    public function __construct(public int $provisionalThreshold = 8)
    {
    }

    public function for(EloRating $rating, int $matchesPlayed): int
    {
        if ($matchesPlayed < $this->provisionalThreshold) {
            return 40;
        }

        return match (true) {
            $rating->value < 2100 => 32,
            $rating->value < 2400 => 24,
            default => 16,
        };
    }
}
