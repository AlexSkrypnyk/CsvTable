# AGENTS.md

This file provides guidance to AI agents when working with
code in this repository.


## Project Overview

CsvTable is a PHP library for parsing and formatting CSV content. It provides:

- CSV parsing with configurable separators, enclosures and escape characters
- Built-in formatters: CSV, text table and Markdown table
- Support for custom formatters (callbacks or class methods)
- Header and no-header modes
- Column manipulation: reorder, filter and exclude columns


## PHP Application Architecture


### Class Library

This project ships classes only - there is no CLI entry point:

- **Location:** `CsvTable.php` in the repository root, autoloaded PSR-4
- **Consumed by:** other projects, via `composer require`
- **Use for:** packages such as test contexts, extensions, plugins and
  interface implementations

The entire library is a single class so that the file can be dropped into a
project that does not use Composer. Keep it self-contained: `CsvTable.php`
has no dependencies beyond the PHP standard library.


### Namespace Structure

- Source code: `AlexSkrypnyk\CsvTable\`
- Tests: `AlexSkrypnyk\CsvTable\Tests\`
- Autoloading: PSR-4 via Composer, mapped to the repository root


### Formatter Pattern

Formatters are static methods - `formatCsv()`, `formatTable()`,
`formatMarkdownTable()` - that receive:

- `array<string> $header` - header row columns
- `array<array<string>> $rows` - data rows
- `array<string,string> $options` - formatter-specific options

A custom formatter is an anonymous function, a class method passed as
`[ClassName::class, 'methodName']`, or any other callable.


### Key Implementation Details

- `parse()` writes the CSV string to a `php://memory` stream and reads it back
  with `fgetcsv()`. Do not replace this with line-by-line `str_getcsv()`:
  `fgetcsv()` reads across line boundaries while inside an enclosure, which is
  what allows a quoted value to contain a newline. Splitting the input into
  lines first breaks that, and `testFormatterCsvMultiline` covers it.
- `$shouldParseHeader` controls header parsing.
- The Markdown table formatter calculates column widths with an index loop over
  the maximum column count, guarding each cell with `isset()`. Ragged rows
  therefore never produce a `NULL` cell, and the callbacks are typed `string`
  rather than `?string`.

## Commands

### Code Quality

```bash
# Run all linters (PHPCS, PHPStan, Rector)
composer lint

# Auto-fix code style issues
composer lint-fix

# Individual tools
./vendor/bin/phpcs # Check coding standards
./vendor/bin/phpcbf # Fix coding standards
./vendor/bin/phpstan # Static analysis (level 9)
./vendor/bin/rector --dry-run # Check Rector suggestions
```

### Testing

```bash
# Run all PHPUnit tests (fast, no coverage)
composer test

# Run with coverage reports
composer test-coverage
# Coverage reports: .logs/.coverage-html/index.html, .logs/cobertura.xml

# Run specific test file
./vendor/bin/phpunit tests/phpunit/CsvTableUnitTest.php

# Run specific test method
./vendor/bin/phpunit --filter testMethodName
```


### Building


```bash
# Clean and reinstall dependencies
composer reset # removes vendor/, composer.lock
composer install
```

## Code Quality Standards

### Three-Layer Quality Stack

1. **PHP_CodeSniffer** - Drupal coding standards + strict types requirement
  - Config: `phpcs.xml`
  - Rules: Drupal standard, DrevOps standard,
    Generic.PHP.RequireStrictTypes
  - Relaxed rules in test files (long arrays, missing function docs)

2. **PHPStan** - Level 9 static analysis
  - Config: `phpstan.neon`
  - Ignores: Untyped iterables in tests/data providers

3. **Rector** - PHP 8.3 modernization + code quality
  - Config: `rector.php`
  - Sets: PHP_83, CODE_QUALITY, CODING_STYLE, DEAD_CODE,
    TYPE_DECLARATION

### Coding Conventions

- All PHP files must declare `strict_types=1`
- Use single quotes for strings (double quotes if containing single quote)
- All files must end with a newline character
- Local variables/method arguments: `snake_case`
- Method names/class properties: `camelCase`

## Testing Patterns

### PHPUnit Structure

- `tests/phpunit/` - Unit tests for the library and its formatters
- `tests/phpunit/TestFormatter.php` - Custom formatter used by the tests

### Writing Tests

Tests should use PHPUnit 12 features:

- Coverage attributes: `#[CoversClass(ClassName::class)]`
- Test attributes: `#[Test]` (optional, using `test` prefix is also fine)
- Data providers: `#[DataProvider('providerMethodName')]`

Fixtures are heredoc strings holding multiline CSV. Cover every built-in
formatter, the custom formatter paths, and the edge cases the parser has to
survive: multiline values, empty headers and special characters.


## CI/CD

GitHub Actions workflows test across:

- PHP versions: 8.3, 8.4, 8.5
- Dependency sets: normal and lowest
- Lint, coverage threshold and Codecov upload run on the 8.4 normal leg only

Key workflows:

- `.github/workflows/test-php.yml` - PHP testing


## Updating from the template

This project was generated from a template and can pull the template's latest
infrastructure (CI workflows, linting and test configuration, Docker, and docs)
at any time. When you are asked to "update scaffold", fetch the updater skill
from the template repository into the local `.claude/skills/` directory, then
invoke it:

1. Create the target directory if it does not exist:

   ```bash
   mkdir -p .claude/skills/update-consumer-scaffold
   ```

2. Download the skill:

   ```bash
   curl -sSL https://raw.githubusercontent.com/AlexSkrypnyk/scaffold/main/.scaffold/skills/update-consumer-scaffold/SKILL.md -o .claude/skills/update-consumer-scaffold/SKILL.md
   ```

3. Invoke the `update-consumer-scaffold` skill and follow its steps.

The skill directory is fetched on demand and is git-ignored - it is not
committed to the project.
