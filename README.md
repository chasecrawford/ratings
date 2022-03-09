# Ratings

A PHP class which implements the Elo rating system & Rating Percentage index.

## Install

```
composer require chasecrawford/ratings
```

## Using Elo

Calculate a new elo rating for a competitor after a result

```
use ChaseCrawford\EloRating;

$elo = new EloRating;

$newEloRating = $elo->calculateElo(
    1000,   // (float) competitor elo rating
    1000,   // (float) opponent's elo rating
    71,     // (int) competitor score
    70,     // (int) opponent score
    0       // (int) number of matches competitor played previously (optional)
)
```

Find the elo ratings for all competitors from a group of results

```
$results = [...];

foreach($results as $result) {
  $elo->addResult(
       $result->competitorOneName,  // (string) unique name for competitor 1
       $result->competitorTwoName,  // (string) unique name for competitor 2
       $result->competitorOneScore, // (int) score for competitor 1
       $result->competitorTwoScore  // (int) score for competitor 2
  )
}

print_r($elo->getCompetitors())
```

## Using RPI

```
use ChaseCrawford\RatingPercentageIndex;

$rpi = new RatingPercentageIndex;

$results = [...];

foreach($results as $result) {
  $rpi->addGame(
       $result->competitorOneName,  // (string) unique name for competitor 1
       $result->competitorTwoName,  // (string) unique name for competitor 2
       $result->outcome, // 1 for win, 0 for loss, 0.5 for draw
  )
}

print_r($rpi)
```

