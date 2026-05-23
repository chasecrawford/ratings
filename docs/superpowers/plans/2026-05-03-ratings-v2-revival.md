# Ratings v2.0 Revival Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the v1 ratings library with a fresh PHP 8.2+ v2.0 implementing Elo, Glicko-2, and RPI as a pure-calculator package, published to Packagist with full CI, tests, and docs.

**Architecture:** Per-algorithm namespaces (`Elo/`, `Glicko/`, `Rpi/`) under `ChaseCrawford\Ratings\` with shared `Common/` building blocks (an `Outcome` enum and a small exception hierarchy). All public types are `final readonly` value objects; calculators are pure (state in, new state out). No accumulators, no static state, no I/O.

**Tech Stack:** PHP 8.2+ · Composer · PHPUnit 11 · PHPStan 2.0 (level max) · PHP-CS-Fixer 3 · GitHub Actions · Packagist

**Repository state going in:** v1.0.0 already tagged. Working from `main` branch on cloned repo at `C:\Users\Chase\Documents\Claude\Projects\ratings`.

**Spec:** `docs/superpowers/specs/2026-05-03-ratings-v2-revival-design.md`

---

## Phase 1: Foundation (no algorithm code yet)

### Task 1: Create v2 feature branch and remove v1 sources

**Files:**
- Delete: `src/EloRating/Competitor.php`
- Delete: `src/EloRating/Elo.php`
- Delete: `src/RatingPercentageIndex/Competitor.php`
- Delete: `src/RatingPercentageIndex/RPI.php`
- Delete: `config/elo.php`
- Modify: `.gitignore`

- [ ] **Step 1: Create and switch to feature branch**

```bash
git checkout main
git pull origin main
git checkout -b feat/v2-revival
```

- [ ] **Step 2: Delete v1 source files and config directory**

```bash
git rm src/EloRating/Competitor.php
git rm src/EloRating/Elo.php
git rm src/RatingPercentageIndex/Competitor.php
git rm src/RatingPercentageIndex/RPI.php
git rm config/elo.php
rmdir src/EloRating src/RatingPercentageIndex config
```

- [ ] **Step 3: Replace .gitignore with proper PHP-library ignores**

Write `.gitignore`:
```gitignore
/vendor/
/.phpunit.cache/
/.phpunit.result.cache
/.php-cs-fixer.cache
/composer.lock
/coverage/
/.idea/
/.vscode/
.DS_Store
Thumbs.db
```

Note: `composer.lock` is gitignored because this is a *library*, not an application. Library consumers resolve their own dependency versions. (Applications would commit composer.lock; libraries do not.)

- [ ] **Step 4: Commit**

```bash
git add .gitignore
git commit -m "chore: remove v1 sources, prepare for v2 redesign

Removes the v1 implementation of Elo, RPI, and the relative-path
config file. v1 remains available at the v1.0.0 git tag and via
'composer require chasecrawford/ratings:^1.0'."
```

---

### Task 2: Rewrite composer.json for v2

**Files:**
- Modify: `composer.json` (full rewrite)

- [ ] **Step 1: Replace composer.json**

Write `composer.json`:
```json
{
    "name": "chasecrawford/ratings",
    "description": "A typed, dependency-free PHP library implementing the Elo, Glicko-2, and RPI rating systems.",
    "type": "library",
    "license": "MIT",
    "keywords": ["elo", "glicko", "glicko-2", "rpi", "rating", "ranking", "leaderboard", "matchmaking"],
    "homepage": "https://github.com/chasecrawford/ratings",
    "authors": [
        {
            "name": "Chase Crawford",
            "homepage": "https://chasecrawford.dev",
            "role": "Developer"
        }
    ],
    "require": {
        "php": "^8.2"
    },
    "require-dev": {
        "phpunit/phpunit": "^11.0",
        "phpstan/phpstan": "^2.0",
        "friendsofphp/php-cs-fixer": "^3.50"
    },
    "autoload": {
        "psr-4": {
            "ChaseCrawford\\Ratings\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "ChaseCrawford\\Ratings\\Tests\\": "tests/"
        }
    },
    "scripts": {
        "test": "phpunit",
        "test-coverage": "phpunit --coverage-text --coverage-html=coverage",
        "phpstan": "phpstan analyse --no-progress",
        "fix": "php-cs-fixer fix",
        "style-check": "php-cs-fixer fix --dry-run --diff",
        "ci": [
            "@style-check",
            "@phpstan",
            "@test"
        ]
    },
    "minimum-stability": "stable",
    "prefer-stable": true
}
```

Notes:
- Removed v1's `"version": "1.0.0"` field — Packagist derives version from git tags. Hardcoding `version` is a footgun (it overrides tags).
- Removed v1's `"time"` field — also derived from git, not declared.
- Namespace flattened from `ChaseCrawford\` to `ChaseCrawford\Ratings\` per spec §1.

- [ ] **Step 2: Install dependencies and verify autoload**

```bash
composer install
```

Expected: writes `vendor/`, generates autoload files, no errors.

- [ ] **Step 3: Verify each tool is invocable**

```bash
vendor/bin/phpunit --version
vendor/bin/phpstan --version
vendor/bin/php-cs-fixer --version
```

Expected: all three print version strings without error.

- [ ] **Step 4: Commit**

```bash
git add composer.json
git commit -m "feat(deps): rewrite composer.json for v2

- Bump PHP requirement to ^8.2
- Add dev deps: phpunit/phpunit ^11, phpstan/phpstan ^2, friendsofphp/php-cs-fixer ^3
- Flatten namespace to ChaseCrawford\\Ratings\\
- Remove hardcoded version/time fields (derived from git tags)
- Add composer scripts: test, phpstan, fix, style-check, ci"
```

---

### Task 3: Configure PHPUnit

**Files:**
- Create: `phpunit.xml.dist`

- [ ] **Step 1: Create phpunit.xml.dist**

Write `phpunit.xml.dist`:
```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true"
         cacheDirectory=".phpunit.cache"
         executionOrder="random"
         resolveDependencies="true"
         failOnRisky="true"
         failOnWarning="true"
         beStrictAboutOutputDuringTests="true"
         beStrictAboutTestsThatDoNotTestAnything="true"
         displayDetailsOnTestsThatTriggerWarnings="true"
         displayDetailsOnTestsThatTriggerErrors="true"
         displayDetailsOnTestsThatTriggerNotices="true"
         displayDetailsOnTestsThatTriggerDeprecations="true">
    <testsuites>
        <testsuite name="default">
            <directory>tests</directory>
        </testsuite>
    </testsuites>
    <source>
        <include>
            <directory>src</directory>
        </include>
    </source>
    <coverage>
        <report>
            <text outputFile="php://stdout" showOnlySummary="true"/>
        </report>
    </coverage>
</phpunit>
```

Notes:
- `executionOrder="random"` catches order-dependent tests (none should exist in this design — pure calculators have no shared state — but the constraint is free).
- `failOnRisky` and `failOnWarning` enforce strictness; tests without assertions become hard errors.
- The `<source>` block (replaces older `<coverage><include>...`) is the PHPUnit 10+ syntax.

- [ ] **Step 2: Verify config parses**

```bash
mkdir -p tests
vendor/bin/phpunit --list-suites
```

Expected: prints "Available test suite(s):" with "default" listed. No XML parse errors.

- [ ] **Step 3: Commit**

```bash
git add phpunit.xml.dist
git commit -m "chore: configure PHPUnit 11 with strict defaults"
```

---

### Task 4: Configure PHPStan

**Files:**
- Create: `phpstan.neon.dist`

- [ ] **Step 1: Create phpstan.neon.dist**

Write `phpstan.neon.dist`:
```neon
parameters:
    level: max
    paths:
        - src
        - tests
    tmpDir: .phpstan
    treatPhpDocTypesAsCertain: false
```

Notes:
- `level: max` is an alias that always points to PHPStan's highest level (currently 10 in PHPStan 2.0). Using the alias means the project tightens automatically as PHPStan adds new levels.
- `treatPhpDocTypesAsCertain: false` makes PHPStan re-verify types declared only via PHPDoc — useful here because we use `@param GameResult[] $games` style annotations heavily.

- [ ] **Step 2: Run PHPStan**

```bash
vendor/bin/phpstan analyse --no-progress
```

Expected: "[OK] No errors" (src and tests are empty so far).

- [ ] **Step 3: Add `.phpstan/` to .gitignore**

Edit `.gitignore`, add line:
```
/.phpstan/
```

- [ ] **Step 4: Commit**

```bash
git add phpstan.neon.dist .gitignore
git commit -m "chore: configure PHPStan at level max"
```

---

### Task 5: Configure PHP-CS-Fixer

**Files:**
- Create: `.php-cs-fixer.dist.php`

- [ ] **Step 1: Create .php-cs-fixer.dist.php**

Write `.php-cs-fixer.dist.php`:
```php
<?php

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->in([__DIR__ . '/src', __DIR__ . '/tests'])
    ->name('*.php');

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12' => true,
        '@Symfony' => true,
        '@PHP82Migration' => true,
        'declare_strict_types' => true,
        'array_syntax' => ['syntax' => 'short'],
        'global_namespace_import' => ['import_classes' => true, 'import_functions' => false],
        'phpdoc_align' => ['align' => 'left'],
        'no_superfluous_phpdoc_tags' => ['allow_mixed' => true],
        'yoda_style' => false,
    ])
    ->setFinder($finder);
```

- [ ] **Step 2: Run a dry-run check**

```bash
vendor/bin/php-cs-fixer fix --dry-run --diff
```

Expected: no files to fix (src and tests empty). No errors.

- [ ] **Step 3: Commit**

```bash
git add .php-cs-fixer.dist.php
git commit -m "chore: configure PHP-CS-Fixer with PSR-12 + Symfony + PHP 8.2 migration"
```

---

### Task 6: Add GitHub Actions CI workflow

**Files:**
- Create: `.github/workflows/ci.yml`

- [ ] **Step 1: Create the CI workflow**

Write `.github/workflows/ci.yml`:
```yaml
name: CI

on:
  push:
    branches: [main]
  pull_request:
    branches: [main]

jobs:
  style:
    name: "Style check"
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: "8.2"
          coverage: none
      - uses: ramsey/composer-install@v3
      - run: composer style-check

  phpstan:
    name: "PHPStan"
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: "8.2"
          coverage: none
      - uses: ramsey/composer-install@v3
      - run: composer phpstan

  test:
    name: "PHPUnit (PHP ${{ matrix.php }} / ${{ matrix.deps }})"
    runs-on: ubuntu-latest
    strategy:
      fail-fast: false
      matrix:
        php: ["8.2", "8.3", "8.4"]
        deps: ["lowest", "stable"]
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php }}
          coverage: xdebug
      - uses: ramsey/composer-install@v3
        with:
          dependency-versions: ${{ matrix.deps == 'lowest' && 'lowest' || 'highest' }}
      - run: vendor/bin/phpunit --coverage-clover=coverage.xml
      - name: Upload coverage to Codecov
        if: matrix.php == '8.3' && matrix.deps == 'stable'
        uses: codecov/codecov-action@v4
        with:
          files: coverage.xml
          fail_ci_if_error: false
```

Notes:
- 6 PHPUnit jobs (3 PHP versions × 2 dependency strategies) match the spec §4.6 matrix.
- Codecov upload only on PHP 8.3 stable to avoid duplicate uploads.
- `fail_ci_if_error: false` because Codecov outages shouldn't break our build.
- `ramsey/composer-install` action handles the `prefer-lowest`/`prefer-stable` dance via `dependency-versions`.

- [ ] **Step 2: Verify YAML parses (locally, optional)**

If `yamllint` is installed: `yamllint .github/workflows/ci.yml`. Otherwise skip; GitHub will validate on push.

- [ ] **Step 3: Commit**

```bash
git add .github/workflows/ci.yml
git commit -m "ci: add GitHub Actions workflow (style + phpstan + phpunit matrix)"
```

---

## Phase 2: Common building blocks (TDD)

### Task 7: `Outcome` enum

**Files:**
- Create: `src/Common/Outcome.php`
- Create: `tests/Common/OutcomeTest.php`

- [ ] **Step 1: Write failing tests**

Write `tests/Common/OutcomeTest.php`:
```php
<?php

declare(strict_types=1);

namespace ChaseCrawford\Ratings\Tests\Common;

use ChaseCrawford\Ratings\Common\Outcome;
use PHPUnit\Framework\TestCase;

final class OutcomeTest extends TestCase
{
    public function test_from_scores_returns_win_when_mine_is_higher(): void
    {
        self::assertSame(Outcome::WIN, Outcome::fromScores(5, 3));
    }

    public function test_from_scores_returns_loss_when_mine_is_lower(): void
    {
        self::assertSame(Outcome::LOSS, Outcome::fromScores(3, 5));
    }

    public function test_from_scores_returns_draw_when_equal(): void
    {
        self::assertSame(Outcome::DRAW, Outcome::fromScores(3, 3));
    }

    public function test_score_returns_one_for_win(): void
    {
        self::assertSame(1.0, Outcome::WIN->score());
    }

    public function test_score_returns_half_for_draw(): void
    {
        self::assertSame(0.5, Outcome::DRAW->score());
    }

    public function test_score_returns_zero_for_loss(): void
    {
        self::assertSame(0.0, Outcome::LOSS->score());
    }

    public function test_inverse_swaps_win_and_loss(): void
    {
        self::assertSame(Outcome::LOSS, Outcome::WIN->inverse());
        self::assertSame(Outcome::WIN, Outcome::LOSS->inverse());
    }

    public function test_inverse_of_draw_is_draw(): void
    {
        self::assertSame(Outcome::DRAW, Outcome::DRAW->inverse());
    }
}
```

- [ ] **Step 2: Run tests, confirm they fail**

```bash
vendor/bin/phpunit tests/Common/OutcomeTest.php
```

Expected: FAIL — `Class "ChaseCrawford\Ratings\Common\Outcome" not found`.

- [ ] **Step 3: Implement Outcome**

Write `src/Common/Outcome.php`:
```php
<?php

declare(strict_types=1);

namespace ChaseCrawford\Ratings\Common;

enum Outcome: string
{
    case WIN = 'win';
    case LOSS = 'loss';
    case DRAW = 'draw';

    public static function fromScores(int $mine, int $theirs): self
    {
        return match (true) {
            $mine > $theirs => self::WIN,
            $mine < $theirs => self::LOSS,
            default => self::DRAW,
        };
    }

    public function score(): float
    {
        return match ($this) {
            self::WIN => 1.0,
            self::DRAW => 0.5,
            self::LOSS => 0.0,
        };
    }

    public function inverse(): self
    {
        return match ($this) {
            self::WIN => self::LOSS,
            self::LOSS => self::WIN,
            self::DRAW => self::DRAW,
        };
    }
}
```

- [ ] **Step 4: Run tests, confirm they pass**

```bash
vendor/bin/phpunit tests/Common/OutcomeTest.php
```

Expected: 8 tests, 10 assertions, all PASS.

- [ ] **Step 5: Run PHPStan and style check**

```bash
vendor/bin/phpstan analyse src/Common tests/Common --no-progress
vendor/bin/php-cs-fixer fix src/Common --dry-run --diff
vendor/bin/php-cs-fixer fix tests/Common --dry-run --diff
```

Expected: PHPStan "No errors". CS-Fixer no changes needed. If CS-Fixer complains, run without `--dry-run` to apply.

- [ ] **Step 6: Commit**

```bash
git add src/Common/Outcome.php tests/Common/OutcomeTest.php
git commit -m "feat(common): add Outcome enum with fromScores, score, inverse helpers"
```

---

### Task 8: Exception hierarchy

**Files:**
- Create: `src/Common/Exception/RatingException.php`
- Create: `src/Common/Exception/InvalidRatingException.php`
- Create: `src/Common/Exception/InvalidConfigurationException.php`
- Create: `tests/Common/Exception/ExceptionHierarchyTest.php`

- [ ] **Step 1: Write failing test**

Write `tests/Common/Exception/ExceptionHierarchyTest.php`:
```php
<?php

declare(strict_types=1);

namespace ChaseCrawford\Ratings\Tests\Common\Exception;

use ChaseCrawford\Ratings\Common\Exception\InvalidConfigurationException;
use ChaseCrawford\Ratings\Common\Exception\InvalidRatingException;
use ChaseCrawford\Ratings\Common\Exception\RatingException;
use PHPUnit\Framework\TestCase;

final class ExceptionHierarchyTest extends TestCase
{
    public function test_invalid_rating_extends_rating_exception(): void
    {
        self::assertInstanceOf(RatingException::class, new InvalidRatingException('x'));
    }

    public function test_invalid_configuration_extends_rating_exception(): void
    {
        self::assertInstanceOf(RatingException::class, new InvalidConfigurationException('x'));
    }

    public function test_rating_exception_extends_runtime_exception(): void
    {
        self::assertInstanceOf(\RuntimeException::class, new RatingException('x'));
    }
}
```

- [ ] **Step 2: Run, confirm fails**

```bash
vendor/bin/phpunit tests/Common/Exception/ExceptionHierarchyTest.php
```

Expected: FAIL — class not found.

- [ ] **Step 3: Implement the three exception classes**

Write `src/Common/Exception/RatingException.php`:
```php
<?php

declare(strict_types=1);

namespace ChaseCrawford\Ratings\Common\Exception;

class RatingException extends \RuntimeException
{
}
```

Write `src/Common/Exception/InvalidRatingException.php`:
```php
<?php

declare(strict_types=1);

namespace ChaseCrawford\Ratings\Common\Exception;

class InvalidRatingException extends RatingException
{
}
```

Write `src/Common/Exception/InvalidConfigurationException.php`:
```php
<?php

declare(strict_types=1);

namespace ChaseCrawford\Ratings\Common\Exception;

class InvalidConfigurationException extends RatingException
{
}
```

- [ ] **Step 4: Run tests, confirm pass**

```bash
vendor/bin/phpunit tests/Common/Exception/ExceptionHierarchyTest.php
```

Expected: 3 tests, 3 assertions, PASS.

- [ ] **Step 5: Commit**

```bash
git add src/Common/Exception tests/Common/Exception
git commit -m "feat(common): add exception hierarchy (RatingException base, two subclasses)"
```

---

## Phase 3: Elo (TDD)

### Task 9: `EloRating` value object

**Files:**
- Create: `src/Elo/EloRating.php`
- Create: `tests/Elo/EloRatingTest.php`

- [ ] **Step 1: Write failing test**

Write `tests/Elo/EloRatingTest.php`:
```php
<?php

declare(strict_types=1);

namespace ChaseCrawford\Ratings\Tests\Elo;

use ChaseCrawford\Ratings\Common\Exception\InvalidRatingException;
use ChaseCrawford\Ratings\Elo\EloRating;
use PHPUnit\Framework\TestCase;

final class EloRatingTest extends TestCase
{
    public function test_constructs_with_valid_value(): void
    {
        $rating = new EloRating(1500.0);
        self::assertSame(1500.0, $rating->value);
    }

    public function test_rejects_nan(): void
    {
        $this->expectException(InvalidRatingException::class);
        new EloRating(NAN);
    }

    public function test_rejects_infinity(): void
    {
        $this->expectException(InvalidRatingException::class);
        new EloRating(INF);
    }

    public function test_accepts_negative_rating(): void
    {
        // Elo ratings can theoretically go negative; we don't forbid it.
        $rating = new EloRating(-50.0);
        self::assertSame(-50.0, $rating->value);
    }
}
```

- [ ] **Step 2: Run, confirm fail**

```bash
vendor/bin/phpunit tests/Elo/EloRatingTest.php
```

Expected: FAIL — class not found.

- [ ] **Step 3: Implement EloRating**

Write `src/Elo/EloRating.php`:
```php
<?php

declare(strict_types=1);

namespace ChaseCrawford\Ratings\Elo;

use ChaseCrawford\Ratings\Common\Exception\InvalidRatingException;

final readonly class EloRating
{
    public function __construct(public float $value)
    {
        if (is_nan($value)) {
            throw new InvalidRatingException('Elo rating cannot be NaN.');
        }
        if (is_infinite($value)) {
            throw new InvalidRatingException('Elo rating cannot be infinite.');
        }
    }
}
```

- [ ] **Step 4: Run tests, confirm pass**

```bash
vendor/bin/phpunit tests/Elo/EloRatingTest.php
```

Expected: 4 tests, 3 assertions (negative test only catches by absence of exception), PASS.

- [ ] **Step 5: Commit**

```bash
git add src/Elo/EloRating.php tests/Elo/EloRatingTest.php
git commit -m "feat(elo): add EloRating value object with NaN/infinity validation"
```

---

### Task 10: `KFactor` interface and `ConstantK`

**Files:**
- Create: `src/Elo/KFactor.php`
- Create: `src/Elo/ConstantK.php`
- Create: `tests/Elo/ConstantKTest.php`

- [ ] **Step 1: Write failing test**

Write `tests/Elo/ConstantKTest.php`:
```php
<?php

declare(strict_types=1);

namespace ChaseCrawford\Ratings\Tests\Elo;

use ChaseCrawford\Ratings\Elo\ConstantK;
use ChaseCrawford\Ratings\Elo\EloRating;
use ChaseCrawford\Ratings\Elo\KFactor;
use PHPUnit\Framework\TestCase;

final class ConstantKTest extends TestCase
{
    public function test_implements_kfactor_interface(): void
    {
        self::assertInstanceOf(KFactor::class, new ConstantK(15));
    }

    public function test_returns_constant_value_regardless_of_inputs(): void
    {
        $k = new ConstantK(20);
        self::assertSame(20, $k->for(new EloRating(1000), 0));
        self::assertSame(20, $k->for(new EloRating(2400), 100));
    }

    public function test_default_k_is_15(): void
    {
        $k = new ConstantK();
        self::assertSame(15, $k->k);
    }
}
```

- [ ] **Step 2: Run, confirm fail**

```bash
vendor/bin/phpunit tests/Elo/ConstantKTest.php
```

Expected: FAIL — class not found.

- [ ] **Step 3: Implement KFactor interface**

Write `src/Elo/KFactor.php`:
```php
<?php

declare(strict_types=1);

namespace ChaseCrawford\Ratings\Elo;

interface KFactor
{
    public function for(EloRating $rating, int $matchesPlayed): int;
}
```

- [ ] **Step 4: Implement ConstantK**

Write `src/Elo/ConstantK.php`:
```php
<?php

declare(strict_types=1);

namespace ChaseCrawford\Ratings\Elo;

final readonly class ConstantK implements KFactor
{
    public function __construct(public int $k = 15)
    {
    }

    public function for(EloRating $rating, int $matchesPlayed): int
    {
        return $this->k;
    }
}
```

- [ ] **Step 5: Run tests, confirm pass**

```bash
vendor/bin/phpunit tests/Elo/ConstantKTest.php
```

Expected: 3 tests, 4 assertions, PASS.

- [ ] **Step 6: Commit**

```bash
git add src/Elo/KFactor.php src/Elo/ConstantK.php tests/Elo/ConstantKTest.php
git commit -m "feat(elo): add KFactor interface and ConstantK strategy"
```

---

### Task 11: `UscfK` strategy

**Files:**
- Create: `src/Elo/UscfK.php`
- Create: `tests/Elo/UscfKTest.php`

The USCF K-factor formula (from the US Chess Federation rating system):
- For "established" players (≥ 8 games):
  - K = 800 / (Ne + m), where Ne is "effective number of games" and m is the number of games in the current event
  - For simplicity in this library's pure-calculator API (which doesn't track event-level batches), we use the simpler tier approximation:
    - Rating < 2100: K = 32
    - Rating 2100–2399: K = 24
    - Rating ≥ 2400: K = 16
- For "provisional" players (< 8 games): K = 40

- [ ] **Step 1: Write failing test**

Write `tests/Elo/UscfKTest.php`:
```php
<?php

declare(strict_types=1);

namespace ChaseCrawford\Ratings\Tests\Elo;

use ChaseCrawford\Ratings\Elo\EloRating;
use ChaseCrawford\Ratings\Elo\UscfK;
use PHPUnit\Framework\TestCase;

final class UscfKTest extends TestCase
{
    public function test_provisional_player_gets_k_40(): void
    {
        $k = new UscfK();
        self::assertSame(40, $k->for(new EloRating(1500), matchesPlayed: 0));
        self::assertSame(40, $k->for(new EloRating(1500), matchesPlayed: 7));
    }

    public function test_established_low_rated_gets_k_32(): void
    {
        $k = new UscfK();
        self::assertSame(32, $k->for(new EloRating(1500), matchesPlayed: 8));
        self::assertSame(32, $k->for(new EloRating(2099), matchesPlayed: 100));
    }

    public function test_established_mid_rated_gets_k_24(): void
    {
        $k = new UscfK();
        self::assertSame(24, $k->for(new EloRating(2100), matchesPlayed: 50));
        self::assertSame(24, $k->for(new EloRating(2399), matchesPlayed: 50));
    }

    public function test_established_high_rated_gets_k_16(): void
    {
        $k = new UscfK();
        self::assertSame(16, $k->for(new EloRating(2400), matchesPlayed: 50));
        self::assertSame(16, $k->for(new EloRating(2800), matchesPlayed: 50));
    }
}
```

- [ ] **Step 2: Run, confirm fail**

```bash
vendor/bin/phpunit tests/Elo/UscfKTest.php
```

Expected: FAIL — class not found.

- [ ] **Step 3: Implement UscfK**

Write `src/Elo/UscfK.php`:
```php
<?php

declare(strict_types=1);

namespace ChaseCrawford\Ratings\Elo;

/**
 * USCF tiered K-factor approximation.
 *
 * Provisional players (< 8 rated games): K = 40.
 * Established players: tier by current rating (32 / 24 / 16).
 */
final readonly class UscfK implements KFactor
{
    public function __construct(public int $provisionalThreshold = 8)
    {
    }

    public function for(EloRating $rating, int $matchesPlayed): int
    {
        if ($matchesPlayed < $this->provisionalThreshold) {
            return 40;
        }

        return match (true) {
            $rating->value < 2100 => 32,
            $rating->value < 2400 => 24,
            default => 16,
        };
    }
}
```

- [ ] **Step 4: Run tests, confirm pass**

```bash
vendor/bin/phpunit tests/Elo/UscfKTest.php
```

Expected: 4 tests, 8 assertions, PASS.

- [ ] **Step 5: Commit**

```bash
git add src/Elo/UscfK.php tests/Elo/UscfKTest.php
git commit -m "feat(elo): add UscfK tiered K-factor strategy"
```

---

### Task 12: `CallableK` strategy

**Files:**
- Create: `src/Elo/CallableK.php`
- Create: `tests/Elo/CallableKTest.php`

- [ ] **Step 1: Write failing test**

Write `tests/Elo/CallableKTest.php`:
```php
<?php

declare(strict_types=1);

namespace ChaseCrawford\Ratings\Tests\Elo;

use ChaseCrawford\Ratings\Elo\CallableK;
use ChaseCrawford\Ratings\Elo\EloRating;
use PHPUnit\Framework\TestCase;

final class CallableKTest extends TestCase
{
    public function test_invokes_provided_closure(): void
    {
        $k = new CallableK(fn(EloRating $r, int $m): int => $r->value > 2000 ? 10 : 20);

        self::assertSame(20, $k->for(new EloRating(1500), 50));
        self::assertSame(10, $k->for(new EloRating(2100), 50));
    }

    public function test_passes_matches_played_to_closure(): void
    {
        $captured = null;
        $k = new CallableK(function (EloRating $r, int $m) use (&$captured): int {
            $captured = $m;
            return 30;
        });

        $k->for(new EloRating(1500), 42);
        self::assertSame(42, $captured);
    }
}
```

- [ ] **Step 2: Run, confirm fail**

```bash
vendor/bin/phpunit tests/Elo/CallableKTest.php
```

Expected: FAIL.

- [ ] **Step 3: Implement CallableK**

Write `src/Elo/CallableK.php`:
```php
<?php

declare(strict_types=1);

namespace ChaseCrawford\Ratings\Elo;

final readonly class CallableK implements KFactor
{
    /** @var \Closure(EloRating, int): int */
    private \Closure $fn;

    /**
     * @param \Closure(EloRating, int): int $fn
     *        Receives (rating, matchesPlayed); must return an int K-factor.
     */
    public function __construct(\Closure $fn)
    {
        $this->fn = $fn;
    }

    public function for(EloRating $rating, int $matchesPlayed): int
    {
        return ($this->fn)($rating, $matchesPlayed);
    }
}
```

- [ ] **Step 4: Run tests, confirm pass**

```bash
vendor/bin/phpunit tests/Elo/CallableKTest.php
```

Expected: 2 tests, 3 assertions, PASS.

- [ ] **Step 5: Commit**

```bash
git add src/Elo/CallableK.php tests/Elo/CallableKTest.php
git commit -m "feat(elo): add CallableK strategy for arbitrary K-factor functions"
```

---

### Task 13: `EloUpdate` value object

**Files:**
- Create: `src/Elo/EloUpdate.php`
- Create: `tests/Elo/EloUpdateTest.php`

- [ ] **Step 1: Write failing test**

Write `tests/Elo/EloUpdateTest.php`:
```php
<?php

declare(strict_types=1);

namespace ChaseCrawford\Ratings\Tests\Elo;

use ChaseCrawford\Ratings\Elo\EloRating;
use ChaseCrawford\Ratings\Elo\EloUpdate;
use PHPUnit\Framework\TestCase;

final class EloUpdateTest extends TestCase
{
    public function test_holds_new_ratings_and_expected_scores(): void
    {
        $update = new EloUpdate(
            newA: new EloRating(1507.2),
            newB: new EloRating(1392.8),
            expectedA: 0.64,
            expectedB: 0.36,
        );

        self::assertSame(1507.2, $update->newA->value);
        self::assertSame(1392.8, $update->newB->value);
        self::assertSame(0.64, $update->expectedA);
        self::assertSame(0.36, $update->expectedB);
    }
}
```

- [ ] **Step 2: Run, confirm fail**

```bash
vendor/bin/phpunit tests/Elo/EloUpdateTest.php
```

Expected: FAIL.

- [ ] **Step 3: Implement EloUpdate**

Write `src/Elo/EloUpdate.php`:
```php
<?php

declare(strict_types=1);

namespace ChaseCrawford\Ratings\Elo;

final readonly class EloUpdate
{
    public function __construct(
        public EloRating $newA,
        public EloRating $newB,
        public float $expectedA,
        public float $expectedB,
    ) {
    }
}
```

- [ ] **Step 4: Run, confirm pass**

```bash
vendor/bin/phpunit tests/Elo/EloUpdateTest.php
```

Expected: 1 test, 4 assertions, PASS.

- [ ] **Step 5: Commit**

```bash
git add src/Elo/EloUpdate.php tests/Elo/EloUpdateTest.php
git commit -m "feat(elo): add EloUpdate result value object"
```

---

### Task 14: `EloCalculator` (with reference vector)

**Files:**
- Create: `src/Elo/EloCalculator.php`
- Create: `tests/Elo/EloCalculatorTest.php`

The Elo math:
- Expected score for A: `Ea = 1 / (1 + 10^((Rb - Ra) / 400))`
- Score from outcome: WIN=1.0, DRAW=0.5, LOSS=0.0
- New rating: `Ra' = Ra + K * (Sa - Ea)`
- B is symmetric: `Eb = 1 - Ea`, `Sb = 1 - Sa` (DRAW gives both 0.5)

Reference vector (hand-computed):
- A=1500, B=1400, A wins, K=20 (constant)
- Ea = 1 / (1 + 10^(-100/400)) = 1 / (1 + 10^-0.25) = 1 / (1 + 0.5623413251903491) = 0.6400649998028851
- Eb = 0.3599350001971149
- Sa = 1.0, Sb = 0.0
- newA = 1500 + 20 * (1.0 - 0.6400649998028851) = 1500 + 7.198700003942298 = 1507.198700003942
- newB = 1400 + 20 * (0.0 - 0.3599350001971149) = 1400 - 7.198700003942298 = 1392.801299996058

- [ ] **Step 1: Write failing tests**

Write `tests/Elo/EloCalculatorTest.php`:
```php
<?php

declare(strict_types=1);

namespace ChaseCrawford\Ratings\Tests\Elo;

use ChaseCrawford\Ratings\Common\Outcome;
use ChaseCrawford\Ratings\Elo\CallableK;
use ChaseCrawford\Ratings\Elo\ConstantK;
use ChaseCrawford\Ratings\Elo\EloCalculator;
use ChaseCrawford\Ratings\Elo\EloRating;
use PHPUnit\Framework\TestCase;

final class EloCalculatorTest extends TestCase
{
    private const TOL = 1e-9;

    public function test_reference_vector_underdog_wins_k20(): void
    {
        // A=1500, B=1400, A wins, K=20.
        // Hand calc: Ea = 1/(1+10^-0.25) ≈ 0.6400649998
        // newA = 1500 + 20*(1 - 0.6400649998) ≈ 1507.198700004
        // newB = 1400 - 20*(0.6400649998) ≈ 1392.801299996
        $elo = new EloCalculator(new ConstantK(20));
        $update = $elo->rate(
            a: new EloRating(1500),
            b: new EloRating(1400),
            outcomeForA: Outcome::WIN,
        );

        self::assertEqualsWithDelta(0.6400649998028851, $update->expectedA, self::TOL);
        self::assertEqualsWithDelta(0.3599350001971149, $update->expectedB, self::TOL);
        self::assertEqualsWithDelta(1507.1987000039422, $update->newA->value, self::TOL);
        self::assertEqualsWithDelta(1392.8012999960577, $update->newB->value, self::TOL);
    }

    public function test_draw_between_equal_players_does_not_change_ratings(): void
    {
        $elo = new EloCalculator(new ConstantK(32));
        $update = $elo->rate(
            a: new EloRating(1500),
            b: new EloRating(1500),
            outcomeForA: Outcome::DRAW,
        );

        self::assertEqualsWithDelta(0.5, $update->expectedA, self::TOL);
        self::assertEqualsWithDelta(1500.0, $update->newA->value, self::TOL);
        self::assertEqualsWithDelta(1500.0, $update->newB->value, self::TOL);
    }

    public function test_zero_sum_property_holds(): void
    {
        // For any ConstantK calculator, the total rating should be conserved.
        $elo = new EloCalculator(new ConstantK(24));
        $update = $elo->rate(
            a: new EloRating(1742),
            b: new EloRating(1188),
            outcomeForA: Outcome::LOSS,
        );

        $deltaA = $update->newA->value - 1742;
        $deltaB = $update->newB->value - 1188;
        self::assertEqualsWithDelta(0.0, $deltaA + $deltaB, self::TOL);
    }

    public function test_expected_scores_sum_to_one(): void
    {
        $elo = new EloCalculator(new ConstantK(20));
        $update = $elo->rate(
            a: new EloRating(1234),
            b: new EloRating(1876),
            outcomeForA: Outcome::WIN,
        );

        self::assertEqualsWithDelta(1.0, $update->expectedA + $update->expectedB, self::TOL);
    }

    public function test_uses_per_player_k_factors_from_strategy(): void
    {
        // CallableK that returns different K for each player.
        $elo = new EloCalculator(new CallableK(
            fn(EloRating $r, int $m): int => $r->value === 1500.0 ? 40 : 10,
        ));

        $update = $elo->rate(
            a: new EloRating(1500),  // K=40 for A
            b: new EloRating(1400),  // K=10 for B
            outcomeForA: Outcome::WIN,
        );

        // |delta A| should be 4x |delta B| because A's K is 4x B's K.
        $deltaA = $update->newA->value - 1500;
        $deltaB = 1400 - $update->newB->value;
        self::assertEqualsWithDelta($deltaA, 4 * $deltaB, self::TOL);
    }

    public function test_default_calculator_uses_k15(): void
    {
        $elo = new EloCalculator();   // default ConstantK(15)
        $update = $elo->rate(
            a: new EloRating(1500),
            b: new EloRating(1500),
            outcomeForA: Outcome::WIN,
        );

        // delta = 15 * (1 - 0.5) = 7.5
        self::assertEqualsWithDelta(1507.5, $update->newA->value, self::TOL);
    }
}
```

- [ ] **Step 2: Run, confirm fail**

```bash
vendor/bin/phpunit tests/Elo/EloCalculatorTest.php
```

Expected: FAIL — class not found.

- [ ] **Step 3: Implement EloCalculator**

Write `src/Elo/EloCalculator.php`:
```php
<?php

declare(strict_types=1);

namespace ChaseCrawford\Ratings\Elo;

use ChaseCrawford\Ratings\Common\Outcome;

final class EloCalculator
{
    public function __construct(
        private readonly KFactor $kFactor = new ConstantK(15),
    ) {
    }

    public function rate(
        EloRating $a,
        EloRating $b,
        Outcome $outcomeForA,
        int $matchesPlayedA = 0,
        int $matchesPlayedB = 0,
    ): EloUpdate {
        $expectedA = $this->expected($a->value, $b->value);
        $expectedB = 1.0 - $expectedA;

        $scoreA = $outcomeForA->score();
        $scoreB = $outcomeForA->inverse()->score();

        $kA = $this->kFactor->for($a, $matchesPlayedA);
        $kB = $this->kFactor->for($b, $matchesPlayedB);

        return new EloUpdate(
            newA: new EloRating($a->value + $kA * ($scoreA - $expectedA)),
            newB: new EloRating($b->value + $kB * ($scoreB - $expectedB)),
            expectedA: $expectedA,
            expectedB: $expectedB,
        );
    }

    private function expected(float $ratingA, float $ratingB): float
    {
        return 1.0 / (1.0 + 10.0 ** (($ratingB - $ratingA) / 400.0));
    }
}
```

- [ ] **Step 4: Run tests, confirm pass**

```bash
vendor/bin/phpunit tests/Elo/EloCalculatorTest.php
```

Expected: 6 tests, all PASS.

- [ ] **Step 5: Run full Elo suite + PHPStan**

```bash
vendor/bin/phpunit tests/Elo
vendor/bin/phpstan analyse src/Elo tests/Elo --no-progress
```

Expected: All tests pass; PHPStan: no errors.

- [ ] **Step 6: Commit**

```bash
git add src/Elo/EloCalculator.php tests/Elo/EloCalculatorTest.php
git commit -m "feat(elo): add EloCalculator with reference-vector and invariant tests"
```

---

## Phase 4: Glicko-2 (TDD)

### Task 15: `GlickoRating` value object

**Files:**
- Create: `src/Glicko/GlickoRating.php`
- Create: `tests/Glicko/GlickoRatingTest.php`

- [ ] **Step 1: Write failing test**

Write `tests/Glicko/GlickoRatingTest.php`:
```php
<?php

declare(strict_types=1);

namespace ChaseCrawford\Ratings\Tests\Glicko;

use ChaseCrawford\Ratings\Common\Exception\InvalidRatingException;
use ChaseCrawford\Ratings\Glicko\GlickoRating;
use PHPUnit\Framework\TestCase;

final class GlickoRatingTest extends TestCase
{
    public function test_default_construction_uses_glicko_defaults(): void
    {
        $r = new GlickoRating();
        self::assertSame(1500.0, $r->rating);
        self::assertSame(350.0, $r->deviation);
        self::assertSame(0.06, $r->volatility);
    }

    public function test_constructs_with_explicit_values(): void
    {
        $r = new GlickoRating(rating: 1742, deviation: 80, volatility: 0.04);
        self::assertSame(1742.0, $r->rating);
        self::assertSame(80.0, $r->deviation);
        self::assertSame(0.04, $r->volatility);
    }

    public function test_rejects_nan_rating(): void
    {
        $this->expectException(InvalidRatingException::class);
        new GlickoRating(rating: NAN);
    }

    public function test_rejects_zero_or_negative_deviation(): void
    {
        $this->expectException(InvalidRatingException::class);
        new GlickoRating(deviation: 0);
    }

    public function test_rejects_negative_deviation_explicit(): void
    {
        $this->expectException(InvalidRatingException::class);
        new GlickoRating(deviation: -10);
    }

    public function test_rejects_zero_or_negative_volatility(): void
    {
        $this->expectException(InvalidRatingException::class);
        new GlickoRating(volatility: 0);
    }

    public function test_rejects_negative_volatility_explicit(): void
    {
        $this->expectException(InvalidRatingException::class);
        new GlickoRating(volatility: -0.05);
    }
}
```

- [ ] **Step 2: Run, confirm fail**

```bash
vendor/bin/phpunit tests/Glicko/GlickoRatingTest.php
```

Expected: FAIL.

- [ ] **Step 3: Implement GlickoRating**

Write `src/Glicko/GlickoRating.php`:
```php
<?php

declare(strict_types=1);

namespace ChaseCrawford\Ratings\Glicko;

use ChaseCrawford\Ratings\Common\Exception\InvalidRatingException;

final readonly class GlickoRating
{
    public function __construct(
        public float $rating = 1500.0,
        public float $deviation = 350.0,
        public float $volatility = 0.06,
    ) {
        if (is_nan($rating) || is_infinite($rating)) {
            throw new InvalidRatingException('Glicko rating must be a finite number.');
        }
        if (is_nan($deviation) || is_infinite($deviation) || $deviation <= 0.0) {
            throw new InvalidRatingException('Glicko deviation must be a finite positive number.');
        }
        if (is_nan($volatility) || is_infinite($volatility) || $volatility <= 0.0) {
            throw new InvalidRatingException('Glicko volatility must be a finite positive number.');
        }
    }
}
```

- [ ] **Step 4: Run tests, confirm pass**

```bash
vendor/bin/phpunit tests/Glicko/GlickoRatingTest.php
```

Expected: 7 tests PASS.

- [ ] **Step 5: Commit**

```bash
git add src/Glicko/GlickoRating.php tests/Glicko/GlickoRatingTest.php
git commit -m "feat(glicko): add GlickoRating value object with full validation"
```

---

### Task 16: `PeriodMatch` value object

**Files:**
- Create: `src/Glicko/PeriodMatch.php`
- Create: `tests/Glicko/PeriodMatchTest.php`

- [ ] **Step 1: Write failing test**

Write `tests/Glicko/PeriodMatchTest.php`:
```php
<?php

declare(strict_types=1);

namespace ChaseCrawford\Ratings\Tests\Glicko;

use ChaseCrawford\Ratings\Common\Outcome;
use ChaseCrawford\Ratings\Glicko\GlickoRating;
use ChaseCrawford\Ratings\Glicko\PeriodMatch;
use PHPUnit\Framework\TestCase;

final class PeriodMatchTest extends TestCase
{
    public function test_holds_opponent_and_outcome(): void
    {
        $opponent = new GlickoRating(1400, 30, 0.06);
        $match = new PeriodMatch($opponent, Outcome::WIN);

        self::assertSame($opponent, $match->opponent);
        self::assertSame(Outcome::WIN, $match->outcome);
    }
}
```

- [ ] **Step 2: Run, confirm fail**

```bash
vendor/bin/phpunit tests/Glicko/PeriodMatchTest.php
```

Expected: FAIL.

- [ ] **Step 3: Implement PeriodMatch**

Write `src/Glicko/PeriodMatch.php`:
```php
<?php

declare(strict_types=1);

namespace ChaseCrawford\Ratings\Glicko;

use ChaseCrawford\Ratings\Common\Outcome;

final readonly class PeriodMatch
{
    public function __construct(
        public GlickoRating $opponent,
        public Outcome $outcome,
    ) {
    }
}
```

- [ ] **Step 4: Run, confirm pass**

```bash
vendor/bin/phpunit tests/Glicko/PeriodMatchTest.php
```

Expected: 1 test, 2 assertions, PASS.

- [ ] **Step 5: Commit**

```bash
git add src/Glicko/PeriodMatch.php tests/Glicko/PeriodMatchTest.php
git commit -m "feat(glicko): add PeriodMatch value object"
```

---

### Task 17: `Glicko2Calculator` (with Glickman 2013 reference vector)

**Files:**
- Create: `src/Glicko/Glicko2Calculator.php`
- Create: `tests/Glicko/Glicko2CalculatorTest.php`

The Glicko-2 algorithm (per Glickman, "Example of the Glicko-2 system", 2013):

1. Convert rating to Glicko-2 scale: `μ = (r - 1500) / 173.7178`, `φ = RD / 173.7178`
2. For each opponent j in the period (also converted): compute
   - `g(φⱼ) = 1 / sqrt(1 + 3·φⱼ² / π²)`
   - `E(μ, μⱼ, φⱼ) = 1 / (1 + exp(-g(φⱼ)·(μ - μⱼ)))`
3. Variance: `v = 1 / Σⱼ g(φⱼ)² · E · (1 - E)`
4. Improvement: `Δ = v · Σⱼ g(φⱼ) · (sⱼ - E)` where sⱼ ∈ {1, 0.5, 0}
5. New volatility σ' via iterative root-finding (Illinois algorithm) on:
   - `f(x) = (e^x · (Δ² - φ² - v - e^x)) / (2·(φ² + v + e^x)²) - (x - ln(σ²)) / τ²`
6. New deviation: `φ* = sqrt(φ² + σ'²)`, then `φ' = 1 / sqrt(1/φ*² + 1/v)`
7. New rating: `μ' = μ + φ'² · Σⱼ g(φⱼ) · (sⱼ - E)`
8. Convert back: `r' = 173.7178·μ' + 1500`, `RD' = 173.7178·φ'`

If no matches in period: only step 6 applies (with φ' = sqrt(φ² + σ'²) using current σ; rating unchanged).

Glickman's worked example:
- Player: r=1500, RD=200, σ=0.06
- Opponents: (1400, 30, WIN), (1550, 100, LOSS), (1700, 300, LOSS)
- τ = 0.5
- Expected new state: r ≈ 1464.06, RD ≈ 151.52, σ ≈ 0.05999

(Decimals from the paper; tolerance 0.01 for rating/RD, 1e-6 for volatility.)

- [ ] **Step 1: Write failing tests**

Write `tests/Glicko/Glicko2CalculatorTest.php`:
```php
<?php

declare(strict_types=1);

namespace ChaseCrawford\Ratings\Tests\Glicko;

use ChaseCrawford\Ratings\Common\Outcome;
use ChaseCrawford\Ratings\Glicko\Glicko2Calculator;
use ChaseCrawford\Ratings\Glicko\GlickoRating;
use ChaseCrawford\Ratings\Glicko\PeriodMatch;
use PHPUnit\Framework\TestCase;

final class Glicko2CalculatorTest extends TestCase
{
    /**
     * Reference vector from Glickman's 2013 paper "Example of the Glicko-2 system".
     * Player: r=1500, RD=200, σ=0.06; τ=0.5
     * Opponents: (1400, 30, WIN), (1550, 100, LOSS), (1700, 300, LOSS)
     * Expected new state per Glickman: r ≈ 1464.06, RD ≈ 151.52, σ ≈ 0.05999
     */
    public function test_glickman_2013_worked_example(): void
    {
        $glicko = new Glicko2Calculator(tau: 0.5);

        $player = new GlickoRating(rating: 1500, deviation: 200, volatility: 0.06);

        $matches = [
            new PeriodMatch(new GlickoRating(1400, 30, 0.06), Outcome::WIN),
            new PeriodMatch(new GlickoRating(1550, 100, 0.06), Outcome::LOSS),
            new PeriodMatch(new GlickoRating(1700, 300, 0.06), Outcome::LOSS),
        ];

        $new = $glicko->updatePlayer($player, $matches);

        self::assertEqualsWithDelta(1464.06, $new->rating, 0.01);
        self::assertEqualsWithDelta(151.52, $new->deviation, 0.01);
        self::assertEqualsWithDelta(0.05999, $new->volatility, 1e-5);
    }

    public function test_empty_period_grows_deviation_only(): void
    {
        $glicko = new Glicko2Calculator(tau: 0.5);

        $player = new GlickoRating(rating: 1500, deviation: 200, volatility: 0.06);
        $new = $glicko->updatePlayer($player, []);

        // No matches: rating unchanged, deviation increases per φ' = sqrt(φ² + σ²)
        // φ = 200/173.7178 ≈ 1.1513
        // σ = 0.06
        // φ' = sqrt(1.1513² + 0.06²) ≈ 1.1528
        // RD' = 173.7178 · 1.1528 ≈ 200.27
        self::assertSame(1500.0, $new->rating);
        self::assertEqualsWithDelta(200.27, $new->deviation, 0.05);
        self::assertSame(0.06, $new->volatility);
    }

    public function test_default_tau(): void
    {
        $glicko = new Glicko2Calculator();
        self::assertSame(0.5, $glicko->tau);
    }

    public function test_winning_against_higher_rated_opponent_increases_rating(): void
    {
        $glicko = new Glicko2Calculator();
        $player = new GlickoRating(rating: 1500, deviation: 100, volatility: 0.06);

        $new = $glicko->updatePlayer($player, [
            new PeriodMatch(new GlickoRating(1700, 50, 0.06), Outcome::WIN),
        ]);

        self::assertGreaterThan(1500.0, $new->rating);
    }

    public function test_losing_against_lower_rated_opponent_decreases_rating(): void
    {
        $glicko = new Glicko2Calculator();
        $player = new GlickoRating(rating: 1700, deviation: 100, volatility: 0.06);

        $new = $glicko->updatePlayer($player, [
            new PeriodMatch(new GlickoRating(1500, 50, 0.06), Outcome::LOSS),
        ]);

        self::assertLessThan(1700.0, $new->rating);
    }
}
```

- [ ] **Step 2: Run, confirm fail**

```bash
vendor/bin/phpunit tests/Glicko/Glicko2CalculatorTest.php
```

Expected: FAIL — class not found.

- [ ] **Step 3: Implement Glicko2Calculator**

Write `src/Glicko/Glicko2Calculator.php`:
```php
<?php

declare(strict_types=1);

namespace ChaseCrawford\Ratings\Glicko;

final class Glicko2Calculator
{
    private const SCALE = 173.7178;
    private const CONVERGENCE_TOLERANCE = 1e-6;
    private const MAX_ITERATIONS = 100;

    public function __construct(
        public readonly float $tau = 0.5,
    ) {
    }

    /**
     * @param PeriodMatch[] $matchesInPeriod
     */
    public function updatePlayer(GlickoRating $player, array $matchesInPeriod): GlickoRating
    {
        // Convert to Glicko-2 internal scale.
        $mu = ($player->rating - 1500.0) / self::SCALE;
        $phi = $player->deviation / self::SCALE;
        $sigma = $player->volatility;

        // Empty period: only deviation grows.
        if ([] === $matchesInPeriod) {
            $newPhi = sqrt($phi ** 2 + $sigma ** 2);

            return new GlickoRating(
                rating: $player->rating,
                deviation: self::SCALE * $newPhi,
                volatility: $sigma,
            );
        }

        // Compute v and Δ.
        $vInverse = 0.0;
        $deltaSum = 0.0;
        foreach ($matchesInPeriod as $match) {
            $oppMu = ($match->opponent->rating - 1500.0) / self::SCALE;
            $oppPhi = $match->opponent->deviation / self::SCALE;
            $g = $this->g($oppPhi);
            $e = $this->expected($mu, $oppMu, $g);
            $s = $match->outcome->score();

            $vInverse += $g ** 2 * $e * (1.0 - $e);
            $deltaSum += $g * ($s - $e);
        }
        $v = 1.0 / $vInverse;
        $delta = $v * $deltaSum;

        // Iteratively determine new volatility (Illinois / regula-falsi method).
        $newSigma = $this->newVolatility($sigma, $phi, $v, $delta);

        // New deviation.
        $phiStar = sqrt($phi ** 2 + $newSigma ** 2);
        $newPhi = 1.0 / sqrt(1.0 / $phiStar ** 2 + 1.0 / $v);

        // New rating.
        $newMu = $mu + $newPhi ** 2 * $deltaSum;

        // Convert back to original scale.
        return new GlickoRating(
            rating: self::SCALE * $newMu + 1500.0,
            deviation: self::SCALE * $newPhi,
            volatility: $newSigma,
        );
    }

    private function g(float $phi): float
    {
        return 1.0 / sqrt(1.0 + 3.0 * $phi ** 2 / M_PI ** 2);
    }

    private function expected(float $mu, float $oppMu, float $g): float
    {
        return 1.0 / (1.0 + exp(-$g * ($mu - $oppMu)));
    }

    /**
     * Illinois algorithm to find new volatility (per Glickman §5.1, step 5).
     */
    private function newVolatility(float $sigma, float $phi, float $v, float $delta): float
    {
        $a = log($sigma ** 2);
        $f = fn(float $x): float => (exp($x) * ($delta ** 2 - $phi ** 2 - $v - exp($x)))
            / (2.0 * ($phi ** 2 + $v + exp($x)) ** 2)
            - ($x - $a) / $this->tau ** 2;

        // Initial bracket.
        if ($delta ** 2 > $phi ** 2 + $v) {
            $b = log($delta ** 2 - $phi ** 2 - $v);
        } else {
            $k = 1;
            while ($f($a - $k * $this->tau) < 0.0) {
                ++$k;
            }
            $b = $a - $k * $this->tau;
        }

        $fa = $f($a);
        $fb = $f($b);

        for ($i = 0; $i < self::MAX_ITERATIONS; ++$i) {
            if (abs($b - $a) < self::CONVERGENCE_TOLERANCE) {
                break;
            }
            $c = $a + ($a - $b) * $fa / ($fb - $fa);
            $fc = $f($c);

            if ($fc * $fb <= 0.0) {
                $a = $b;
                $fa = $fb;
            } else {
                $fa /= 2.0;
            }
            $b = $c;
            $fb = $fc;
        }

        return exp($a / 2.0);
    }
}
```

- [ ] **Step 4: Run tests, confirm pass**

```bash
vendor/bin/phpunit tests/Glicko/Glicko2CalculatorTest.php
```

Expected: 5 tests PASS. **If the Glickman reference vector test fails by more than the tolerance, debug carefully — the algorithm is sensitive to small numerical errors in the iterative volatility step.**

- [ ] **Step 5: Run full Glicko suite + PHPStan**

```bash
vendor/bin/phpunit tests/Glicko
vendor/bin/phpstan analyse src/Glicko tests/Glicko --no-progress
```

Expected: All pass; PHPStan no errors.

- [ ] **Step 6: Commit**

```bash
git add src/Glicko/Glicko2Calculator.php tests/Glicko/Glicko2CalculatorTest.php
git commit -m "feat(glicko): add Glicko2Calculator validated against Glickman 2013 worked example"
```

---

## Phase 5: RPI (TDD)

### Task 18: `GameResult` value object

**Files:**
- Create: `src/Rpi/GameResult.php`
- Create: `tests/Rpi/GameResultTest.php`

- [ ] **Step 1: Write failing test**

Write `tests/Rpi/GameResultTest.php`:
```php
<?php

declare(strict_types=1);

namespace ChaseCrawford\Ratings\Tests\Rpi;

use ChaseCrawford\Ratings\Common\Exception\InvalidConfigurationException;
use ChaseCrawford\Ratings\Common\Outcome;
use ChaseCrawford\Ratings\Rpi\GameResult;
use PHPUnit\Framework\TestCase;

final class GameResultTest extends TestCase
{
    public function test_holds_competitor_names_and_outcome(): void
    {
        $g = new GameResult('Duke', 'UNC', Outcome::WIN);
        self::assertSame('Duke', $g->competitorA);
        self::assertSame('UNC', $g->competitorB);
        self::assertSame(Outcome::WIN, $g->outcomeForA);
    }

    public function test_rejects_self_play(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        new GameResult('Duke', 'Duke', Outcome::WIN);
    }

    public function test_rejects_empty_competitor_name(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        new GameResult('', 'UNC', Outcome::WIN);
    }
}
```

- [ ] **Step 2: Run, confirm fail**

```bash
vendor/bin/phpunit tests/Rpi/GameResultTest.php
```

Expected: FAIL.

- [ ] **Step 3: Implement GameResult**

Write `src/Rpi/GameResult.php`:
```php
<?php

declare(strict_types=1);

namespace ChaseCrawford\Ratings\Rpi;

use ChaseCrawford\Ratings\Common\Exception\InvalidConfigurationException;
use ChaseCrawford\Ratings\Common\Outcome;

final readonly class GameResult
{
    public function __construct(
        public string $competitorA,
        public string $competitorB,
        public Outcome $outcomeForA,
    ) {
        if ('' === $competitorA || '' === $competitorB) {
            throw new InvalidConfigurationException('Competitor names cannot be empty.');
        }
        if ($competitorA === $competitorB) {
            throw new InvalidConfigurationException(
                "A competitor cannot play themselves (got '{$competitorA}' on both sides).",
            );
        }
    }
}
```

- [ ] **Step 4: Run, confirm pass**

```bash
vendor/bin/phpunit tests/Rpi/GameResultTest.php
```

Expected: 3 tests PASS.

- [ ] **Step 5: Commit**

```bash
git add src/Rpi/GameResult.php tests/Rpi/GameResultTest.php
git commit -m "feat(rpi): add GameResult value object"
```

---

### Task 19: `Weights` value object

**Files:**
- Create: `src/Rpi/Weights.php`
- Create: `tests/Rpi/WeightsTest.php`

- [ ] **Step 1: Write failing test**

Write `tests/Rpi/WeightsTest.php`:
```php
<?php

declare(strict_types=1);

namespace ChaseCrawford\Ratings\Tests\Rpi;

use ChaseCrawford\Ratings\Common\Exception\InvalidConfigurationException;
use ChaseCrawford\Ratings\Rpi\Weights;
use PHPUnit\Framework\TestCase;

final class WeightsTest extends TestCase
{
    public function test_default_weights_are_classic_25_50_25(): void
    {
        $w = new Weights();
        self::assertSame(0.25, $w->own);
        self::assertSame(0.50, $w->opponents);
        self::assertSame(0.25, $w->opponentsOpponents);
    }

    public function test_classic_factory_returns_canonical_weights(): void
    {
        $w = Weights::classic();
        self::assertSame(0.25, $w->own);
        self::assertSame(0.50, $w->opponents);
        self::assertSame(0.25, $w->opponentsOpponents);
    }

    public function test_accepts_custom_weights_summing_to_one(): void
    {
        $w = new Weights(own: 0.4, opponents: 0.4, opponentsOpponents: 0.2);
        self::assertSame(0.4, $w->own);
    }

    public function test_rejects_weights_that_do_not_sum_to_one(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        new Weights(own: 0.5, opponents: 0.5, opponentsOpponents: 0.5);
    }

    public function test_rejects_negative_weights(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        new Weights(own: -0.1, opponents: 0.55, opponentsOpponents: 0.55);
    }
}
```

- [ ] **Step 2: Run, confirm fail**

```bash
vendor/bin/phpunit tests/Rpi/WeightsTest.php
```

Expected: FAIL.

- [ ] **Step 3: Implement Weights**

Write `src/Rpi/Weights.php`:
```php
<?php

declare(strict_types=1);

namespace ChaseCrawford\Ratings\Rpi;

use ChaseCrawford\Ratings\Common\Exception\InvalidConfigurationException;

final readonly class Weights
{
    private const SUM_TOLERANCE = 1e-9;

    public function __construct(
        public float $own = 0.25,
        public float $opponents = 0.50,
        public float $opponentsOpponents = 0.25,
    ) {
        if ($own < 0.0 || $opponents < 0.0 || $opponentsOpponents < 0.0) {
            throw new InvalidConfigurationException('Weights must be non-negative.');
        }
        $sum = $own + $opponents + $opponentsOpponents;
        if (abs($sum - 1.0) > self::SUM_TOLERANCE) {
            throw new InvalidConfigurationException(
                "Weights must sum to 1.0; got {$sum}.",
            );
        }
    }

    public static function classic(): self
    {
        return new self(0.25, 0.50, 0.25);
    }
}
```

- [ ] **Step 4: Run, confirm pass**

```bash
vendor/bin/phpunit tests/Rpi/WeightsTest.php
```

Expected: 5 tests PASS.

- [ ] **Step 5: Commit**

```bash
git add src/Rpi/Weights.php tests/Rpi/WeightsTest.php
git commit -m "feat(rpi): add Weights value object with sum-to-one validation"
```

---

### Task 20: `RpiResult` value object

**Files:**
- Create: `src/Rpi/RpiResult.php`
- Create: `tests/Rpi/RpiResultTest.php`

- [ ] **Step 1: Write failing test**

Write `tests/Rpi/RpiResultTest.php`:
```php
<?php

declare(strict_types=1);

namespace ChaseCrawford\Ratings\Tests\Rpi;

use ChaseCrawford\Ratings\Rpi\RpiResult;
use PHPUnit\Framework\TestCase;

final class RpiResultTest extends TestCase
{
    public function test_holds_ratings_map(): void
    {
        $r = new RpiResult(['A' => 0.7, 'B' => 0.5, 'C' => 0.3]);
        self::assertSame(['A' => 0.7, 'B' => 0.5, 'C' => 0.3], $r->ratings);
    }

    public function test_ranked_returns_sorted_descending(): void
    {
        $r = new RpiResult(['B' => 0.5, 'C' => 0.3, 'A' => 0.7]);
        self::assertSame(['A' => 0.7, 'B' => 0.5, 'C' => 0.3], $r->ranked());
    }

    public function test_ranked_preserves_keys_and_values(): void
    {
        $r = new RpiResult(['Duke' => 0.6234, 'UNC' => 0.5821]);
        $ranked = $r->ranked();
        self::assertSame(['Duke', 'UNC'], array_keys($ranked));
        self::assertSame([0.6234, 0.5821], array_values($ranked));
    }
}
```

- [ ] **Step 2: Run, confirm fail**

```bash
vendor/bin/phpunit tests/Rpi/RpiResultTest.php
```

Expected: FAIL.

- [ ] **Step 3: Implement RpiResult**

Write `src/Rpi/RpiResult.php`:
```php
<?php

declare(strict_types=1);

namespace ChaseCrawford\Ratings\Rpi;

final readonly class RpiResult
{
    /**
     * @param array<string, float> $ratings
     */
    public function __construct(public array $ratings)
    {
    }

    /**
     * @return array<string, float> Same map, sorted by rating descending.
     */
    public function ranked(): array
    {
        $sorted = $this->ratings;
        arsort($sorted);

        return $sorted;
    }
}
```

- [ ] **Step 4: Run, confirm pass**

```bash
vendor/bin/phpunit tests/Rpi/RpiResultTest.php
```

Expected: 3 tests PASS.

- [ ] **Step 5: Commit**

```bash
git add src/Rpi/RpiResult.php tests/Rpi/RpiResultTest.php
git commit -m "feat(rpi): add RpiResult value object with ranked() helper"
```

---

### Task 21: `RpiCalculator` (with worked example)

**Files:**
- Create: `src/Rpi/RpiCalculator.php`
- Create: `tests/Rpi/RpiCalculatorTest.php`

The classic NCAA RPI formula:
- WP (Winning Percentage): wins / total games. Draws count as 0.5 wins.
- OWP (Opponents' Winning Percentage): for each opponent, compute their WP **excluding games against the team being calculated**, then average across all the team's games.
- OOWP (Opponents' Opponents' Winning Percentage): for each opponent, compute their OWP (no exclusion at this level), then average across the team's games.
- RPI = w_own·WP + w_opp·OWP + w_oo·OOWP

Worked reference example (3 teams, round-robin):
- Games: A beats B, A loses to C, B beats C
- A: 1-1, B: 1-1, C: 1-1
- WP_A = 0.5, WP_B = 0.5, WP_C = 0.5
- OWP_A: opponents are B and C. B's WP excluding-games-vs-A = 1/1 = 1.0 (B's only non-A game was beating C). C's WP excluding-games-vs-A = 0/1 = 0.0 (C's only non-A game was losing to B). Average = (1.0 + 0.0) / 2 = 0.5
- OWP_B: opponents are A and C. A's WP excluding-games-vs-B = 0/1 = 0.0 (A only played C and lost). C's WP excluding-games-vs-B = 1/1 = 1.0 (C only played A and won). Average = 0.5
- OWP_C: opponents are A and B. A's WP excluding-games-vs-C = 1/1 = 1.0. B's WP excluding-games-vs-C = 0/1 = 0.0. Average = 0.5
- OOWP_A: average of opponents' (B and C) full OWP = (0.5 + 0.5) / 2 = 0.5
- Similarly OOWP_B = OOWP_C = 0.5
- All teams: RPI = 0.25·0.5 + 0.50·0.5 + 0.25·0.5 = 0.5

(In a perfectly symmetric round-robin everyone gets the same RPI — by design. We use this as a sanity test.)

A second example breaks symmetry: 4 teams, A beats {B, C, D}, B beats C, B beats D, C beats D.
- Records: A 3-0, B 2-1 (lost only to A), C 1-2 (beat D, lost to A and B), D 0-3
- WP: A=1.0, B=2/3, C=1/3, D=0
- OWP_A: opponents B, C, D.
  - B's WP excluding games vs A: B's non-A games are vs C (W) and vs D (W) → 2/2 = 1.0
  - C's WP excluding games vs A: C's non-A games are vs B (L) and vs D (W) → 1/2 = 0.5
  - D's WP excluding games vs A: D's non-A games are vs B (L) and vs C (L) → 0/2 = 0.0
  - Average over A's 3 games: (1.0 + 0.5 + 0.0) / 3 ≈ 0.5
- OOWP_A: opponents B, C, D — average their full OWP.
  - B's OWP (full): opponents A (excl B's game vs B → A's WP minus B-game = A is 2/2 vs others = 1.0), C (excl B-game → C is 1/2 = 0.5), D (excl B-game → D is 0/2 = 0.0). Avg = (1+0.5+0)/3 = 0.5
  - C's OWP (full): opponents A (excl C → 2/2=1.0), B (excl C → 1/2=0.5 since B's non-C games are A loss + D win), D (excl C → 0/2=0.0). Avg = 0.5
  - D's OWP (full): opponents A (excl D → 2/2=1.0), B (excl D → 1/2=0.5), C (excl D → 0/2=0.0). Avg = 0.5
  - OOWP_A = (0.5 + 0.5 + 0.5) / 3 = 0.5
- RPI_A = 0.25·1.0 + 0.50·0.5 + 0.25·0.5 = 0.25 + 0.25 + 0.125 = 0.625

This second case gives us a non-trivial assertion target: A should have RPI ≈ 0.625.

- [ ] **Step 1: Write failing tests**

Write `tests/Rpi/RpiCalculatorTest.php`:
```php
<?php

declare(strict_types=1);

namespace ChaseCrawford\Ratings\Tests\Rpi;

use ChaseCrawford\Ratings\Common\Outcome;
use ChaseCrawford\Ratings\Rpi\GameResult;
use ChaseCrawford\Ratings\Rpi\RpiCalculator;
use ChaseCrawford\Ratings\Rpi\Weights;
use PHPUnit\Framework\TestCase;

final class RpiCalculatorTest extends TestCase
{
    private const TOL = 1e-9;

    /**
     * Round-robin 3-team: every team is symmetric, every RPI must equal 0.5.
     */
    public function test_symmetric_round_robin_yields_equal_rpi(): void
    {
        $rpi = new RpiCalculator();
        $result = $rpi->rate([
            new GameResult('A', 'B', Outcome::WIN),
            new GameResult('A', 'C', Outcome::LOSS),
            new GameResult('B', 'C', Outcome::WIN),
        ]);

        self::assertEqualsWithDelta(0.5, $result->ratings['A'], self::TOL);
        self::assertEqualsWithDelta(0.5, $result->ratings['B'], self::TOL);
        self::assertEqualsWithDelta(0.5, $result->ratings['C'], self::TOL);
    }

    /**
     * 4-team example with hand-computed RPI for team A.
     * Games: A beats B, C, D; B beats C, D; C beats D.
     * Hand calc: WP_A=1.0, OWP_A=0.5, OOWP_A=0.5
     * RPI_A = 0.25·1.0 + 0.50·0.5 + 0.25·0.5 = 0.625
     */
    public function test_four_team_hand_computed_rpi_for_a(): void
    {
        $rpi = new RpiCalculator();
        $result = $rpi->rate([
            new GameResult('A', 'B', Outcome::WIN),
            new GameResult('A', 'C', Outcome::WIN),
            new GameResult('A', 'D', Outcome::WIN),
            new GameResult('B', 'C', Outcome::WIN),
            new GameResult('B', 'D', Outcome::WIN),
            new GameResult('C', 'D', Outcome::WIN),
        ]);

        self::assertEqualsWithDelta(0.625, $result->ratings['A'], self::TOL);
    }

    public function test_returns_rating_for_every_competitor_in_results(): void
    {
        $rpi = new RpiCalculator();
        $result = $rpi->rate([
            new GameResult('A', 'B', Outcome::WIN),
            new GameResult('B', 'C', Outcome::WIN),
        ]);

        self::assertArrayHasKey('A', $result->ratings);
        self::assertArrayHasKey('B', $result->ratings);
        self::assertArrayHasKey('C', $result->ratings);
    }

    public function test_custom_weights_change_ranking(): void
    {
        // Weight WP at 100% — equivalent to pure win percentage.
        $rpi = new RpiCalculator(new Weights(own: 1.0, opponents: 0.0, opponentsOpponents: 0.0));
        $result = $rpi->rate([
            new GameResult('A', 'B', Outcome::WIN),
            new GameResult('A', 'C', Outcome::WIN),
            new GameResult('B', 'C', Outcome::LOSS),
        ]);

        self::assertEqualsWithDelta(1.0, $result->ratings['A'], self::TOL);
        self::assertEqualsWithDelta(0.0, $result->ratings['B'], self::TOL);
        self::assertEqualsWithDelta(1.0, $result->ratings['C'], self::TOL);
    }

    public function test_draws_count_as_half_wins(): void
    {
        $rpi = new RpiCalculator(new Weights(own: 1.0, opponents: 0.0, opponentsOpponents: 0.0));
        $result = $rpi->rate([
            new GameResult('A', 'B', Outcome::DRAW),
            new GameResult('A', 'C', Outcome::DRAW),
        ]);

        self::assertEqualsWithDelta(0.5, $result->ratings['A'], self::TOL);
    }

    public function test_empty_games_returns_empty_result(): void
    {
        $rpi = new RpiCalculator();
        $result = $rpi->rate([]);
        self::assertSame([], $result->ratings);
    }
}
```

- [ ] **Step 2: Run, confirm fail**

```bash
vendor/bin/phpunit tests/Rpi/RpiCalculatorTest.php
```

Expected: FAIL.

- [ ] **Step 3: Implement RpiCalculator**

Write `src/Rpi/RpiCalculator.php`:
```php
<?php

declare(strict_types=1);

namespace ChaseCrawford\Ratings\Rpi;

use ChaseCrawford\Ratings\Common\Outcome;

final class RpiCalculator
{
    public function __construct(
        private readonly Weights $weights = new Weights(),
    ) {
    }

    /**
     * @param GameResult[] $games
     */
    public function rate(array $games): RpiResult
    {
        if ([] === $games) {
            return new RpiResult([]);
        }

        $competitors = $this->collectCompetitors($games);
        $ratings = [];

        foreach ($competitors as $competitor) {
            $wp = $this->winPercentage($competitor, $games, excludeOpponent: null);
            $owp = $this->opponentsWinPercentage($competitor, $games);
            $oowp = $this->opponentsOpponentsWinPercentage($competitor, $games);

            $ratings[$competitor] = $this->weights->own * $wp
                + $this->weights->opponents * $owp
                + $this->weights->opponentsOpponents * $oowp;
        }

        return new RpiResult($ratings);
    }

    /**
     * @param GameResult[] $games
     * @return list<string>
     */
    private function collectCompetitors(array $games): array
    {
        $set = [];
        foreach ($games as $g) {
            $set[$g->competitorA] = true;
            $set[$g->competitorB] = true;
        }

        return array_keys($set);
    }

    /**
     * Win percentage for $competitor, optionally excluding games against $excludeOpponent.
     * Draws count as 0.5 wins.
     *
     * @param GameResult[] $games
     */
    private function winPercentage(string $competitor, array $games, ?string $excludeOpponent): float
    {
        $wins = 0.0;
        $count = 0;
        foreach ($games as $g) {
            if (!$this->involves($g, $competitor)) {
                continue;
            }
            if (null !== $excludeOpponent && $this->involves($g, $excludeOpponent)) {
                continue;
            }
            ++$count;
            $outcomeForCompetitor = ($g->competitorA === $competitor)
                ? $g->outcomeForA
                : $g->outcomeForA->inverse();
            $wins += $outcomeForCompetitor->score();
        }

        return 0 === $count ? 0.0 : $wins / $count;
    }

    /**
     * Average opponent WP, with each opponent's WP computed excluding games vs $competitor.
     * Averaged across competitor's games (so an opponent faced twice contributes twice).
     *
     * @param GameResult[] $games
     */
    private function opponentsWinPercentage(string $competitor, array $games): float
    {
        $sum = 0.0;
        $count = 0;
        foreach ($games as $g) {
            if (!$this->involves($g, $competitor)) {
                continue;
            }
            $opponent = ($g->competitorA === $competitor) ? $g->competitorB : $g->competitorA;
            $sum += $this->winPercentage($opponent, $games, excludeOpponent: $competitor);
            ++$count;
        }

        return 0 === $count ? 0.0 : $sum / $count;
    }

    /**
     * Average of opponents' full OWP (no exclusion at this level).
     *
     * @param GameResult[] $games
     */
    private function opponentsOpponentsWinPercentage(string $competitor, array $games): float
    {
        $sum = 0.0;
        $count = 0;
        foreach ($games as $g) {
            if (!$this->involves($g, $competitor)) {
                continue;
            }
            $opponent = ($g->competitorA === $competitor) ? $g->competitorB : $g->competitorA;
            $sum += $this->opponentFullOwp($opponent, $games);
            ++$count;
        }

        return 0 === $count ? 0.0 : $sum / $count;
    }

    /**
     * Helper: opponent's OWP without excluding any team.
     * Used to compute OOWP (one level removed from the team being rated).
     *
     * @param GameResult[] $games
     */
    private function opponentFullOwp(string $competitor, array $games): float
    {
        $sum = 0.0;
        $count = 0;
        foreach ($games as $g) {
            if (!$this->involves($g, $competitor)) {
                continue;
            }
            $opponent = ($g->competitorA === $competitor) ? $g->competitorB : $g->competitorA;
            $sum += $this->winPercentage($opponent, $games, excludeOpponent: $competitor);
            ++$count;
        }

        return 0 === $count ? 0.0 : $sum / $count;
    }

    private function involves(GameResult $g, string $competitor): bool
    {
        return $g->competitorA === $competitor || $g->competitorB === $competitor;
    }
}
```

- [ ] **Step 4: Run tests, confirm pass**

```bash
vendor/bin/phpunit tests/Rpi/RpiCalculatorTest.php
```

Expected: 6 tests PASS. The `four_team_hand_computed_rpi_for_a` test specifically validates the canonical RPI formula; if it fails, the OWP/OOWP exclusion logic is wrong.

- [ ] **Step 5: Run full suite + PHPStan + style**

```bash
composer ci
```

Expected: style-check passes, PHPStan no errors, all tests pass.

- [ ] **Step 6: Commit**

```bash
git add src/Rpi/RpiCalculator.php tests/Rpi/RpiCalculatorTest.php
git commit -m "feat(rpi): add RpiCalculator with hand-computed reference vector"
```

---

## Phase 6: Coverage gate, documentation, governance

### Task 22: Add coverage threshold to CI

**Files:**
- Modify: `.github/workflows/ci.yml`

- [ ] **Step 1: Add coverage check step**

Edit `.github/workflows/ci.yml`. Inside the `test:` job, after the `phpunit` step and before the codecov step, add:

```yaml
      - name: Enforce 95% coverage
        if: matrix.php == '8.3' && matrix.deps == 'stable'
        run: |
          php -r '
            $xml = simplexml_load_file("coverage.xml");
            $m = $xml->project->metrics;
            $covered = (int)$m["coveredstatements"];
            $total = (int)$m["statements"];
            $pct = $total > 0 ? 100 * $covered / $total : 0;
            printf("Statement coverage: %d / %d (%.2f%%)\n", $covered, $total, $pct);
            if ($pct < 95.0) {
              fwrite(STDERR, "Coverage below 95% threshold\n");
              exit(1);
            }
          '
```

- [ ] **Step 2: Verify locally**

```bash
vendor/bin/phpunit --coverage-clover=coverage.xml
php -r '$xml=simplexml_load_file("coverage.xml"); $m=$xml->project->metrics; printf("%.2f%%\n", 100*(int)$m["coveredstatements"]/(int)$m["statements"]);'
```

Expected: prints a coverage percentage. Should be ≥ 95% — if not, add tests for any uncovered branches before proceeding.

- [ ] **Step 3: Commit**

```bash
git add .github/workflows/ci.yml
git commit -m "ci: enforce 95% statement coverage threshold"
```

---

### Task 23: Rewrite README

**Files:**
- Modify: `README.md` (full rewrite)

- [ ] **Step 1: Replace README**

Write `README.md`:
```markdown
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
$elo = new EloCalculator(new CallableK(       // your own logic
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
```

- [ ] **Step 2: Verify it renders**

(Optional) preview locally with a markdown renderer (e.g., VS Code preview pane).

- [ ] **Step 3: Commit**

```bash
git add README.md
git commit -m "docs: rewrite README for v2 (three algorithms, badges, comparison table)"
```

---

### Task 24: Add CHANGELOG, CONTRIBUTING, SECURITY, issue templates

**Files:**
- Create: `CHANGELOG.md`
- Create: `CONTRIBUTING.md`
- Create: `SECURITY.md`
- Create: `.github/ISSUE_TEMPLATE/bug_report.md`
- Create: `.github/ISSUE_TEMPLATE/feature_request.md`

- [ ] **Step 1: Create CHANGELOG.md**

Write `CHANGELOG.md`:
```markdown
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
```

(The `2026-05-DD` placeholder gets replaced with the actual release date in Task 26.)

- [ ] **Step 2: Create CONTRIBUTING.md**

Write `CONTRIBUTING.md`:
```markdown
# Contributing

Thanks for your interest in contributing.

## Development setup

```bash
git clone https://github.com/chasecrawford/ratings.git
cd ratings
composer install
```

## Running checks

```bash
composer test          # PHPUnit
composer phpstan       # PHPStan max
composer style-check   # PHP-CS-Fixer (dry run)
composer fix           # PHP-CS-Fixer (apply)
composer ci            # Run all of the above
```

## Pull requests

- Add tests for any new behavior. Coverage gate is 95%.
- Run `composer ci` locally before submitting; CI runs the same checks.
- New algorithms or rating-system variants: open an issue first to discuss design.
- Keep commits focused and descriptive.

## Reporting bugs

Open an issue using the bug report template. Include a minimal reproduction.
```

- [ ] **Step 3: Create SECURITY.md**

Write `SECURITY.md`:
```markdown
# Security policy

Please report security vulnerabilities privately by emailing **adalius.crawford@gmail.com**.

Do not open public issues for security reports.
```

- [ ] **Step 4: Create issue templates**

Write `.github/ISSUE_TEMPLATE/bug_report.md`:
```markdown
---
name: Bug report
about: Report a bug in the library
labels: bug
---

**Description**

A clear description of the bug.

**Reproduction**

Minimal code example that reproduces the issue:

```php
// ...
```

**Expected behavior**

What you expected to happen.

**Actual behavior**

What actually happened (including any exception or output).

**Environment**

- PHP version:
- Library version:
```

Write `.github/ISSUE_TEMPLATE/feature_request.md`:
```markdown
---
name: Feature request
about: Suggest a new algorithm, variant, or capability
labels: enhancement
---

**What problem does this solve?**

**Proposed API or behavior**

```php
// example usage
```

**Alternatives considered**
```

- [ ] **Step 5: Commit**

```bash
git add CHANGELOG.md CONTRIBUTING.md SECURITY.md .github/ISSUE_TEMPLATE
git commit -m "docs: add CHANGELOG, CONTRIBUTING, SECURITY, and issue templates"
```

---

## Phase 7: Release

### Task 25: Open PR, merge, tag v2.0.0 (manual)

These steps require user action — they touch shared state (the `main` branch on GitHub).

- [ ] **Step 1: Run final CI locally**

```bash
composer ci
```

Expected: all green.

- [ ] **Step 2: Push the feature branch**

```bash
git push -u origin feat/v2-revival
```

- [ ] **Step 3: Open the PR**

```bash
gh pr create --title "v2.0.0: full redesign as multi-algorithm rating toolkit" --body "$(cat <<'EOF'
## Summary
- Replaces v1 accumulator API with pure stateless calculators
- Adds Glicko-2 alongside refactored Elo and RPI
- Targets PHP 8.2+; full CI (PHPStan max, PHPUnit matrix, 95% coverage gate)
- Validated against Glickman 2013 Glicko-2 reference vector and hand-computed RPI examples

See `docs/superpowers/specs/2026-05-03-ratings-v2-revival-design.md` for the full design.
See `CHANGELOG.md` for the user-facing change list.

## Test plan
- [ ] All CI jobs green on PR
- [ ] Coverage ≥ 95%
- [ ] README renders correctly on the PR page
EOF
)"
```

- [ ] **Step 4: Wait for CI green, then merge**

```bash
gh pr checks --watch
gh pr merge --squash --delete-branch
git checkout main && git pull origin main
```

- [ ] **Step 5: Update CHANGELOG release date and tag v2.0.0**

Edit `CHANGELOG.md`: replace `2026-05-DD` with today's actual date.

```bash
git add CHANGELOG.md
git commit -m "docs: set v2.0.0 release date in CHANGELOG"
git push origin main

git tag -a v2.0.0 -m "v2.0.0: full redesign — Elo, Glicko-2, RPI as pure calculators"
git push origin v2.0.0
```

- [ ] **Step 6: Create GitHub release**

```bash
gh release create v2.0.0 --title "v2.0.0" --notes-from-tag
```

(Or paste the v2.0.0 section of CHANGELOG.md as the release body via `--notes "..."`.)

---

### Task 26: Submit to Packagist (manual, one-time)

- [ ] **Step 1: Submit the package**

Go to https://packagist.org/packages/submit and paste the GitHub URL: `https://github.com/chasecrawford/ratings`. Click Submit.

- [ ] **Step 2: Wire the GitHub auto-update webhook**

On the new Packagist package page, click "Settings" → copy the API token + URL → on GitHub, repo Settings → Webhooks → Add webhook with that URL and a JSON payload. Test fire it once.

- [ ] **Step 3: Verify install works**

In a scratch directory:

```bash
mkdir /tmp/ratings-smoketest && cd /tmp/ratings-smoketest
composer init --no-interaction --name=test/test --require=chasecrawford/ratings:^2.0
composer install
php -r '
  require "vendor/autoload.php";
  use ChaseCrawford\Ratings\Common\Outcome;
  use ChaseCrawford\Ratings\Elo\EloCalculator;
  use ChaseCrawford\Ratings\Elo\EloRating;
  $u = (new EloCalculator())->rate(new EloRating(1500), new EloRating(1400), Outcome::WIN);
  echo $u->newA->value, "\n";
'
```

Expected: prints something like `1505.39...` (depends on default K=15). If it prints a number, the package is fully installable from Packagist.

---

## Done

The package is now:
- Implemented (Elo + Glicko-2 + RPI, pure-calculator API)
- Tested (PHPUnit, 95%+ coverage, reference vectors)
- Type-checked (PHPStan max)
- Style-checked (PHP-CS-Fixer)
- CI-protected (GitHub Actions matrix)
- Documented (README, CHANGELOG, CONTRIBUTING, SECURITY, issue templates)
- Tagged (v2.0.0)
- Published (Packagist with auto-update webhook)

Ready to feature on chasecrawford.dev.
