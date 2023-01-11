<?php

namespace ChaseCrawford\RatingPercentageIndex;

class Competitor
{
    private string $name;
    private float $rpi;

    public function __construct(string $competitor)
    {
        $this->name = $competitor;
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function getRPI() : float
    {
        return $this->rpi;
    }

    public function setRPI(float $rpi) : void
    {
        $this->rpi = $rpi;
    }
}