<?php

declare(strict_types=1);

namespace ChaseCrawford\Ratings\Tests\Elo;

use ChaseCrawford\Ratings\Elo\EloRating;
use ChaseCrawford\Ratings\Elo\EloUpdate;
use PHPUnit\Framework\TestCase;

final class EloUpdateTest extends TestCase
{
    public function testHoldsNewRatingsAndExpectedScores(): void
    {
        $update = new EloUpdate(
            newA: new EloRating(1507.2),
            newB: new EloRating(1392.8),
            expectedA: 0.64,
            expectedB: 0.36,
        );

        self::assertSame(1507.2, $update->newA->value);
        self::assertSame(1392.8, $update->newB->value);
        self::assertSame(0.64, $update->expectedA);
        self::assertSame(0.36, $update->expectedB);
    }
}
