# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog 1.1.0](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [2.0.0] — 2026-05-DD

### Added

- **Glicko-2** algorithm (`ChaseCrawford\Ratings\Glicko\Glicko2Calculator`) validated against Glickman's 2013 reference paper worked example.
- **Pluggable Elo K-factor strategies**: `ConstantK`, `UscfK`, `CallableK`.
- **`Outcome` enum** in `Common\` with `fromScores()`, `score()`, and `inverse()` helpers.
- **Exception hierarchy** rooted at `RatingException` for unified error handling.
- **`RpiResult::ranked()`** helper returning ratings sorted descending.
- **Custom RPI weights** via `Weights` value object (default classic 25/50/25).
- **Packagist publication** — install with `composer require chasecrawford/ratings`.
- **CI pipeline**: PHPStan max-level, PHP-CS-Fixer style check, PHPUnit on PHP 8.2 / 8.3 / 8.4 with `prefer-lowest` and `prefer-stable`, 95% coverage gate.

### Changed

- **Namespace** flattened from `ChaseCrawford\EloRating\…` and `ChaseCrawford\RatingPercentageIndex\…` to `ChaseCrawford\Ratings\Elo\…` / `ChaseCrawford\Ratings\Glicko\…` / `ChaseCrawford\Ratings\Rpi\…` / `ChaseCrawford\Ratings\Common\…`.
- **API model** redesigned from in-memory accumulator (`addResult`/`getCompetitors`) to pure stateless calculators (`rate(...)`, `updatePlayer(...)`).
- **PHP requirement** raised from `>=8.0` to `^8.2`.
- All public types are now `final readonly` value objects with constructor validation.

### Removed

- **`Elo::addResult()` / `Elo::getCompetitors()` accumulator API.** Use `EloCalculator::rate()` per match and store state in your own data layer.
- **`RPI::addResult()` / `RPI::getCompetitors()` accumulator API.** Use `RpiCalculator::rate(array $games)` aggregate call.
- **`config/elo.php`.** Configuration now travels via the calculator's constructor.
- **`Competitor` classes.** Stateless model has no per-competitor object.

### Fixed

- `Elo::updateCompetitor()` called `Elo::calc()` with arguments in the wrong order in v1 (score and elo were swapped). The redesigned API makes this class of bug structurally impossible via typed value objects.
- `RPI::getCompetitorResults()` filtered on `$game['one']` / `$game['two']` keys that were never set in v1; results were always empty. The redesign uses typed `GameResult` value objects with named properties.
- `Elo` v1 used static properties initialized in an instance constructor, so two `Elo` instances with different configs would clobber each other globally. The redesign has no static state.

## [1.0.0] — 2023-01-11

Initial release. Elo and RPI accumulator APIs.

[Unreleased]: https://github.com/chasecrawford/ratings/compare/v2.0.0...HEAD
[2.0.0]: https://github.com/chasecrawford/ratings/compare/v1.0.0...v2.0.0
[1.0.0]: https://github.com/chasecrawford/ratings/releases/tag/v1.0.0
