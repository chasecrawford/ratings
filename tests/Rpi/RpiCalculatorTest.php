<?php

declare(strict_types=1);

namespace ChaseCrawford\Ratings\Tests\Rpi;

use ChaseCrawford\Ratings\Common\Outcome;
use ChaseCrawford\Ratings\Rpi\GameResult;
use ChaseCrawford\Ratings\Rpi\RpiCalculator;
use ChaseCrawford\Ratings\Rpi\Weights;
use PHPUnit\Framework\TestCase;

final class RpiCalculatorTest extends TestCase
{
    private const TOL = 1e-9;

    public function testSymmetricRoundRobinYieldsEqualRpi(): void
    {
        $rpi = new RpiCalculator();
        $result = $rpi->rate([
            new GameResult('A', 'B', Outcome::WIN),
            new GameResult('A', 'C', Outcome::LOSS),
            new GameResult('B', 'C', Outcome::WIN),
        ]);

        self::assertEqualsWithDelta(0.5, $result->ratings['A'], self::TOL);
        self::assertEqualsWithDelta(0.5, $result->ratings['B'], self::TOL);
        self::assertEqualsWithDelta(0.5, $result->ratings['C'], self::TOL);
    }

    public function testFourTeamHandComputedRpiForA(): void
    {
        $rpi = new RpiCalculator();
        $result = $rpi->rate([
            new GameResult('A', 'B', Outcome::WIN),
            new GameResult('A', 'C', Outcome::WIN),
            new GameResult('A', 'D', Outcome::WIN),
            new GameResult('B', 'C', Outcome::WIN),
            new GameResult('B', 'D', Outcome::WIN),
            new GameResult('C', 'D', Outcome::WIN),
        ]);

        self::assertEqualsWithDelta(0.625, $result->ratings['A'], self::TOL);
    }

    public function testReturnsRatingForEveryCompetitorInResults(): void
    {
        $rpi = new RpiCalculator();
        $result = $rpi->rate([
            new GameResult('A', 'B', Outcome::WIN),
            new GameResult('B', 'C', Outcome::WIN),
        ]);

        self::assertArrayHasKey('A', $result->ratings);
        self::assertArrayHasKey('B', $result->ratings);
        self::assertArrayHasKey('C', $result->ratings);
    }

    public function testCustomWeightsChangeRanking(): void
    {
        $rpi = new RpiCalculator(new Weights(own: 1.0, opponents: 0.0, opponentsOpponents: 0.0));
        $result = $rpi->rate([
            new GameResult('A', 'B', Outcome::WIN),
            new GameResult('A', 'C', Outcome::WIN),
            new GameResult('B', 'C', Outcome::LOSS),
        ]);

        self::assertEqualsWithDelta(1.0, $result->ratings['A'], self::TOL);
        self::assertEqualsWithDelta(0.0, $result->ratings['B'], self::TOL);
        self::assertEqualsWithDelta(0.5, $result->ratings['C'], self::TOL);
    }

    public function testDrawsCountAsHalfWins(): void
    {
        $rpi = new RpiCalculator(new Weights(own: 1.0, opponents: 0.0, opponentsOpponents: 0.0));
        $result = $rpi->rate([
            new GameResult('A', 'B', Outcome::DRAW),
            new GameResult('A', 'C', Outcome::DRAW),
        ]);

        self::assertEqualsWithDelta(0.5, $result->ratings['A'], self::TOL);
    }

    public function testEmptyGamesReturnsEmptyResult(): void
    {
        $rpi = new RpiCalculator();
        $result = $rpi->rate([]);
        self::assertSame([], $result->ratings);
    }
}
