<?php

declare(strict_types=1);

namespace ChaseCrawford\Ratings\Tests\Elo;

use ChaseCrawford\Ratings\Common\Outcome;
use ChaseCrawford\Ratings\Elo\CallableK;
use ChaseCrawford\Ratings\Elo\ConstantK;
use ChaseCrawford\Ratings\Elo\EloCalculator;
use ChaseCrawford\Ratings\Elo\EloRating;
use PHPUnit\Framework\TestCase;

final class EloCalculatorTest extends TestCase
{
    private const TOL = 1e-9;

    public function testReferenceVectorUnderdogWinsK20(): void
    {
        $elo = new EloCalculator(new ConstantK(20));
        $update = $elo->rate(
            a: new EloRating(1500),
            b: new EloRating(1400),
            outcomeForA: Outcome::WIN,
        );

        self::assertEqualsWithDelta(0.6400649998028851, $update->expectedA, self::TOL);
        self::assertEqualsWithDelta(0.3599350001971149, $update->expectedB, self::TOL);
        self::assertEqualsWithDelta(1507.1987000039422, $update->newA->value, self::TOL);
        self::assertEqualsWithDelta(1392.8012999960577, $update->newB->value, self::TOL);
    }

    public function testDrawBetweenEqualPlayersDoesNotChangeRatings(): void
    {
        $elo = new EloCalculator(new ConstantK(32));
        $update = $elo->rate(
            a: new EloRating(1500),
            b: new EloRating(1500),
            outcomeForA: Outcome::DRAW,
        );

        self::assertEqualsWithDelta(0.5, $update->expectedA, self::TOL);
        self::assertEqualsWithDelta(1500.0, $update->newA->value, self::TOL);
        self::assertEqualsWithDelta(1500.0, $update->newB->value, self::TOL);
    }

    public function testZeroSumPropertyHolds(): void
    {
        $elo = new EloCalculator(new ConstantK(24));
        $update = $elo->rate(
            a: new EloRating(1742),
            b: new EloRating(1188),
            outcomeForA: Outcome::LOSS,
        );

        $deltaA = $update->newA->value - 1742;
        $deltaB = $update->newB->value - 1188;
        self::assertEqualsWithDelta(0.0, $deltaA + $deltaB, self::TOL);
    }

    public function testExpectedScoresSumToOne(): void
    {
        $elo = new EloCalculator(new ConstantK(20));
        $update = $elo->rate(
            a: new EloRating(1234),
            b: new EloRating(1876),
            outcomeForA: Outcome::WIN,
        );

        self::assertEqualsWithDelta(1.0, $update->expectedA + $update->expectedB, self::TOL);
    }

    public function testUsesPerPlayerKFactorsFromStrategy(): void
    {
        $elo = new EloCalculator(new CallableK(
            fn (EloRating $r, int $m): int => 1500.0 === $r->value ? 40 : 10,
        ));

        $update = $elo->rate(
            a: new EloRating(1500),
            b: new EloRating(1400),
            outcomeForA: Outcome::WIN,
        );

        $deltaA = $update->newA->value - 1500;
        $deltaB = 1400 - $update->newB->value;
        self::assertEqualsWithDelta($deltaA, 4 * $deltaB, self::TOL);
    }

    public function testDefaultCalculatorUsesK15(): void
    {
        $elo = new EloCalculator();
        $update = $elo->rate(
            a: new EloRating(1500),
            b: new EloRating(1500),
            outcomeForA: Outcome::WIN,
        );

        self::assertEqualsWithDelta(1507.5, $update->newA->value, self::TOL);
    }
}
