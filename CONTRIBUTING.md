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
