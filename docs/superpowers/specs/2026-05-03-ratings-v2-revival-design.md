# Ratings v2.0 — Revival Design

**Date:** 2026-05-03
**Author:** Chase Crawford (with Claude)
**Status:** Draft, awaiting user review
**Repository:** https://github.com/chasecrawford/ratings

---

## Summary

Revive the dormant `chasecrawford/ratings` PHP library (last commit January 2023) as a credible, portfolio-worthy package. Replace the v1 codebase entirely with a fresh **v2.0.0** that:

- Targets PHP 8.2+
- Implements three rating algorithms — **Elo**, **Glicko-2**, **RPI** — each with its own namespace
- Exposes a **pure-calculator** API: stateless, named-argument, immutable value objects
- Ships with PHPUnit tests (95%+ coverage), PHPStan max-level static analysis, GitHub Actions CI, and a published Packagist entry

The package's purpose: developers feed in entries with the necessary data and get rating results back, without thinking about the underlying algorithms.

## Goals

1. **Library credibility.** A senior PHP developer visiting the repo should see a clean public API, comprehensive tests, green CI, and a well-structured README within 60 seconds.
2. **Drop-in usability.** `composer require chasecrawford/ratings` works on Packagist; integration into any consumer project (Laravel, Symfony, raw script, queue worker) is a one-liner.
3. **Algorithmic correctness.** All three implementations validated against published reference vectors (Glickman's 2013 Glicko-2 paper, FIDE/USCF Elo examples, NCAA RPI worked seasons).
4. **Portfolio signal.** Five green badges, hand-curated CHANGELOG, "Choosing an algorithm" comparison table — small touches that signal craft.

## Non-goals

- **No interactive web demo or hosted dashboard.** The package itself *is* the portfolio piece.
- **No persistence layer or storage adapters.** Pure calculators only; consumers handle their own state.
- **No backwards compatibility with v1.** v1 stays at its current GitHub tag; v2 is a fresh redesign.
- **No TrueSkill in v2.0.** Deferred to a possible v2.1; documented in the roadmap.
- **No mutation testing, property-based testing, or dedicated docs site** — overbuilt for a 3-algorithm calculator library.

## Decisions made during brainstorming

| Decision | Choice | Reasoning |
|---|---|---|
| Revival shape | Library polish + redesign (not demo, not reframe-as-app) | User clarified the package itself is the portfolio piece |
| Algorithm scope | Elo + RPI + Glicko-2 (TrueSkill deferred) | Ship a polished release this month rather than fight TrueSkill's complexity |
| API uniformity | Per-algorithm namespaces with shared building blocks | The algorithms have fundamentally different shapes (Elo pairwise, Glicko-2 per-period, RPI seasonal-aggregate); a unified interface would leak abstractions |
| Interaction model | Pure calculator (stateless) | Users hold their own state; library does only math |
| PHP version | `^8.2` | Unlocks `readonly` classes (essential for value objects); PHP 8.1 hit EOL Dec 2025 |
| Versioning | v2.0.0 fresh release; v1 frozen | Composer semver protects existing v1 consumers |

---

## 1. Project shape & layout

### Identity

- **Name:** `chasecrawford/ratings` (unchanged)
- **License:** MIT (unchanged)
- **Root namespace:** `ChaseCrawford\Ratings\` (changed — see "Namespace rename")
- **First v2 release:** `v2.0.0`
- **Distribution:** Packagist (new for v2)

### Directory layout

```
ratings/
├── composer.json                # name, deps, autoload, scripts
├── README.md                    # rewritten for v2
├── CHANGELOG.md                 # keepachangelog format, hand-curated
├── CONTRIBUTING.md              # how to run tests, code style, PR expectations
├── SECURITY.md                  # one-line security disclosure policy
├── LICENSE                      # MIT
├── phpunit.xml.dist             # PHPUnit config
├── phpstan.neon.dist            # PHPStan config (level: max)
├── .php-cs-fixer.dist.php       # @PSR12 + @Symfony rule sets
├── .github/
│   ├── workflows/ci.yml         # lint + phpstan + phpunit on PHP 8.2/8.3/8.4
│   └── ISSUE_TEMPLATE/
│       ├── bug_report.md
│       └── feature_request.md
├── src/
│   ├── Common/
│   │   ├── Outcome.php          # the enum
│   │   └── Exception/           # RatingException + subclasses
│   ├── Elo/                     # EloCalculator, EloRating, K-factor strategies, EloUpdate
│   ├── Glicko/                  # Glicko2Calculator, GlickoRating, PeriodMatch
│   └── Rpi/                     # RpiCalculator, GameResult, Weights, RpiResult
└── tests/
    ├── Common/
    ├── Elo/
    ├── Glicko/
    └── Rpi/
```

### Namespace rename

v1 used `ChaseCrawford\EloRating\Elo` (the namespace doubled the algorithm name). v2 flattens to:

- `ChaseCrawford\Ratings\Common\…`
- `ChaseCrawford\Ratings\Elo\…`
- `ChaseCrawford\Ratings\Glicko\…`
- `ChaseCrawford\Ratings\Rpi\…`

### Files removed from v1

- `config/elo.php` — eliminated. The v1 code does `include(dirname(__FILE__) . '/../../config/elo.php')`, which silently breaks when installed under a consumer's `vendor/`. In a stateless calculator, configuration travels with each call (or via a config object passed to the calculator's constructor), not as global file state.
- v1 `Competitor` classes (both `EloRating\Competitor` and `RatingPercentageIndex\Competitor`) — not needed in a stateless model.

---

## 2. Per-algorithm APIs

Common pattern across all three algorithms:

- One `*Calculator` class per algorithm (configurable via constructor; immutable)
- `final readonly` value objects for inputs and outputs
- Methods are pure: state in, new state out
- No accumulators, no global config, no static state

### 2.1 Elo

**Public types:**

```php
namespace ChaseCrawford\Ratings\Elo;

final readonly class EloRating
{
    public function __construct(public float $value);
}

interface KFactor
{
    public function for(EloRating $rating, int $matchesPlayed): int;
}

final readonly class ConstantK implements KFactor
{
    public function __construct(public int $k = 15);
}

final readonly class UscfK implements KFactor { /* USCF tiered K formula */ }

final readonly class CallableK implements KFactor
{
    public function __construct(private \Closure $fn);
}

final readonly class EloUpdate
{
    public function __construct(
        public EloRating $newA,
        public EloRating $newB,
        public float $expectedA,    // pre-match win probability for A
        public float $expectedB,
    );
}

final class EloCalculator
{
    public function __construct(private readonly KFactor $kFactor = new ConstantK(15));

    public function rate(
        EloRating $a,
        EloRating $b,
        \ChaseCrawford\Ratings\Common\Outcome $outcomeForA,
        int $matchesPlayedA = 0,
        int $matchesPlayedB = 0,
    ): EloUpdate;
}
```

**Usage:**

```php
$elo = new EloCalculator(new ConstantK(k: 20));
$update = $elo->rate(
    a: new EloRating(1500),
    b: new EloRating(1400),
    outcomeForA: Outcome::WIN,
);
$update->newA->value;   // ~1507.69
$update->expectedA;     // ~0.64
```

### 2.2 Glicko-2

Glicko-2's natural shape is **per-player, per-period, batch**: given one player's pre-period state and a list of opponents they faced in that period, return their new state.

**Public types:**

```php
namespace ChaseCrawford\Ratings\Glicko;

final readonly class GlickoRating
{
    public function __construct(
        public float $rating = 1500.0,        // μ
        public float $deviation = 350.0,      // φ (uncertainty)
        public float $volatility = 0.06,      // σ (rate of change in skill)
    );
}

final readonly class PeriodMatch
{
    public function __construct(
        public GlickoRating $opponent,
        public \ChaseCrawford\Ratings\Common\Outcome $outcome,
    );
}

final class Glicko2Calculator
{
    public function __construct(public readonly float $tau = 0.5);

    /**
     * @param PeriodMatch[] $matchesInPeriod
     *        Empty array = no matches this period; deviation grows (decay).
     */
    public function updatePlayer(GlickoRating $player, array $matchesInPeriod): GlickoRating;
}
```

**Usage:**

```php
$glicko = new Glicko2Calculator(tau: 0.5);
$alice = new GlickoRating(rating: 1500, deviation: 200, volatility: 0.06);

$newAlice = $glicko->updatePlayer($alice, matchesInPeriod: [
    new PeriodMatch(new GlickoRating(1400, 30), Outcome::WIN),
    new PeriodMatch(new GlickoRating(1550, 100), Outcome::LOSS),
    new PeriodMatch(new GlickoRating(1700, 300), Outcome::LOSS),
]);
```

### 2.3 RPI (intrinsically aggregate)

**Public types:**

```php
namespace ChaseCrawford\Ratings\Rpi;

final readonly class GameResult
{
    public function __construct(
        public string $competitorA,
        public string $competitorB,
        public \ChaseCrawford\Ratings\Common\Outcome $outcomeForA,
    );
}

final readonly class Weights
{
    public function __construct(
        public float $own = 0.25,
        public float $opponents = 0.50,
        public float $opponentsOpponents = 0.25,
    );
    // Throws InvalidConfigurationException if weights don't sum to 1.0 ± epsilon.

    public static function classic(): self;   // 0.25 / 0.50 / 0.25
}

final readonly class RpiResult
{
    /** @param array<string, float> $ratings */
    public function __construct(public array $ratings);

    /** @return array<string, float> sorted by rating descending */
    public function ranked(): array;
}

final class RpiCalculator
{
    public function __construct(private readonly Weights $weights = new Weights());

    /** @param GameResult[] $games */
    public function rate(array $games): RpiResult;
}
```

**Usage:**

```php
$rpi = new RpiCalculator();   // classic weights by default
$result = $rpi->rate(games: [
    new GameResult('Duke', 'UNC',     Outcome::WIN),
    new GameResult('Duke', 'NC State', Outcome::LOSS),
    new GameResult('UNC',  'NC State', Outcome::WIN),
]);
$result->ratings['Duke'];    // computed RPI
$result->ranked();           // ordered by rating, descending
```

### 2.4 API design rationale

- **Calculator classes (not pure static functions)** because configuration (K-factor strategy, Glicko τ, RPI weights) needs to live somewhere; passing it into every call gets noisy. Calculators are still immutable; methods are still pure.
- **Named arguments** are the recommended invocation style; parameter names are part of the public API.
- **Value objects** earn their keep when they appear in collections (`PeriodMatch`, `GameResult`); Elo's pairwise inputs don't need wrapping.

---

## 3. Shared building blocks (`Common/`)

Intentionally minimal.

### 3.1 `Outcome` enum

```php
namespace ChaseCrawford\Ratings\Common;

enum Outcome: string
{
    case WIN  = 'win';
    case LOSS = 'loss';
    case DRAW = 'draw';

    /** Convenience: most users have (myScore, theirScore), not "did I win?". */
    public static function fromScores(int $mine, int $theirs): self;

    /** WIN=1.0, DRAW=0.5, LOSS=0.0. Used by Elo and Glicko-2 internally. */
    public function score(): float;

    /** "From the other side's view." DRAW→DRAW, WIN↔LOSS. */
    public function inverse(): self;
}
```

### 3.2 Exception hierarchy

```php
namespace ChaseCrawford\Ratings\Common\Exception;

class RatingException                 extends \RuntimeException {}      // base — catch-all
class InvalidRatingException          extends RatingException {}        // NaN, negative deviation, etc.
class InvalidConfigurationException   extends RatingException {}        // weights don't sum to 1.0, etc.
```

### 3.3 Validation philosophy

Validation lives in **value-object constructors**, never in math methods. `new GlickoRating(deviation: -1)` throws `InvalidRatingException` immediately; `Glicko2Calculator::updatePlayer()` then assumes its inputs are valid and never re-checks.

This is the upside of `final readonly` value objects: invariants are enforced at construction, the rest of the codebase stays clean. An invalid value object cannot exist at runtime.

### 3.4 Deliberately not in `Common/`

- ❌ Shared `Match` / `Result` value object — Elo doesn't need one; Glicko has `PeriodMatch`; RPI has `GameResult`. Each shaped to its algorithm.
- ❌ Shared `Rating` interface or base class — `EloRating` is one float; `GlickoRating` carries three; RPI has no per-competitor rating object at all. No useful abstraction lives at the intersection.
- ❌ Shared `RatingSystem` interface — explicitly rejected during brainstorming (the algorithms don't share verbs).

---

## 4. Testing & quality

### 4.1 Test framework

- **PHPUnit ^11**, one test class per src class, mirroring directory structure
- **`declare(strict_types=1);`** at the top of every src and test file
- Pure-calculator design = every test is `assertEquals(expected, $calculator->method(...))` with no setup, no mocks

### 4.2 Reference vectors (the heart of the suite)

- **Glicko-2:** `Glicko2ReferenceTest::test_glickman_2013_worked_example()` — encodes Glickman's published numerical example line for line, asserting intermediate `g(φ)`, `E(μ, μⱼ, φⱼ)`, `v`, `Δ`, and final state values to 6 decimal places.
- **Elo:** hand-computed cases against FIDE's published rating-change examples and chess.com documentation.
- **RPI:** worked examples from publicly archived NCAA basketball seasons.

### 4.3 Coverage

- **Target: 95%+ line coverage.** CI fails the build below threshold.
- Pure calculators with no I/O have no excuse for uncovered branches; the only legitimately uncovered lines are typehints in `__construct` signatures.

### 4.4 Static analysis

- **PHPStan ^2.0 at level max (10).**
- Generic types on collections (`array<PeriodMatch>`, `array<string, float>`).
- `@phpstan-immutable` on value objects.

### 4.5 Code style

- **PHP-CS-Fixer** with `@PSR12` + `@Symfony` rule sets.
- Composer scripts: `composer fix` (auto-fix), `composer style-check` (CI verify).

### 4.6 CI matrix (GitHub Actions)

Triggered on push and PR:

| Check | Command | Matrix |
|---|---|---|
| Style | `composer style-check` | PHP 8.2 |
| Static analysis | `vendor/bin/phpstan analyse` | PHP 8.2 |
| Tests | `vendor/bin/phpunit --coverage-clover` | PHP 8.2, 8.3, 8.4 × `prefer-lowest` & `prefer-stable` (6 jobs) |
| Coverage | upload to Codecov | one job |

README badges: build status, coverage %, Packagist version, PHP version, license.

### 4.7 Deliberately not added

- ❌ Mutation testing (Infection)
- ❌ Property-based testing (Eris)
- ❌ Integration tests (nothing to integrate with)

---

## 5. Distribution, docs, and release

### 5.1 Versioning

- Strict semver from v2.0.0: patch = bugfix, minor = additive, major = breaking
- v1 line frozen — never updated, never deleted
- One package, one version (no per-algorithm semver)

### 5.2 Packagist

- Submit to packagist.org via the GitHub repo URL
- Wire GitHub webhook for auto-update on tag push
- After v2.0.0 ships: `composer require chasecrawford/ratings:^2.0` resolves cleanly

### 5.3 README structure

1. Title + badges (build · coverage · packagist · php · license)
2. One-paragraph what-and-why
3. Install (one line)
4. Quick start (6-line Elo example)
5. Algorithm sections, in order of complexity:
   - Elo (calculator + K-factor strategies)
   - Glicko-2 (calculator + rating periods + 3-number state)
   - RPI (calculator + weight presets)
6. Common patterns (Outcome enum, fromScores helper, exception handling)
7. **Choosing an algorithm** (comparison table — pairwise vs aggregate, simple vs uncertainty-aware, etc.)
8. Versioning policy (one paragraph)
9. Contributing (link to CONTRIBUTING.md)
10. License

### 5.4 CHANGELOG

`CHANGELOG.md` in [keepachangelog.com](https://keepachangelog.com/en/1.1.0/) format. Hand-curated, not auto-generated.

### 5.5 Light governance files

- `CONTRIBUTING.md` — how to run tests, code style, PR expectations
- `.github/ISSUE_TEMPLATE/bug_report.md` and `feature_request.md`
- `SECURITY.md` — one-line policy: report security issues privately

### 5.6 Deliberately not added

- ❌ Dedicated docs site (GitHub Pages, MkDocs, phpDoc-generated)
- ❌ Discord / community / discussions channels
- ❌ Donation / sponsor links

### 5.7 Portfolio entry on chasecrawford.dev (informational, not in this repo)

Suggested format:

> **Ratings** — *2026, PHP*
> A typed, dependency-free PHP library implementing the Elo, Glicko-2, and RPI rating systems. Pure-calculator API designed to drop into any consumer project. PHPStan max-level, 95%+ test coverage, validated against published reference vectors.
> [GitHub] · [Packagist]

Tech tags: `PHP 8.2` · `Composer` · `PHPUnit` · `PHPStan` · `GitHub Actions`

---

## 6. Known v1 issues being fixed by this redesign

These bugs were identified during the brainstorm exploration. v2's redesign eliminates them rather than patching:

1. **`Elo::updateCompetitor` calls `Elo::calc` with arguments in the wrong order.** v1 signature is `calc(float elo1, float elo2, int score1, int score2, int matches)` but `updateCompetitor` calls it as `calc(score, opponentScore, elo, opponentElo, matches)` — score and elo are swapped. v2 has no `updateCompetitor` (no accumulator); the calculator's `rate()` method takes typed value objects and an `Outcome` enum, making this class of bug structurally impossible.
2. **`RPI::getCompetitorResults` filters on `$game['one']` and `$game['two']`** but results are stored with keys `competitorOneName` / `competitorTwoName`. v2 uses `final readonly class GameResult` with named properties — no string-keyed arrays.
3. **`Elo` initializes static properties from the instance constructor**, meaning two `Elo` instances with different configs would clobber each other's defaults globally. v2 calculators are immutable instances, no static state.
4. **`config/elo.php` loaded via relative-path `include`**, which fails silently when the package is installed under a consumer's `vendor/`. v2 has no config file; configuration travels via the calculator's constructor.
5. **`starter_boundry`** misspelled (should be `boundary`). v2 doesn't expose this concept publicly; if it appears internally, the spelling is correct.
6. **No tests, no CI, no static analysis.** Addressed in §4.

---

## 7. Out of scope (future v2.x work)

- **TrueSkill** — Bayesian skill model for teams and free-for-alls. Defer to v2.1.
- **Property-based testing** — interesting for these algorithms; defer to v2.x if appetite remains.
- **Additional Elo K-factor strategies** beyond constant / USCF / callable (e.g., FIDE tiered, chess.com formulas) — could land as minor releases.
- **Additional RPI variants** (e.g., NCAA basketball post-2004 home/road weighting) — could land as minor releases.
- **Visual demo / hosted leaderboard / interactive web app** — explicitly rejected during brainstorming; the package itself is the portfolio piece.

---

## Appendix: brainstorming trail

The user and assistant arrived at this design through five rounds of clarifying questions on 2026-05-03:

1. **Revival shape** — User chose "reframe entirely" initially, then clarified mid-conversation to "library polish + redesign" (the package itself is the portfolio piece, no demo).
2. **Algorithm scope** — User chose "expand to a small rating-systems toolkit" (option B), then later accepted "drop TrueSkill from v2.0" (option D) to ship faster.
3. **API uniformity** — User chose "per-algorithm namespaces with shared building blocks" (option B).
4. **Interaction model** — User chose "pure calculator (stateless)" (option A).
5. **PHP version** — User chose PHP 8.2+ (option C).
6. **Algorithm depth** — User chose "drop TrueSkill, ship Elo + RPI + Glicko-2 polished" (option D).
