<?php

declare(strict_types = 1);

namespace AlexSkrypnyk\CsvTable;

/**
 * Class CsvTable.
 *
 * Represents and manipulates CSV data.
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
   * Options.
   *
   * @var array<mixed>
   */
  protected array $options = [];

  /**
   * Number of columns.
   *
   * @var int
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
   * @param array<mixed> $options
   *   Options.
   */
  public function __construct(array $header, array $rows, array $options = []) {
    $this->header = array_map([self::class, 'processValue'], $header);

    $this->rows = array_map(function ($row) {
      return array_map([self::class, 'processValue'], $row);
    }, $rows);
    $this->options = $options + ['column_separator' => '|'];

    // Assume that all rows and the header have the same number of columns.
    $this->colCount = count($this->rows) > 0 ? count($this->rows[0]) : 0;
    $this->colWidths = $this->getColWidths(array_merge([$this->header], $this->rows));
  }

  /**
   * Render markdown output.
   *
   * @param string[] $header
   *   Header.
   * @param array<string[]> $rows
   *   Rows.
   * @param array<mixed> $options
   *   Options.
   *
   * @return string
   *   Markdown output.
   */
  public static function render(array $header, array $rows, array $options): string {
    /* @phpstan-ignore-next-line */
    return (new static($header, $rows, $options))->renderTable();
  }

  /**
   * Render Markdown table.
   *
   * @return string
   *   Markdown table.
   */
  public function renderTable(): string {
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
    $output = $this->options['column_separator'] . ' ';

    for ($i = 0; $i < $this->colCount - 1; ++$i) {
      $output .= str_pad($row[$i], $this->colWidths[$i]);
      $output .= ' ' . $this->options['column_separator'] . ' ';
    }

    $output .= str_pad($row[$this->colCount - 1], $this->colWidths[$this->colCount - 1]);
    $output .= ' ' . $this->options['column_separator'] . "\n";

    return $output;
  }

  /**
   * Create header separator.
   */
  protected function createHeaderSeparator(): string {
    $output = '';

    $output .= $this->options['column_separator'];

    for ($i = 0; $i < $this->colCount - 1; $i++) {
      $output .= str_repeat('-', $this->colWidths[$i] + 2);
      $output .= $this->options['column_separator'];
    }

    $last_index = $this->colCount - 1;
    $output .= str_repeat('-', $this->colWidths[$last_index] + 2);

    $output .= $this->options['column_separator'];

    return $output . "\n";
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
    return (string) preg_replace('/(\r\n|\n|\r)/', '<br />', $value);
  }

  /**
   * Get the maximum width of each column.
   *
   * @param array<string[]> $rows
   *   Rows.
   *
   * @return array<int>
   *   Col Widths.
   */
  protected function getColWidths(array $rows): array {
    $widths = array_fill(0, $this->colCount, 0);

    foreach ($rows as $cols) {
      foreach ($cols as $k => $v) {
        $widths[$k] = max($widths[$k], strlen($v));
      }
    }

    return $widths;
  }

}
