<?php

declare(strict_types=1);

namespace ChaseCrawford\Ratings\Common;

enum Outcome: string
{
    case WIN = 'win';
    case LOSS = 'loss';
    case DRAW = 'draw';

    public static function fromScores(int $myScore, int $theirScore): self
    {
        return match (true) {
            $myScore > $theirScore => self::WIN,
            $myScore < $theirScore => self::LOSS,
            default => self::DRAW,
        };
    }

    public function score(): float
    {
        return match ($this) {
            self::WIN => 1.0,
            self::DRAW => 0.5,
            self::LOSS => 0.0,
        };
    }

    public function inverse(): self
    {
        return match ($this) {
            self::WIN => self::LOSS,
            self::LOSS => self::WIN,
            self::DRAW => self::DRAW,
        };
    }
}
