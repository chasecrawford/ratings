<?php

namespace ChaseCrawford;

class RatingPercentageIndex
{

    public $competitors = [];
    public $games = [];

    public function addGame($one,$two,$result)
    {
        $game = [
            'one' => $one,
            'two' => $two,
            'result' => $result
        ];
        array_push($this->games,$game);
        $this->competitors[$one] = $this->getNewRating($one);
        $this->competitors[$two] = $this->getNewRating($two);
    }

    public function getGamesPlayed($competitor)
    {
        return array_filter($this->games,function($game)use($competitor){
            return $game['one'] == $competitor || $game['two'] == $competitor;
        });
    }

    public function getNewRating($competitor)
    {
        $win_per = $this->getWinPer($competitor);
        $opp_win_per = $this->getOppWinPer($competitor);
        $opp_oop_win_per = $this->getOppOppWinPer($competitor);;
        return ($win_per * .25) + ($opp_win_per * .5) + ($opp_oop_win_per * .25);
    }

    public function getNumOfWins($competitor,$games) {
        $wins = 0;
        foreach($games as $game) {
            if($game['one'] == $competitor && $game['result'] == 1) $wins++;
            if($game['two'] == $competitor && $game['result'] == 0) $wins++;
        }
        return $wins;
    }

    public function getOppWinPer($competitor)
    {
        $gp = 0;
        $wins = 0;
        foreach($this->getGamesPlayed($competitor) as $game)
        {
            $opp = $competitor === $game['one'] ? $game['two'] : $game['one'];
            $games = $this->getGamesPlayed($opp);
            $wins += $this->getNumOfWins($opp,$games);
            $gp += count($games);
        }
        return $wins / $gp;
    }

    public function getOppOppWinPer($competitor)
    {
        $gp = 0;
        $wins = 0;
        foreach($this->getGamesPlayed($competitor) as $game)
        {
            $opp = $competitor === $game['one'] ? $game['two'] : $game['one'];
            foreach($this->getGamesPlayed($opp) as $oppGame)
            {
                $opp_opp = $opp === $oppGame['one'] ? $oppGame['two'] : $oppGame['one'];
                $games = $this->getGamesPlayed($opp_opp);
                $wins += $this->getNumOfWins($opp_opp,$games);
                $gp += count($games);
            }
        }
        return $wins / $gp;
    }

    public function getWinPer($competitor)
    {
        $games = $this->getGamesPlayed($competitor);
        $wins = $this->getNumOfWins($competitor,$games);
        return $wins / count($games);
    }

}
