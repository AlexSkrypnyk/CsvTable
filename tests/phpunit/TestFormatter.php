<?php

declare(strict_types=1);

namespace AlexSkrypnyk\CsvTable\Tests;

/**
 * Formatter used in tests.
 */
class TestFormatter {

  /**
   * Format a table.
   *
   * @param array<int, string> $header
   *   The header.
   * @param array<int, array<int, string>> $rows
   *   The rows.
   * @param array<string, string> $options
   *   The options.
   *
   * @return string
   *   The formatted table.
   */
  public static function format(array $header, array $rows, array $options = []): string {
    $output = '';

    $options += [
      'delimiter' => '|',
    ];

    if (count($header) > 0) {
      $output = implode($options['delimiter'], $header);
      $output .= "\n" . str_repeat('=', strlen($output)) . "\n";
    }

    return $output . implode("\n", array_map(static function (array $row) use ($options): string {
        return implode($options['delimiter'], $row);
    }, $rows));
  }

  /**
   * Format a table with a custom delimiter.
   *
   * @param array<int, string> $header
   *   The header.
   * @param array<int, array<int, string>> $rows
   *   The rows.
   *
   * @return string
   *   The formatted table.
   */
  public static function customFormat(array $header, array $rows): string {
    return static::format($header, $rows, ['delimiter' => '!']);
  }

}
