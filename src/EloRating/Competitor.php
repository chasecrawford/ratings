<?php

namespace ChaseCrawford\EloRating;

class Competitor
{
    private float $elo;
    private string $name;
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

    public function setElo($elo) : void
    {
        $this->elo = $elo;
    }
}