<?php

declare(strict_types=1);

namespace AlexSkrypnyk\CsvTable;

/**
 * Class Markdown.
 *
 * Renders CSV data as a Markdown table.
 */
class Markdown {

  /**
   * The header row.
   *
   * @var string[]
   */
  protected array $header;

  /**
   * The rows.
   *
   * @var array<string[]>
   */
  protected array $rows;

  /**
   * Column separator.
   */
  protected string $columnSeparator = '|';

  /**
   * Row separator.
   */
  protected string $rowSeparator = "\n";

  /**
   * Value row separator.
   */
  protected string $valueRowSeparator = '<br/>';

  /**
   * Number of columns.
   */
  protected int $colCount = 0;

  /**
   * Column widths.
   *
   * @var array<int>
   */
  protected array $colWidths = [];

  /**
   * Markdown constructor.
   *
   * @param string[] $header
   *   The header row.
   * @param array<string[]> $rows
   *   The rows.
   * @param array<string,string> $options
   *   Additional options with keys:
   *   - column_separator: Column separator.
   *   - row_separator: Row separator.
   *   - value_row_separator: Value row separator.
   */
  final public function __construct(array $header, array $rows, array $options = []) {
    $this->columnSeparator = $options['column_separator'] ?? $this->columnSeparator;
    $this->rowSeparator = $options['row_separator'] ?? $this->rowSeparator;
    $this->valueRowSeparator = $options['value_row_separator'] ?? $this->valueRowSeparator;

    $this->header = array_map(function ($col): string {
      return $this->processValue($col);
    }, $header);

    $this->rows = array_map(function ($row): array {
      return array_map(function ($col): string {
        return $this->processValue($col);
      }, $row);
    }, $rows);

    // Assume that all rows and the header have the same number of columns.
    $this->colCount = count($this->rows) > 0 ? count($this->rows[0]) : 0;
    $this->colWidths = $this->calcColWidths(array_merge([$this->header], $this->rows));
  }

  /**
   * Render markdown output.
   *
   * @param string[] $header
   *   Header.
   * @param array<string[]> $rows
   *   Rows.
   * @param array<string,string> $options
   *   Options.
   *
   * @return string
   *   Markdown output.
   */
  public static function render(array $header, array $rows, array $options): string {
    return (new static($header, $rows, $options))->doRender();
  }

  /**
   * Render Markdown table.
   *
   * @return string
   *   Markdown table.
   */
  public function doRender(): string {
    return count($this->header) > 0
      ? $this->createRow($this->header) . $this->createHeaderSeparator() . $this->createRows($this->rows)
      : $this->createRows($this->rows);
  }

  /**
   * Create a row.
   *
   * @param string[] $row
   *   Row.
   *
   * @return string
   *   Row as string.
   */
  protected function createRow(array $row): string {
    $output = $this->columnSeparator . ' ';

    for ($i = 0; $i < $this->colCount - 1; ++$i) {
      $output .= str_pad($row[$i], $this->colWidths[$i]);
      $output .= ' ' . $this->columnSeparator . ' ';
    }

    $output .= str_pad($row[$this->colCount - 1], $this->colWidths[$this->colCount - 1]);

    return $output . (' ' . $this->columnSeparator . $this->rowSeparator);
  }

  /**
   * Create header separator.
   */
  protected function createHeaderSeparator(): string {
    $output = '';

    $output .= $this->columnSeparator;

    for ($i = 0; $i < $this->colCount - 1; $i++) {
      $output .= str_repeat('-', $this->colWidths[$i] + 2);
      $output .= $this->columnSeparator;
    }

    $last_index = $this->colCount - 1;
    $output .= str_repeat('-', $this->colWidths[$last_index] + 2);

    $output .= $this->columnSeparator;

    return $output . $this->rowSeparator;
  }

  /**
   * Create rows.
   *
   * @param array<string[]> $rows
   *   Rows.
   *
   * @return string
   *   Rows as string.
   */
  protected function createRows(array $rows): string {
    $output = '';

    foreach ($rows as $row) {
      $output .= $this->createRow($row);
    }

    return $output;
  }

  /**
   * Process value.
   */
  protected function processValue(string $value): string {
    return (string) preg_replace('/(\r\n|\n|\r)/', $this->valueRowSeparator, $value);
  }

  /**
   * Calculate widths for each column.
   *
   * @param array<string[]> $rows
   *   Rows.
   *
   * @return array<int>
   *   Calculated widths for each column.
   */
  protected function calcColWidths(array $rows): array {
    $widths = array_fill(0, $this->colCount, 0);

    foreach ($rows as $cols) {
      foreach ($cols as $k => $v) {
        $widths[$k] = max($widths[$k], strlen($v));
      }
    }

    return $widths;
  }

}
