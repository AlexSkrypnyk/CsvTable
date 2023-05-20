<?php

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
   * @var array
   */
  protected $header;

  /**
   * The rows.
   *
   * @var array
   */
  protected $rows;

  /**
   * Options.
   *
   * @var array
   */
  protected $options = [];

  /**
   * Number of columns.
   *
   * @var int
   */
  protected $colCount = 0;

  /**
   * Column widths.
   *
   * @var array
   */
  protected $colWidths = [];

  /**
   * Markdown constructor.
   *
   * @param array $header
   *   The header row.
   * @param array $rows
   *   The rows.
   * @param array $options
   *   Options.
   */
  public function __construct($header, $rows, $options = []) {
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
   */
  public static function render($header, $rows, $options) {
    return (new static($header, $rows, $options))->renderTable();
  }

  /**
   * Render Markdown table.
   *
   * @return string
   *   Markdown table.
   */
  public function renderTable() {
    return count($this->header) > 0
      ? $this->createRow($this->header) . $this->createHeaderSeparator() . $this->createRows($this->rows)
      : $this->createRows($this->rows);
  }

  /**
   * Create a row.
   */
  protected function createRow($row) {
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
  protected function createHeaderSeparator() {
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
   */
  protected function createRows($rows) {
    $output = '';

    foreach ($rows as $row) {
      $output .= $this->createRow($row);
    }

    return $output;
  }

  /**
   * Process value.
   */
  protected function processValue($value) {
    return preg_replace('/(\r\n|\n|\r)/', '<br />', $value);
  }

  /**
   * Get the maximum width of each column.
   */
  protected function getColWidths($rows) {
    $widths = array_fill(0, $this->colCount, 0);

    foreach ($rows as $cols) {
      foreach ($cols as $k => $v) {
        $widths[$k] = max($widths[$k], strlen($v));
      }
    }

    return $widths;
  }

}
