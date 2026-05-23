<?php

declare(strict_types=1);

namespace ChaseCrawford\Ratings\Tests\Common\Exception;

use ChaseCrawford\Ratings\Common\Exception\InvalidConfigurationException;
use ChaseCrawford\Ratings\Common\Exception\InvalidRatingException;
use ChaseCrawford\Ratings\Common\Exception\RatingException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ExceptionHierarchyTest extends TestCase
{
    public function testInvalidRatingExtendsRatingException(): void
    {
        self::assertInstanceOf(RatingException::class, new InvalidRatingException('x'));
    }

    public function testInvalidConfigurationExtendsRatingException(): void
    {
        self::assertInstanceOf(RatingException::class, new InvalidConfigurationException('x'));
    }

    public function testRatingExceptionExtendsRuntimeException(): void
    {
        self::assertInstanceOf(RuntimeException::class, new RatingException('x'));
    }
}
