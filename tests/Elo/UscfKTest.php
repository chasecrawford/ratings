<?php

declare(strict_types=1);

namespace ChaseCrawford\Ratings\Tests\Elo;

use ChaseCrawford\Ratings\Elo\EloRating;
use ChaseCrawford\Ratings\Elo\UscfK;
use PHPUnit\Framework\TestCase;

final class UscfKTest extends TestCase
{
    public function testProvisionalPlayerGetsK40(): void
    {
        $k = new UscfK();
        self::assertSame(40, $k->for(new EloRating(1500), matchesPlayed: 0));
        self::assertSame(40, $k->for(new EloRating(1500), matchesPlayed: 7));
    }

    public function testEstablishedLowRatedGetsK32(): void
    {
        $k = new UscfK();
        self::assertSame(32, $k->for(new EloRating(1500), matchesPlayed: 8));
        self::assertSame(32, $k->for(new EloRating(2099), matchesPlayed: 100));
    }

    public function testEstablishedMidRatedGetsK24(): void
    {
        $k = new UscfK();
        self::assertSame(24, $k->for(new EloRating(2100), matchesPlayed: 50));
        self::assertSame(24, $k->for(new EloRating(2399), matchesPlayed: 50));
    }

    public function testEstablishedHighRatedGetsK16(): void
    {
        $k = new UscfK();
        self::assertSame(16, $k->for(new EloRating(2400), matchesPlayed: 50));
        self::assertSame(16, $k->for(new EloRating(2800), matchesPlayed: 50));
    }
}
