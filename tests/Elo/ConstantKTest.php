<?php

declare(strict_types=1);

namespace ChaseCrawford\Ratings\Tests\Elo;

use ChaseCrawford\Ratings\Elo\ConstantK;
use ChaseCrawford\Ratings\Elo\EloRating;
use ChaseCrawford\Ratings\Elo\KFactor;
use PHPUnit\Framework\TestCase;

final class ConstantKTest extends TestCase
{
    public function testImplementsKfactorInterface(): void
    {
        self::assertInstanceOf(KFactor::class, new ConstantK(15));
    }

    public function testReturnsConstantValueRegardlessOfInputs(): void
    {
        $k = new ConstantK(20);
        self::assertSame(20, $k->for(new EloRating(1000), 0));
        self::assertSame(20, $k->for(new EloRating(2400), 100));
    }

    public function testDefaultKIs15(): void
    {
        $k = new ConstantK();
        self::assertSame(15, $k->k);
    }
}
