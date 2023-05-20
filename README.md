# CsvTable

[![Tests](https://github.com/AlexSkrypnyk/CsvTable/actions/workflows/test.yml/badge.svg)](https://github.com/AlexSkrypnyk/CsvTable/actions/workflows/test.yml)
![GitHub release (latest by date)](https://img.shields.io/github/v/release/AlexSkrypnyk/CsvTable)
![LICENSE](https://img.shields.io/github/license/AlexSkrypnyk/CsvTable)

## Features

- Single-file class to manipulate CSV table.
- Converter to Markdown table.
- Allows to provide custom converter.

## Installation

```bash
composer require AlexSkrypnyk/CsvTable
```    

## Usage

```php
$csvTable = new CsvTable();
$csvTable->toMarkdown()
```

## Maintenance

```bash
composer install
composer lint
composer test
```
