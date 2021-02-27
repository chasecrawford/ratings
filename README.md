# Ratings

A PHP class which implements the Elo rating system & Rating Percentage index.

# Install

```
composer require chasecrawford/ratings
```

# Usage

```
use ChaseCrawford\EloRating;
use ChaseCrawford\RatingPercentageIndex;

$elo = new EloRating;
$rpi = new RatingPercentageIndex;

$games = [
    [
        'one' => 'Louisville',
        'two' => 'Kentucky',
        'result' => 1 // One Wins (1), Two Wins (0), Draw (0.5)
    ],
    ...
];

foreach($games as $game):
    $elo->addGame(
        $game['one'], 
        $game['two'], 
        $game['result']
    );

    $rpi->addGame(
        $game['one'], 
        $game['two'], 
        $game['result']
    );
endforeach;

print "<pre>";

print_r($elo);
print_r($rpi);

print "</pre>";
exit;
```
