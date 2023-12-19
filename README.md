<p align="center">
  <a href="" rel="noopener">
  <img width=200px height=200px src="https://placehold.jp/000000/ffffff/200x200.png?text=CsvTable&css=%7B%22border-radius%22%3A%22%20100px%22%7D" alt="Yourproject logo"></a>
</p>

<h1 align="center">CsvTable</h1>

<div align="center">

[![GitHub Issues](https://img.shields.io/github/issues/AlexSkrypnyk/CsvTable.svg)](https://github.com/AlexSkrypnyk/CsvTable/issues)
[![GitHub Pull Requests](https://img.shields.io/github/issues-pr/AlexSkrypnyk/CsvTable.svg)](https://github.com/AlexSkrypnyk/CsvTable/pulls)
[![Test](https://github.com/AlexSkrypnyk/CsvTable/actions/workflows/test.yml/badge.svg)](https://github.com/AlexSkrypnyk/CsvTable/actions/workflows/test.yml)
[![codecov](https://codecov.io/gh/AlexSkrypnyk/CsvTable/graph/badge.svg?token=7WEB1IXBYT)](https://codecov.io/gh/AlexSkrypnyk/CsvTable)
![GitHub release (latest by date)](https://img.shields.io/github/v/release/AlexSkrypnyk/CsvTable)
![LICENSE](https://img.shields.io/github/license/AlexSkrypnyk/CsvTable)
![Renovate](https://img.shields.io/badge/renovate-enabled-green?logo=renovatebot)

</div>

---

<p align="center"> PHP class to work with CSV as a table and export it as Markdown.
    <br>
</p>

## Features

- Single-file class to manipulate CSV table.
- Renderers for CSV and text table.
- Ability to provide custom renderer.

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
// Render using the default renderer.
print (new CsvTable($csv))->render();
```
will produce identical CSV content by default:
```csv
col11,col12,col13
col21,col22,col23
col31,col32,col33      
```

### From file

```php
print (CsvTable::fromFile($file))->render();
```
will produce identical CSV content by default:
```csv
col11,col12,col13
col21,col22,col23
col31,col32,col33
```

### Using `CsvTable::renderTextTable()` renderer

```php
print (CsvTable::fromFile($file))->render([CsvTable::class, 'renderTextTable']);
```
will produce table content:
```csv
col11|col12|col13
-----------------
col21|col22|col23
col31|col32|col33     
```

### Using `CsvTable::renderTextTable()` renderer with disabled header

```php
print (CsvTable::fromFile($file))->noHeader()->render([CsvTable::class, 'renderTextTable']);
```
will produce table content:
```csv
col11|col12|col13
col21|col22|col23
col31|col32|col33     
```

### Custom renderer from class

```php
print (CsvTable::fromFile($file))->render(Markdown::class);
```
will produce Markdown content:
```markdown
| col11 | col12 | col13 |
|-------|-------|-------|
| col21 | col22 | col23 |
| col31 | col32 | col33 |     
```

### Custom renderer as a callback

```php
print (CsvTable::fromFile($file))->render(function ($header, $rows, $options) {
  if (count($header) > 0) {
    $header = implode('|', $header);
    $header = $header . "\n" . str_repeat('-', strlen($header)) . "\n";
  }
  else {
    $header = '';
  }

  return $header . implode("\n", array_map(function ($row) {
    return implode('|', $row);
  }, $rows));
});
```
will produce CSV content:
```csv
col11|col12|col13
-----------------
col21|col22|col23
col31|col32|col33     
```

## Maintenance

```bash
composer install
composer lint
composer test
```
