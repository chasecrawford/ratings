<?php

declare(strict_types=1);

namespace ChaseCrawford\Ratings\Tests\Rpi;

use ChaseCrawford\Ratings\Common\Exception\InvalidConfigurationException;
use ChaseCrawford\Ratings\Common\Outcome;
use ChaseCrawford\Ratings\Rpi\GameResult;
use PHPUnit\Framework\TestCase;

final class GameResultTest extends TestCase
{
    public function testHoldsCompetitorNamesAndOutcome(): void
    {
        $g = new GameResult('Duke', 'UNC', Outcome::WIN);
        self::assertSame('Duke', $g->competitorA);
        self::assertSame('UNC', $g->competitorB);
        self::assertSame(Outcome::WIN, $g->outcomeForA);
    }

    public function testRejectsSelfPlay(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        new GameResult('Duke', 'Duke', Outcome::WIN);
    }

    public function testRejectsEmptyCompetitorName(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        new GameResult('', 'UNC', Outcome::WIN);
    }
}
