<?php

namespace ChaseCrawford\EloRating;

class Competitor
{
    private float $elo;
    public int $numOfResults;

    public function __construct(string $competitor)
    {
        $config = include(dirname(__FILE__) . '/../../config/elo.php');
        $this->name = $competitor;
        $this->elo = $config['default_rating'];
    }

    public function getElo() : float
    {
        return $this->elo;
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function setElo($elo)
    {
        $this->elo = $elo;
    }
}