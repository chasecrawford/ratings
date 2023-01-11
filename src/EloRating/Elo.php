<?php

namespace ChaseCrawford\EloRating;

class Elo
{

    private array $competitors = [];
    private static int $defaultKFactor;
    private static int $proRating;
    private static int $starterBoundry;

    public function  __construct()
    {
        $config = include(dirname(__FILE__) . '/../../config/elo.php');
        self::$defaultKFactor = $config['default_k_factor'];
        self::$proRating = $config['pro_rating'];
        self::$starterBoundry = $config['starter_boundry'];
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

    public static function calc(
        float $competitorOneElo,
        float $competitorTwoElo,
        int $competitorOneScore,
        int $competitorTwoScore,
        int $competitorOneNumberOfResults = 0
    ) : float
    {
        $expectedResult = self::getExpectedResult(
            $competitorOneElo,
            $competitorTwoElo
        );
        $kFactor = self::getKFactor($competitorOneElo, $competitorOneNumberOfResults);
        if($competitorOneScore === $competitorTwoScore) {
            $result = 0.5;
        } else {
            $result = $competitorOneScore > $competitorTwoScore
                ? 1
                : 0;
        }
        return $competitorOneElo + self::getChange($result, $expectedResult, $kFactor);
    }

    private static function getChange(
        int $result,
        float $expectedResult,
        int $kFactor
    ) : float
    {
        return $kFactor * ($result - $expectedResult);
    }

    public function getCompetitor(string $competitorName)
    {
        return $this->competitors[$competitorName] ?? new Competitor($competitorName);
    }

    public function getCompetitors() : array
    {
        return $this->competitors;
    }

    private static function getExpectedResult(
        float $elo,
        float $opponentElo
    ) : float
    {
        return 1.0 / (1.0 + (10.0 ** (($opponentElo - $elo) / 400.0)));
    }

    private static function getKFactor(float $elo, int $numOfResults)
    {
        $kFactor = self::$defaultKFactor;
        if($elo >= self::$proRating) {
            $kFactor = 10;
        }
        if($numOfResults < self::$starterBoundry) {
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
            self::calc(
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
