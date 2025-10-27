# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

CsvTable is a single-file PHP library for parsing and formatting CSV content. The core functionality is contained in `CsvTable.php`, which provides:
- CSV parsing with configurable separators, enclosures, and escape characters
- Built-in formatters: CSV, text table, and Markdown table
- Support for custom formatters (callbacks or class methods)
- Header/no-header modes

## Architecture

### Single-Class Design
The entire library is implemented as a single class (`CsvTable`) for portability. All functionality is self-contained in `CsvTable.php`.

### Formatter Pattern
Formatters are static methods (e.g., `formatCsv()`, `formatTable()`, `formatMarkdownTable()`) that receive:
- `array<string> $header` - Header row columns
- `array<array<string>> $rows` - Data rows
- `array<string,string> $options` - Formatter-specific options

Custom formatters can be:
- Anonymous functions
- Class methods (using `[ClassName::class, 'methodName']` syntax)
- Callable references

### Key Implementation Details
- The `parse()` method uses `str_getcsv()` to parse CSV strings line by line
- Header parsing is controlled by `$shouldParseHeader` boolean
- The Markdown table formatter uses `array_map(NULL, ...)` to transpose arrays for calculating column widths
  - Note: This can produce `null` values when arrays have different lengths, so type hints must use `?string`

## Development Commands

### Testing
```bash
composer test              # Run unit tests without coverage
composer test-coverage     # Run unit tests with coverage
```

To run a single test:
```bash
./vendor/bin/phpunit --filter testMethodName
```

### Code Quality
```bash
composer lint              # Run all linting (PHPCS, PHPStan, Rector dry-run)
composer lint-fix          # Fix auto-fixable issues (Rector + PHPCBF)
```

Individual linters:
```bash
./vendor/bin/phpcs         # PHP_CodeSniffer
./vendor/bin/phpcbf        # PHP Code Beautifier and Fixer
./vendor/bin/phpstan       # PHPStan static analysis
./vendor/bin/rector        # Rector (with --clear-cache for actual fixes)
```

### Standards

#### Coding Standards
- Follows Drupal coding standards (via `drupal/coder`)
- Enforces strict types declaration (`declare(strict_types=1);`)
- PHP 8.2+ minimum version
- PSR-4 autoloading with namespace `AlexSkrypnyk\CsvTable`

#### PHPStan Configuration
- Level 9 (strictest)
- Analyzes both source (`CsvTable.php`) and tests (`tests/`)

#### Rector Configuration
- Target: PHP 8.2
- Includes: CODE_QUALITY, CODING_STYLE, DEAD_CODE, INSTANCEOF, TYPE_DECLARATION
- See `rector.php` for skipped rules

## File Structure
```
CsvTable.php           # Main library class
tests/phpunit/         # PHPUnit tests
  CsvTableUnitTest.php # Main test class
  TestFormatter.php    # Custom formatter for testing
phpunit.xml            # PHPUnit configuration
phpcs.xml              # PHP_CodeSniffer configuration
phpstan.neon           # PHPStan configuration
rector.php             # Rector configuration
```

## Testing Notes
- Test fixtures use heredoc syntax for multiline CSV data
- Tests cover all built-in formatters and custom formatter scenarios
- Tests include edge cases: multiline values, empty headers, special characters
