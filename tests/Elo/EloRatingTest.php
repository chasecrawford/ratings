<?php

declare(strict_types=1);

namespace ChaseCrawford\Ratings\Tests\Elo;

use ChaseCrawford\Ratings\Common\Exception\InvalidRatingException;
use ChaseCrawford\Ratings\Elo\EloRating;
use PHPUnit\Framework\TestCase;

final class EloRatingTest extends TestCase
{
    public function testConstructsWithValidValue(): void
    {
        $rating = new EloRating(1500.0);
        self::assertSame(1500.0, $rating->value);
    }

    public function testRejectsNan(): void
    {
        $this->expectException(InvalidRatingException::class);
        new EloRating(NAN);
    }

    public function testRejectsInfinity(): void
    {
        $this->expectException(InvalidRatingException::class);
        new EloRating(INF);
    }

    public function testAcceptsNegativeRating(): void
    {
        // Elo ratings can theoretically go negative; we don't forbid it.
        $rating = new EloRating(-50.0);
        self::assertSame(-50.0, $rating->value);
    }
}
