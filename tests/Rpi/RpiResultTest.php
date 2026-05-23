<?php

declare(strict_types=1);

namespace ChaseCrawford\Ratings\Tests\Rpi;

use ChaseCrawford\Ratings\Rpi\RpiResult;
use PHPUnit\Framework\TestCase;

final class RpiResultTest extends TestCase
{
    public function testHoldsRatingsMap(): void
    {
        $r = new RpiResult(['A' => 0.7, 'B' => 0.5, 'C' => 0.3]);
        self::assertSame(['A' => 0.7, 'B' => 0.5, 'C' => 0.3], $r->ratings);
    }

    public function testRankedReturnsSortedDescending(): void
    {
        $r = new RpiResult(['B' => 0.5, 'C' => 0.3, 'A' => 0.7]);
        self::assertSame(['A' => 0.7, 'B' => 0.5, 'C' => 0.3], $r->ranked());
    }

    public function testRankedPreservesKeysAndValues(): void
    {
        $r = new RpiResult(['Duke' => 0.6234, 'UNC' => 0.5821]);
        $ranked = $r->ranked();
        self::assertSame(['Duke', 'UNC'], array_keys($ranked));
        self::assertSame([0.6234, 0.5821], array_values($ranked));
    }
}
