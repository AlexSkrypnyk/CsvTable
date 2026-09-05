# Contributing

Thank you for considering a contribution to this project. This guide covers setting up a local environment and running the linting and tests.

## Setup

```bash
composer install
```

## Linting

`composer lint` runs PHP_CodeSniffer, PHPStan and Rector in check mode. `composer lint-fix` applies the fixes that Rector and PHP_CodeSniffer can make automatically.

```bash
composer lint
composer lint-fix
```

## Testing

`composer test` runs the PHPUnit suite. `composer test-coverage` additionally produces coverage reports in `.logs/`.

```bash
composer test
composer test-coverage
```

## Rebuilding dependencies

`composer reset` removes `vendor/` and `composer.lock` so the dependencies can be installed from scratch.

```bash
composer reset
composer install
```
