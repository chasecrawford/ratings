# Ratings

[![CI](https://github.com/chasecrawford/ratings/actions/workflows/ci.yml/badge.svg)](https://github.com/chasecrawford/ratings/actions/workflows/ci.yml)
[![codecov](https://codecov.io/gh/chasecrawford/ratings/branch/main/graph/badge.svg)](https://codecov.io/gh/chasecrawford/ratings)
[![Latest Version](https://img.shields.io/packagist/v/chasecrawford/ratings.svg)](https://packagist.org/packages/chasecrawford/ratings)
[![PHP Version](https://img.shields.io/packagist/php-v/chasecrawford/ratings.svg)](https://packagist.org/packages/chasecrawford/ratings)
[![License](https://img.shields.io/packagist/l/chasecrawford/ratings.svg)](LICENSE)

A typed, dependency-free PHP library implementing the **Elo**, **Glicko-2**, and **RPI** rating systems. Pure-calculator design: feed in match data, get rating updates back. No accumulators, no global state, no I/O — drop into any consumer project (Laravel, Symfony, raw scripts, queue workers).

## Install

```bash
composer require chasecrawford/ratings
```

Requires PHP 8.2+.

## Quick start (Elo)

```php
use ChaseCrawford\Ratings\Common\Outcome;
use ChaseCrawford\Ratings\Elo\{EloCalculator, EloRating, ConstantK};

$elo = new EloCalculator(new ConstantK(k: 20));
$update = $elo->rate(
    a: new EloRating(1500),
    b: new EloRating(1400),
    outcomeForA: Outcome::WIN,
);

echo $update->newA->value;  // ~1507.20
echo $update->expectedA;    // ~0.640 (pre-match win probability)
```

## Elo

```php
use ChaseCrawford\Ratings\Elo\{EloCalculator, EloRating, ConstantK, UscfK, CallableK};
use ChaseCrawford\Ratings\Common\Outcome;

// K-factor strategies:
$elo = new EloCalculator(new ConstantK(15));   // simple constant
$elo = new EloCalculator(new UscfK());         // USCF tiered (40 provisional, then 32/24/16 by rating)
$elo = new EloCalculator(new CallableK(        // your own logic
    fn(EloRating $r, int $matches) => $r->value > 2400 ? 10 : 24
));

$update = $elo->rate(
    a: new EloRating(1500),
    b: new EloRating(1400),
    outcomeForA: Outcome::WIN,
    matchesPlayedA: 42,   // optional, used by UscfK and CallableK
    matchesPlayedB: 12,
);
```

## Glicko-2

Glicko-2 carries three numbers per player — rating, deviation (uncertainty), and volatility — and is updated in *rating periods* (e.g., weekly). You give the calculator one player's pre-period state and the list of opponents they faced; you get their new state back.

```php
use ChaseCrawford\Ratings\Glicko\{Glicko2Calculator, GlickoRating, PeriodMatch};
use ChaseCrawford\Ratings\Common\Outcome;

$glicko = new Glicko2Calculator(tau: 0.5);   // system constant; 0.3–1.2 reasonable

$alice = new GlickoRating(rating: 1500, deviation: 200, volatility: 0.06);

$newAlice = $glicko->updatePlayer($alice, [
    new PeriodMatch(new GlickoRating(1400, 30), Outcome::WIN),
    new PeriodMatch(new GlickoRating(1550, 100), Outcome::LOSS),
    new PeriodMatch(new GlickoRating(1700, 300), Outcome::LOSS),
]);

// Empty period = deviation grows (decay):
$idle = $glicko->updatePlayer($alice, []);
```

## RPI

RPI (Rating Percentage Index) is a *seasonal aggregate* — compute from a body of game results all at once.

```php
use ChaseCrawford\Ratings\Rpi\{RpiCalculator, GameResult, Weights};
use ChaseCrawford\Ratings\Common\Outcome;

$rpi = new RpiCalculator();   // defaults to classic 25/50/25 weights
// or: new RpiCalculator(new Weights(own: 0.30, opponents: 0.50, opponentsOpponents: 0.20))

$result = $rpi->rate([
    new GameResult('Duke',     'UNC',      Outcome::WIN),
    new GameResult('Duke',     'NC State', Outcome::LOSS),
    new GameResult('UNC',      'NC State', Outcome::WIN),
    // ... a season's worth
]);

$result->ratings['Duke'];   // computed RPI
$result->ranked();          // ['Duke' => 0.62, 'UNC' => 0.51, …] sorted descending
```

## Common patterns

```php
use ChaseCrawford\Ratings\Common\Outcome;

// Convert from raw scores:
Outcome::fromScores(myScore: 5, theirScore: 3);   // Outcome::WIN

// Numeric value for Elo/Glicko math:
Outcome::WIN->score();   // 1.0
Outcome::DRAW->score();  // 0.5
Outcome::LOSS->score();  // 0.0

// "From the other side's view":
Outcome::WIN->inverse();   // Outcome::LOSS
```

All exceptions extend `ChaseCrawford\Ratings\Common\Exception\RatingException`, with two subclasses: `InvalidRatingException` (NaN, negative deviation, etc.) and `InvalidConfigurationException` (weights don't sum to 1.0, self-play, etc.).

## Choosing an algorithm

| Algorithm | Best for | Per-player state | Update model |
|---|---|---|---|
| **Elo** | Pairwise head-to-head, simple to explain, needs few games to start working | 1 number (rating) | One match at a time |
| **Glicko-2** | When you care about *uncertainty* — players who haven't played recently or have few games should be rated less confidently | 3 numbers (rating, deviation, volatility) | Batch by rating period (e.g., weekly) |
| **RPI** | Season-end rankings where strength of schedule matters; college sports tradition | None — recomputed each time | Aggregate over a body of games |

## Versioning

Strict semver from v2.0.0. Major bumps for any breaking API change. The v1 line (`^1.0`) is unmaintained and incompatible with v2.

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md).

## License

[MIT](LICENSE) © Chase Crawford
