<?php

declare(strict_types=1);

namespace ChaseCrawford\Ratings\Tests\Common;

use ChaseCrawford\Ratings\Common\Outcome;
use PHPUnit\Framework\TestCase;

final class OutcomeTest extends TestCase
{
    public function testFromScoresReturnsWinWhenMineIsHigher(): void
    {
        self::assertSame(Outcome::WIN, Outcome::fromScores(5, 3));
    }

    public function testFromScoresReturnsLossWhenMineIsLower(): void
    {
        self::assertSame(Outcome::LOSS, Outcome::fromScores(3, 5));
    }

    public function testFromScoresReturnsDrawWhenEqual(): void
    {
        self::assertSame(Outcome::DRAW, Outcome::fromScores(3, 3));
    }

    public function testScoreReturnsOneForWin(): void
    {
        self::assertSame(1.0, Outcome::WIN->score());
    }

    public function testScoreReturnsHalfForDraw(): void
    {
        self::assertSame(0.5, Outcome::DRAW->score());
    }

    public function testScoreReturnsZeroForLoss(): void
    {
        self::assertSame(0.0, Outcome::LOSS->score());
    }

    public function testInverseSwapsWinAndLoss(): void
    {
        self::assertSame(Outcome::LOSS, Outcome::WIN->inverse());
        self::assertSame(Outcome::WIN, Outcome::LOSS->inverse());
    }

    public function testInverseOfDrawIsDraw(): void
    {
        self::assertSame(Outcome::DRAW, Outcome::DRAW->inverse());
    }
}
