# CLAUDE.md

Guidance for Claude Code when working in this repository. Short and load-bearing — every session reads this.

## Project at a glance

`chasecrawford/ratings` — a typed, dependency-free PHP library implementing the **Elo**, **Glicko-2**, and **RPI** rating systems. Distributed via Packagist; consumed with `composer require chasecrawford/ratings`. PHP 8.2+, MIT licensed.

The package is a **pure calculator**: stateless, named-argument, immutable value objects. Consumers hold their own state (player ratings, match history, etc.); the library does only math. No accumulators, no global config, no static state, no I/O.

**Current status:** v2 algorithm code complete on `feat/v2-revival`; release work (CI coverage gate, README, CHANGELOG, governance) in progress. v1 (last commit Jan 2023) was a stateful, bug-laden accumulator API. v2.0 is a fresh redesign — v1 stays frozen at the `v1.0.0` git tag for existing consumers. All three algorithms (`Elo/EloCalculator`, `Glicko/Glicko2Calculator`, `Rpi/RpiCalculator`) are landed and validated against reference vectors. The remaining work is documentation, the 95% coverage gate in CI, and the manual release steps (PR → tag → Packagist).

History and rationale:
- `docs/superpowers/specs/2026-05-03-ratings-v2-revival-design.md` — canonical v2 design doc. Read this before touching public API shape, namespace layout, or testing strategy. Includes the brainstorming decisions table, the per-algorithm API sketches, and the catalog of v1 bugs being structurally eliminated.
- `docs/superpowers/plans/2026-05-03-ratings-v2-revival.md` — task-by-task implementation plan with checkbox tracking.

## Architecture

### Per-algorithm namespaces, shared `Common/`

```
src/
├── Common/
│   ├── Outcome.php               # WIN / LOSS / DRAW enum with score() + inverse() + fromScores()
│   └── Exception/                # RatingException base + InvalidRatingException + InvalidConfigurationException
├── Elo/
│   ├── EloRating.php             # final readonly value object wrapping a float
│   ├── KFactor.php               # interface: for(EloRating, int matchesPlayed): int
│   ├── ConstantK.php             # fixed K
│   ├── UscfK.php                 # USCF tiered (40 provisional; 32/24/16 by rating)
│   ├── CallableK.php             # closure-based, for custom strategies
│   └── EloUpdate.php             # result: newA, newB, expectedA, expectedB
├── Glicko/                       # not yet implemented — see spec §2.2
└── Rpi/                          # not yet implemented — see spec §2.3
```

The three algorithms have fundamentally different shapes (Elo pairwise, Glicko-2 per-player-per-period batch, RPI seasonal-aggregate), so they each get their own namespace with their own value-object vocabulary. There is deliberately **no** shared `Match`, `Result`, or `RatingSystem` interface — see spec §3.4 for the rejection rationale.

### Key design rules (LOAD-BEARING)

These are baked into the v2 design and reverting them undoes the whole redesign.

1. **Pure calculator, no accumulators.** v1 had a stateful `Elo` class you fed results into and queried later. That class is gone. v2 calculators take inputs and return outputs; no `addResult()` / `getCompetitors()` pattern. If you find yourself adding mutable state to a calculator, stop — the consumer holds state.

2. **Validation lives in value-object constructors, never in math methods.** `new EloRating(NAN)` throws `InvalidRatingException` at construction. `EloCalculator::rate()` then assumes its inputs are valid and never re-checks. This is the upside of `final readonly` — an invalid value object cannot exist at runtime.

3. **Every public class is `final readonly`** (when it's a value object) or `final` (when it's a calculator/strategy with no state to mutate). The one exception is `KFactor` (interface) and the exception hierarchy (extends `\RuntimeException`).

4. **`declare(strict_types=1);`** at the top of every src and test file. No exceptions.

5. **Named arguments are the recommended invocation style** in documentation and tests. Parameter names are part of the public API — don't rename them in a non-breaking release.

6. **Configuration travels via constructor**, not via global config files. v1's `config/elo.php` was loaded with a relative `include()` that broke silently when the package was installed under a consumer's `vendor/`. There is no `config/` directory in v2 and there should never be one.

7. **No persistence, no storage adapters, no Laravel/Symfony integration code.** This is a math library. If a consumer wants Eloquent models or a Redis cache, they write that themselves.

## Dev commands

```bash
# Tests (PHPUnit 11)
composer test
composer test-coverage          # HTML report in coverage/

# Static analysis (PHPStan 2.0, level max via phpstan.neon.dist)
composer phpstan

# Code style (PHP-CS-Fixer, @PSR12 + @Symfony rule sets)
composer fix                    # auto-fix
composer style-check            # dry-run, for CI

# Full CI suite (style → phpstan → tests)
composer ci
```

CI runs on GitHub Actions across PHP 8.2 / 8.3 / 8.4. Coverage target is **95%+** — pure calculators with no I/O have no excuse for uncovered branches.

## Conventions

- **Namespacing:** `ChaseCrawford\Ratings\{Common,Elo,Glicko,Rpi}\…`. v1's `ChaseCrawford\EloRating\…` and `ChaseCrawford\RatingPercentageIndex\…` namespaces are gone and stay gone.
- **Tests mirror src:** `src/Elo/EloRating.php` → `tests/Elo/EloRatingTest.php`. One test class per src class. Test namespace is `ChaseCrawford\Ratings\Tests\…`.
- **Reference vectors are the heart of the suite.** Every algorithm has a test asserting against a published worked example: Glickman's 2013 Glicko-2 paper, FIDE/USCF Elo, archived NCAA RPI seasons. Don't let calibration drift; if a test against a reference vector fails after a change to the math, the math is wrong, not the test.
- **Exception hierarchy is shallow on purpose:** catch `RatingException` for everything; catch the subclasses (`InvalidRatingException`, `InvalidConfigurationException`) when you want to differentiate. Don't add a fourth subclass unless there's a genuinely distinct failure mode.
- **PHPStan generics on collections:** `array<PeriodMatch>`, `array<string, float>`. Don't fall back to untyped `array`.

## Gotchas

- **The Elo namespace flattened from v1.** v1: `ChaseCrawford\EloRating\Elo` (doubled the algorithm name). v2: `ChaseCrawford\Ratings\Elo\EloCalculator`. Imports from old code or examples will not work — recheck against `src/Elo/`.
- **`Outcome::fromScores(int $myScore, int $theirScore)` returns from the *first player's* perspective.** `fromScores(myScore: 71, theirScore: 70)` returns `Outcome::WIN`. Mirror with `->inverse()` for the opponent's perspective.
- **`Outcome::DRAW->inverse()` is `DRAW`,** not LOSS. The mapping is symmetric: WIN↔LOSS, DRAW→DRAW.
- **Elo ratings can be negative.** `EloRating` only rejects NaN and infinity. The math doesn't break at negative values, so we don't gate them — chess `EloRating(-50.0)` is legal. If a consumer wants a floor, they enforce it themselves.
- **`UscfK` tier boundaries are `< 2100`, `< 2400`, `>= 2400` for established players,** and any player with `matchesPlayed < provisionalThreshold` (default 8) gets `K = 40` regardless of rating. Don't conflate the provisional check with the tier boundaries.
- **`CallableK` accepts a `Closure`, not just any `callable`.** This is intentional — closures bind their captured scope, which avoids the global-state pitfalls that bit v1. If a consumer hands in a string callable, type error.
- **`composer.lock` is gitignored** because this is a library, not an application. Don't commit it. Consumers resolve their own dependency versions.
- **v1 bugs that v2 makes structurally impossible** (see spec §6): argument-order swap in Elo recompute, key-name mismatch in RPI lookups, static-property clobbering between instances, relative-path config include, `starter_boundry` typo. If you're tempted to "fix" a similar pattern in v2 — check first that v2 doesn't already eliminate the bug class by construction. Don't reintroduce the shape that allowed it.

## Out of scope

- TrueSkill (deferred to a possible v2.1).
- Mutation testing, property-based testing, dedicated docs site, hosted demo — all explicitly rejected during brainstorming as overbuilt for a 3-algorithm calculator library.
- Backwards compatibility with v1. Composer semver protects existing v1 consumers via the frozen `v1.0.0` tag.
