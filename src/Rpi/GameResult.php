<?php

declare(strict_types=1);

namespace ChaseCrawford\Ratings\Rpi;

use ChaseCrawford\Ratings\Common\Exception\InvalidConfigurationException;
use ChaseCrawford\Ratings\Common\Outcome;

final readonly class GameResult
{
    public function __construct(
        public string $competitorA,
        public string $competitorB,
        public Outcome $outcomeForA,
    ) {
        if ('' === $competitorA || '' === $competitorB) {
            throw new InvalidConfigurationException('Competitor names cannot be empty.');
        }
        if ($competitorA === $competitorB) {
            throw new InvalidConfigurationException("A competitor cannot play themselves (got '{$competitorA}' on both sides).");
        }
    }
}
