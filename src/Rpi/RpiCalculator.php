<?php

declare(strict_types=1);

namespace ChaseCrawford\Ratings\Rpi;

final class RpiCalculator
{
    public function __construct(
        private readonly Weights $weights = new Weights(),
    ) {
    }

    /**
     * @param GameResult[] $games
     */
    public function rate(array $games): RpiResult
    {
        if ([] === $games) {
            return new RpiResult([]);
        }

        $competitors = $this->collectCompetitors($games);
        $ratings = [];

        foreach ($competitors as $competitor) {
            $wp = $this->winPercentage($competitor, $games, excludeOpponent: null);
            $owp = $this->opponentsWinPercentage($competitor, $games);
            $oowp = $this->opponentsOpponentsWinPercentage($competitor, $games);

            $ratings[$competitor] = $this->weights->own * $wp
                + $this->weights->opponents * $owp
                + $this->weights->opponentsOpponents * $oowp;
        }

        return new RpiResult($ratings);
    }

    /**
     * @param GameResult[] $games
     *
     * @return list<string>
     */
    private function collectCompetitors(array $games): array
    {
        $set = [];
        foreach ($games as $g) {
            $set[$g->competitorA] = true;
            $set[$g->competitorB] = true;
        }

        return array_keys($set);
    }

    /**
     * @param GameResult[] $games
     */
    private function winPercentage(string $competitor, array $games, ?string $excludeOpponent): float
    {
        $wins = 0.0;
        $count = 0;
        foreach ($games as $g) {
            if (!$this->involves($g, $competitor)) {
                continue;
            }
            if (null !== $excludeOpponent && $this->involves($g, $excludeOpponent)) {
                continue;
            }
            ++$count;
            $outcomeForCompetitor = ($g->competitorA === $competitor)
                ? $g->outcomeForA
                : $g->outcomeForA->inverse();
            $wins += $outcomeForCompetitor->score();
        }

        return 0 === $count ? 0.0 : $wins / $count;
    }

    /**
     * @param GameResult[] $games
     */
    private function opponentsWinPercentage(string $competitor, array $games): float
    {
        $sum = 0.0;
        $count = 0;
        foreach ($games as $g) {
            if (!$this->involves($g, $competitor)) {
                continue;
            }
            $opponent = ($g->competitorA === $competitor) ? $g->competitorB : $g->competitorA;
            $sum += $this->winPercentage($opponent, $games, excludeOpponent: $competitor);
            ++$count;
        }

        return 0 === $count ? 0.0 : $sum / $count;
    }

    /**
     * @param GameResult[] $games
     */
    private function opponentsOpponentsWinPercentage(string $competitor, array $games): float
    {
        $sum = 0.0;
        $count = 0;
        foreach ($games as $g) {
            if (!$this->involves($g, $competitor)) {
                continue;
            }
            $opponent = ($g->competitorA === $competitor) ? $g->competitorB : $g->competitorA;
            $sum += $this->opponentFullOwp($opponent, $games);
            ++$count;
        }

        return 0 === $count ? 0.0 : $sum / $count;
    }

    /**
     * @param GameResult[] $games
     */
    private function opponentFullOwp(string $competitor, array $games): float
    {
        $sum = 0.0;
        $count = 0;
        foreach ($games as $g) {
            if (!$this->involves($g, $competitor)) {
                continue;
            }
            $opponent = ($g->competitorA === $competitor) ? $g->competitorB : $g->competitorA;
            $sum += $this->winPercentage($opponent, $games, excludeOpponent: $competitor);
            ++$count;
        }

        return 0 === $count ? 0.0 : $sum / $count;
    }

    private function involves(GameResult $g, string $competitor): bool
    {
        return $g->competitorA === $competitor || $g->competitorB === $competitor;
    }
}
