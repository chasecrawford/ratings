<?php

declare(strict_types=1);

namespace ChaseCrawford\Ratings\Tests\Glicko;

use ChaseCrawford\Ratings\Common\Outcome;
use ChaseCrawford\Ratings\Glicko\Glicko2Calculator;
use ChaseCrawford\Ratings\Glicko\GlickoRating;
use ChaseCrawford\Ratings\Glicko\PeriodMatch;
use PHPUnit\Framework\TestCase;

final class Glicko2CalculatorTest extends TestCase
{
    public function testGlickman2013WorkedExample(): void
    {
        $glicko = new Glicko2Calculator(tau: 0.5);

        $player = new GlickoRating(rating: 1500, deviation: 200, volatility: 0.06);

        $matches = [
            new PeriodMatch(new GlickoRating(1400, 30, 0.06), Outcome::WIN),
            new PeriodMatch(new GlickoRating(1550, 100, 0.06), Outcome::LOSS),
            new PeriodMatch(new GlickoRating(1700, 300, 0.06), Outcome::LOSS),
        ];

        $new = $glicko->updatePlayer($player, $matches);

        self::assertEqualsWithDelta(1464.06, $new->rating, 0.01);
        self::assertEqualsWithDelta(151.52, $new->deviation, 0.01);
        self::assertEqualsWithDelta(0.05999, $new->volatility, 1e-5);
    }

    public function testEmptyPeriodGrowsDeviationOnly(): void
    {
        $glicko = new Glicko2Calculator(tau: 0.5);

        $player = new GlickoRating(rating: 1500, deviation: 200, volatility: 0.06);
        $new = $glicko->updatePlayer($player, []);

        self::assertSame(1500.0, $new->rating);
        self::assertEqualsWithDelta(200.27, $new->deviation, 0.05);
        self::assertSame(0.06, $new->volatility);
    }

    public function testDefaultTau(): void
    {
        $glicko = new Glicko2Calculator();
        self::assertSame(0.5, $glicko->tau);
    }

    public function testWinningAgainstHigherRatedOpponentIncreasesRating(): void
    {
        $glicko = new Glicko2Calculator();
        $player = new GlickoRating(rating: 1500, deviation: 100, volatility: 0.06);

        $new = $glicko->updatePlayer($player, [
            new PeriodMatch(new GlickoRating(1700, 50, 0.06), Outcome::WIN),
        ]);

        self::assertGreaterThan(1500.0, $new->rating);
    }

    public function testLosingAgainstLowerRatedOpponentDecreasesRating(): void
    {
        $glicko = new Glicko2Calculator();
        $player = new GlickoRating(rating: 1700, deviation: 100, volatility: 0.06);

        $new = $glicko->updatePlayer($player, [
            new PeriodMatch(new GlickoRating(1500, 50, 0.06), Outcome::LOSS),
        ]);

        self::assertLessThan(1700.0, $new->rating);
    }
}
