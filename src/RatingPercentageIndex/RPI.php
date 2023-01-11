<?php

namespace ChaseCrawford\RatingPercentageIndex;

class RPI
{

    private array $competitors = [];
    private array $results = [];

    public function addResult(
        string $competitorOneName,
        string $competitorTwoName,
        int $competitorOneScore,
        int $competitorTwoScore
    ) : void
    {
        $this->results[] = [
            'competitorOneName' => $competitorOneName,
            'competitorTwoName' => $competitorTwoName,
            'competitorOneScore' => $competitorOneScore,
            'competitorTwoScore' => $competitorTwoScore,
        ];
        $this->updateCompetitors($competitorOneName, $competitorTwoName);
    }

    public function calcNumOfResults(string $competitor) : int
    {
        return count($this->getCompetitorResults($competitor));
    }

    public function calcNumOfWins(string $competitor, array $results) : int
    {
        $wins = 0;
        foreach($results as $result) {
            if($result['competitorOneName'] == $competitor && $result['competitorOneScore'] > $result['competitorTwoScore']) $wins++;
            if($result['competitorTwoName'] == $competitor && $result['competitorTwoScore'] > $result['competitorOneScore']) $wins++;
        }
        return $wins;
    }

    public function calcOpponentWinPercentage(string $competitor) : float
    {
        $opponents = [];
        $wins = 0;
        $numOfResults = 0;
        foreach($this->getCompetitorResults($competitor) as $result)
        {
            $opponent = $competitor === $result['competitorOneName'] ? $result['competitorTwoName'] : $result['competitorOneName'];
            if(!in_array($opponent, $opponents)) $opponents[] = $opponent;
        }
        foreach($opponents as $opponent) {
            $numOfResults += $this->calcNumOfResults($opponent);
            $wins += $this->calcNumOfWins($opponent, $this->getCompetitorResults($opponent));
        }
        return $wins / $numOfResults;
    }

    public function calcOpponentsOpponentWinPercentage(string $competitor) : float
    {
        $opponents = [];
        $wins = 0;
        $numOfResults = 0;
        foreach($this->getCompetitorResults($competitor) as $result)
        {
            $opponent = $competitor === $result['competitorOneName'] ? $result['competitorTwoName'] : $result['competitorOneName'];
            foreach($this->getCompetitorResults($opponent) as $r) {
                $o = $opponent === $result['competitorOneName'] ? $result['competitorTwoName'] : $result['competitorOneName'];
                if(!in_array($o, $opponents)) $opponents[] = $o;
            }
        }
        foreach($opponents as $opponent) {
            $numOfResults += $this->calcNumOfResults($opponent);
            $wins += $this->calcNumOfWins($opponent, $this->getCompetitorResults($opponent));
        }
        return $wins / $numOfResults;
    }

    public function calcWinPercentage(string $competitor) : float
    {
        $results = $this->getCompetitorResults($competitor);
        $wins = $this->calcNumOfWins($competitor, $results);
        return $wins / count($results);
    }

    public function getCompetitorResults(string $competitor) : array
    {
        return array_filter($this->results, function ($game) use ($competitor) {
            return $game['one'] == $competitor || $game['two'] == $competitor;
        });
    }

    public function getCompetitors() : array
    {
        return $this->competitors;
    }

    public function updateCompetitors(string $competitorOne, string $competitorTwo) : void
    {
        foreach([$competitorOne, $competitorTwo] as $competitor) {
            $winPercentage = $this->calcWinPercentage($competitor);
            $oppWinPercentage = $this->calcOpponentWinPercentage($competitor);
            $oppOppWinPercentage = $this->calcOpponentsOpponentWinPercentage($competitor);
            $rpi = ($winPercentage * .25) + ($oppWinPercentage * .5) + ($oppOppWinPercentage * .25);
            if(!isset($this->competitors[$competitor])) {
                $this->competitors[$competitor] = new Competitor($competitor);
            }
            $this->competitors[$competitor]->setRPI($rpi);
        }
    }



}