<?php

namespace ChaseCrawford;

class EloRating
{
    public $competitors = [];
    public $default_rating;
    public $games = [];
    public $pro_rating;
    public $starter_boundry;

    public function  __construct()
    {
        $config = include(dirname(__FILE__) . '/../config/elo.php');
        $this->default_k_factor = $config['default_k_factor'];
        $this->default_rating = $config['default_rating'];
        $this->pro_rating = $config['pro_rating'];
        $this->starter_boundry = $config['starter_boundry'];
    }

    public function addGame($one,$two,$result)
    {
        $game = [
            'one' => $one,
            'two' => $two,
            'result' => $result
        ];
        array_push($this->games,$game);
        $this->competitors[$one] = $this->getNewRating($one,$game);
        $this->competitors[$two] = $this->getNewRating($two,$game);
    }

    public function getChange($k_factor,$result,$expected) {
        return $k_factor * ( $result - $expected );
    }

    public function getExpected($other_rating,$old_rating) {
        return 1.0 / ( 1.0 + ( 10.0 ** (($other_rating - $old_rating) / 400.0) ) );
    }

    public function getGamesPlayed($competitor) {
        return array_filter($this->games,function($game)use($competitor){
            return $game['one'] == $competitor || $game['two'] == $competitor;
        });
    }

    public function getKFactor($competitor) {
        $kFactor = $this->default_k_factor;
        if($this->isPro($competitor)) $kFactor = 10;
        if($this->isStarter($competitor)) $kFactor = 25;
        return $kFactor;
    }

    public function getNewRating($competitor,$game)
    {
        $old_rating = $this->competitors[$competitor] ?? $this->default_rating;
        $other_rating = $this->competitors[$competitor == $game['one'] ? $game['two'] : $game['one']] ?? $this->default_rating;
        $expected = $this->getExpected($other_rating,$old_rating);
        $k_factor = $this->getKFactor($competitor);
        // if we're getting the rating for two we need to flip the result
        if($game['one'] == $competitor || $game['result'] == 0.5) {
            $result = $game['result'];
        } else {
            $result = $game['result'] == 1 ? 0 : 1;
        }
        return ($old_rating + $this->getChange($k_factor,$result,$expected));
    }

    public function isPro($competitor) {
        return ($this->competitors[$competitor] ?? $this->default_rating) >= $this->pro_rating;
    }

    public function isStarter($competitor) {
        return count($this->getGamesPlayed($competitor)) < $this->starter_boundry;
    }
}
