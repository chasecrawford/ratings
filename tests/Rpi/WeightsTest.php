<?php

declare(strict_types=1);

namespace ChaseCrawford\Ratings\Tests\Rpi;

use ChaseCrawford\Ratings\Common\Exception\InvalidConfigurationException;
use ChaseCrawford\Ratings\Rpi\Weights;
use PHPUnit\Framework\TestCase;

final class WeightsTest extends TestCase
{
    public function testDefaultWeightsAreClassic255025(): void
    {
        $w = new Weights();
        self::assertSame(0.25, $w->own);
        self::assertSame(0.50, $w->opponents);
        self::assertSame(0.25, $w->opponentsOpponents);
    }

    public function testClassicFactoryReturnsCanonicalWeights(): void
    {
        $w = Weights::classic();
        self::assertSame(0.25, $w->own);
        self::assertSame(0.50, $w->opponents);
        self::assertSame(0.25, $w->opponentsOpponents);
    }

    public function testAcceptsCustomWeightsSummingToOne(): void
    {
        $w = new Weights(own: 0.4, opponents: 0.4, opponentsOpponents: 0.2);
        self::assertSame(0.4, $w->own);
    }

    public function testRejectsWeightsThatDoNotSumToOne(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        new Weights(own: 0.5, opponents: 0.5, opponentsOpponents: 0.5);
    }

    public function testRejectsNegativeWeights(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        new Weights(own: -0.1, opponents: 0.55, opponentsOpponents: 0.55);
    }
}
