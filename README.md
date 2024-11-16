<p align="center">
  <a href="" rel="noopener">
  <img width=200px height=200px src="https://placehold.jp/000000/ffffff/200x200.png?text=CsvTable&css=%7B%22border-radius%22%3A%22%20100px%22%7D" alt="Yourproject logo"></a>
</p>

<h1 align="center">PHP class to parse and format CSV content</h1>

<div align="center">

[![GitHub Issues](https://img.shields.io/github/issues/AlexSkrypnyk/CsvTable.svg)](https://github.com/AlexSkrypnyk/CsvTable/issues)
[![GitHub Pull Requests](https://img.shields.io/github/issues-pr/AlexSkrypnyk/CsvTable.svg)](https://github.com/AlexSkrypnyk/CsvTable/pulls)
[![Test](https://github.com/AlexSkrypnyk/CsvTable/actions/workflows/test-php.yml/badge.svg)](https://github.com/AlexSkrypnyk/CsvTable/actions/workflows/test-php.yml)
[![codecov](https://codecov.io/gh/AlexSkrypnyk/CsvTable/graph/badge.svg?token=7WEB1IXBYT)](https://codecov.io/gh/AlexSkrypnyk/CsvTable)
![GitHub release (latest by date)](https://img.shields.io/github/v/release/AlexSkrypnyk/CsvTable)
![LICENSE](https://img.shields.io/github/license/AlexSkrypnyk/CsvTable)
![Renovate](https://img.shields.io/badge/renovate-enabled-green?logo=renovatebot)

</div>

---

## Features

- Single-file class to manipulate CSV table.
- Formatters for CSV, text table and Markdown table.
- Support for a custom formatter.

## Installation

```bash
composer require alexskrypnyk/csvtable
```    

## Usage

Given a CSV file with the following content:
```csv
col11,col12,col13
col21,col22,col23
col31,col32,col33      
```

### From string

```php
$csv = file_get_contents($csv_file);
// Format using the default formatter.
print (new CsvTable($csv))->format();
```
will produce identical CSV content by default:
```csv
col11,col12,col13
col21,col22,col23
col31,col32,col33      
```

### From file

```php
print (CsvTable::fromFile($file))->format();
```
will produce identical CSV content by default:
```csv
col11,col12,col13
col21,col22,col23
col31,col32,col33
```

### Using `text_table` formatter

```php
print (CsvTable::fromFile($file))->format('text_table');
```
will produce table content:
```csv
col11|col12|col13
-----------------
col21|col22|col23
col31|col32|col33     
```

### Using `text_table` formatter without a header

```php
print (CsvTable::fromFile($file))->withoutHeader()->format('text_table');
```
will produce table content:
```csv
col11|col12|col13
col21|col22|col23
col31|col32|col33     
```

### Using `markdown_table` formatter

```php
print (CsvTable::fromFile($file))->withoutHeader()->format('markdown_table');
```
will produce Markdown table:
```markdown
| col11 | col12 | col13 |
|-------|-------|-------|
| col21 | col22 | col23 |
| col31 | col32 | col33 |     
```

### Custom formatter as an anonymous callback

```php
print (CsvTable::fromFile($file))->format(function ($header, $rows, $options) {
  $output = '';

  if (count($header) > 0) {
    $output = implode('|', $header);
    $output .= "\n" . str_repeat('=', strlen($output)) . "\n";
  }

  return $output . implode("\n", array_map(static function ($row): string {
    return implode('|', $row);
  }, $rows));
});
```
will produce CSV content:
```csv
col11|col12|col13
=================
col21|col22|col23
col31|col32|col33     
```

### Custom formatter as a class with default `format` method

```php
print (CsvTable::fromFile($file))->withoutHeader()->format(CustomFormatter::class);
```

### Custom formatter as a class with a custom method and options

```php
$formatter_options = ['option1' => 'value1', 'option2' => 'value2'];
print (CsvTable::fromFile($file))->withoutHeader()->format([CustomFormatter::class, 'customFormat'], $formatter_options);
```

## Maintenance

```bash
composer install
composer lint
composer test
```
---
_This repository was created using the [Scaffold](https://getscaffold.dev/) project template_
