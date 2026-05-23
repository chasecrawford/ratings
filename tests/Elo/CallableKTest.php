<?php

declare(strict_types=1);

namespace ChaseCrawford\Ratings\Tests\Elo;

use ChaseCrawford\Ratings\Elo\CallableK;
use ChaseCrawford\Ratings\Elo\EloRating;
use PHPUnit\Framework\TestCase;

final class CallableKTest extends TestCase
{
    public function testInvokesProvidedClosure(): void
    {
        $k = new CallableK(fn (EloRating $r, int $m): int => $r->value > 2000 ? 10 : 20);

        self::assertSame(20, $k->for(new EloRating(1500), 50));
        self::assertSame(10, $k->for(new EloRating(2100), 50));
    }

    public function testPassesMatchesPlayedToClosure(): void
    {
        $captured = null;
        $k = new CallableK(function (EloRating $r, int $m) use (&$captured): int {
            $captured = $m;

            return 30;
        });

        $k->for(new EloRating(1500), 42);
        self::assertSame(42, $captured);
    }
}
