<?php

declare(strict_types=1);

namespace ChaseCrawford\Ratings\Tests\Glicko;

use ChaseCrawford\Ratings\Common\Exception\InvalidRatingException;
use ChaseCrawford\Ratings\Glicko\GlickoRating;
use PHPUnit\Framework\TestCase;

final class GlickoRatingTest extends TestCase
{
    public function testDefaultConstructionUsesGlickoDefaults(): void
    {
        $r = new GlickoRating();
        self::assertSame(1500.0, $r->rating);
        self::assertSame(350.0, $r->deviation);
        self::assertSame(0.06, $r->volatility);
    }

    public function testConstructsWithExplicitValues(): void
    {
        $r = new GlickoRating(rating: 1742, deviation: 80, volatility: 0.04);
        self::assertSame(1742.0, $r->rating);
        self::assertSame(80.0, $r->deviation);
        self::assertSame(0.04, $r->volatility);
    }

    public function testRejectsNanRating(): void
    {
        $this->expectException(InvalidRatingException::class);
        new GlickoRating(rating: NAN);
    }

    public function testRejectsZeroDeviation(): void
    {
        $this->expectException(InvalidRatingException::class);
        new GlickoRating(deviation: 0);
    }

    public function testRejectsNegativeDeviation(): void
    {
        $this->expectException(InvalidRatingException::class);
        new GlickoRating(deviation: -10);
    }

    public function testRejectsZeroVolatility(): void
    {
        $this->expectException(InvalidRatingException::class);
        new GlickoRating(volatility: 0);
    }

    public function testRejectsNegativeVolatility(): void
    {
        $this->expectException(InvalidRatingException::class);
        new GlickoRating(volatility: -0.05);
    }
}
