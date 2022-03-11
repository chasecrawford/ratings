<?php

namespace ChaseCrawford\EloRating;

class EloRating
{

    private array $competitors = [];
    private int $defaultKFactor;
    private int $proRating;
    private int $starterBoundry;

    public function  __construct()
    {
        $config = include(dirname(__FILE__) . '/../../config/elo.php');
        $this->defaultKFactor = $config['default_k_factor'];
        $this->proRating = $config['pro_rating'];
        $this->starterBoundry = $config['starter_boundry'];
    }

    public function addResult(
        string $competitorOneName,
        string $competitorTwoName,
        int $competitorOneScore,
        int $competitorTwoScore
    ) : void
    {
        $competitorOne = $this->getCompetitor($competitorOneName);
        $competitorTwo = $this->getCompetitor($competitorTwoName);
        $this->updateCompetitor(
            $competitorOne,
            $competitorOneScore,
            $competitorTwoScore,
            $competitorOne->getElo(),
            $competitorTwo->getElo(),
        );
        $this->updateCompetitor(
            $competitorTwo,
            $competitorTwoScore,
            $competitorOneScore,
            $competitorTwo->getElo(),
            $competitorOne->getElo(),
        );
    }

    public function calculateElo(
        float $competitorOneElo,
        float $competitorTwoElo,
        int $competitorOneScore,
        int $competitorTwoScore,
        int $competitorOneNumberOfResults = 0
    ) : float
    {
        $expectedResult = $this->getExpectedResult(
            $competitorOneElo,
            $competitorTwoElo
        );
        $kFactor = $this->getKFactor($competitorOneElo, $competitorOneNumberOfResults);
        if($competitorOneScore === $competitorTwoScore) {
            $result = 0.5;
        } else {
            $result = $competitorOneScore > $competitorTwoScore
                ? 1
                : 0;
        }
        return $competitorOneElo + $this->getChange($result, $expectedResult, $kFactor);
    }

    private function getChange(
        int $result,
        float $expectedResult,
        int $kFactor
    )
    {
        return $kFactor * ($result - $expectedResult);
    }

    public function getCompetitor(string $competitorName)
    {
        return $this->competitors[$competitorName] ?? new Competitor($competitorName);
    }

    public function getCompetitors()
    {
        return $this->competitors;
    }

    private function getExpectedResult(
        float $elo,
        float $opponentElo
    ) : float
    {
        return 1.0 / (1.0 + (10.0 ** (($opponentElo - $elo) / 400.0)));
    }

    private function getKFactor(float $elo, int $numOfResults)
    {
        $kFactor = $this->defaultKFactor;
        if($elo >= $this->proRating) {
            $kFactor = 10;
        }
        if($numOfResults < $this->starterBoundry) {
            $kFactor = 25;
        }
        return $kFactor;
    }

    private function updateCompetitor(
        Competitor $competitor,
        string $score,
        string $opponentScore,
        float $elo,
        float $opponentElo
    ) : void
    {
        $competitor->setElo(
            $this->calculateElo(
                $score,
                $opponentScore,
                $elo,
                $opponentElo,
                $competitor->numOfResults
            )
        );
        $competitor->numOfResults++;
        $this->competitors[$competitor->getName()] = $competitor;
    }

}
