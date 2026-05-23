<?php

declare(strict_types=1);

namespace ChaseCrawford\Ratings\Rpi;

final readonly class RpiResult
{
    /**
     * @param array<string, float> $ratings
     */
    public function __construct(public array $ratings)
    {
    }

    /**
     * @return array<string, float> same map, sorted by rating descending
     */
    public function ranked(): array
    {
        $sorted = $this->ratings;
        arsort($sorted);

        return $sorted;
    }
}
