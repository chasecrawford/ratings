<?php

declare(strict_types=1);

namespace ChaseCrawford\Ratings\Tests\Glicko;

use ChaseCrawford\Ratings\Common\Outcome;
use ChaseCrawford\Ratings\Glicko\GlickoRating;
use ChaseCrawford\Ratings\Glicko\PeriodMatch;
use PHPUnit\Framework\TestCase;

final class PeriodMatchTest extends TestCase
{
    public function testHoldsOpponentAndOutcome(): void
    {
        $opponent = new GlickoRating(1400, 30, 0.06);
        $match = new PeriodMatch($opponent, Outcome::WIN);

        self::assertSame($opponent, $match->opponent);
        self::assertSame(Outcome::WIN, $match->outcome);
    }
}
