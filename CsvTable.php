<?php

namespace AlexSkrypnyk\CsvTable;

/**
 * Class CsvTable.
 *
 * Represents and manipulates CSV data.
 */
class CsvTable {

  /**
   * The CSV data as a string.
   *
   * @var string
   */
  protected $csvString;

  /**
   * The character used to separate values in the CSV data.
   *
   * @var string
   */
  protected $csvSeparator;

  /**
   * The character used to enclose values in the CSV data.
   *
   * @var string
   */
  protected $csvEnclosure;

  /**
   * The character used to escape special characters in the CSV data.
   *
   * @var string
   */
  protected $csvEscape;

  /**
   * Array containing the header row from the CSV data.
   *
   * @var array
   */
  protected $header = [];

  /**
   * Array containing all non-header rows from the CSV data.
   *
   * @var array
   */
  protected $rows = [];

  /**
   * Boolean flag indicating whether the header should be parsed.
   *
   * @var bool
   */
  protected $useHeader = TRUE;

  /**
   * Constructs a CsvTable object.
   *
   * @param string|null $csvString
   *   The CSV data as a string. Defaults to NULL.
   * @param string $separator
   *   The character used to separate values in the CSV data. Defaults to ','.
   * @param string $enclosure
   *   The character used to enclose values in the CSV data. Defaults to '"'.
   * @param string $escape
   *   The character used to escape special characters in the CSV data.
   *   Defaults to '\\'.
   */
  public function __construct($csvString = NULL, $separator = ',', $enclosure = '"', $escape = '\\') {
    $this->csvSeparator = $separator;
    $this->csvEnclosure = $enclosure;
    $this->csvEscape = $escape;

    if ($csvString) {
      $this->csvString = $csvString;
    }

    $this->parse();
  }

  /**
   * Get header columns.
   *
   * @return array
   *   Array of header columns.
   */
  public function getHeader(): array {
    return $this->header;
  }

  /**
   * Get rows without the header.
   *
   * @return array
   *   Array of rows without the header.
   */
  public function getRows(): array {
    return $this->rows;
  }

  /**
   * Indicates that the CSV data has a header row.
   *
   * @return $this
   */
  public function hasHeader() {
    $this->useHeader = TRUE;
    $this->parse();
    return $this;
  }

  /**
   * Indicates that the CSV data does not have a header row.
   *
   * @return $this
   */
  public function noHeader() {
    $this->useHeader = FALSE;
    $this->parse();
    return $this;
  }

  /**
   * Create a CsvTable object from a file.
   *
   * @param string $filepath
   *   Path to the CSV file.
   * @param string $separator
   *   The character used to separate values in the CSV data. Defaults to ','.
   * @param string $enclosure
   *   The character used to enclose values in the CSV data. Defaults to '"'.
   * @param string $escape
   *   The character used to escape special characters in the CSV data.
   *   Defaults to '\\'.
   *
   * @return CsvTable
   *   A new CsvTable object containing the contents of the file.
   *
   * @throws Exception
   *   When the file is not readable.
   */
  public static function fromFile($filepath, $separator = ',', $enclosure = '"', $escape = '\\') {
    if (!is_readable($filepath)) {
      throw new \Exception('File not readable');
    }

    return new static(file_get_contents($filepath), $separator, $enclosure, $escape);
  }

  /**
   * Render the CSV data.
   *
   * @param callable|null $formatter
   *   A callable to format the output. Defaults to NULL, which uses the default
   *   formatter.
   * @param array $formatter_options
   *   An array of options to pass to the formatter. Defaults to an empty array.
   *
   * @return string
   *   The formatted output.
   *
   * @throws Exception
   *   When the formatter is not callable.
   */
  public function render(callable $formatter = NULL, array $formatter_options = []): string {
    $formatter = $formatter ?: [$this, 'renderCsv'];

    if (!is_callable($formatter)) {
      throw new \Exception('Formatter must be callable');
    }

    $formatter_options += [
      'separator' => $this->csvSeparator,
      'enclosure' => $this->csvEnclosure,
      'escape' => $this->csvEscape,
    ];

    return call_user_func($formatter, $this->header, $this->rows, $formatter_options);
  }

  /**
   * Parse the CSV string into header and rows.
   */
  protected function parse() {
    $rows = [];

    if (!empty($this->csvString)) {
      $stream = fopen('php://memory', 'r+');
      fwrite($stream, $this->csvString);
      rewind($stream);

      while (($data = fgetcsv($stream, 0, $this->csvSeparator, $this->csvEnclosure, $this->csvEscape)) !== FALSE) {
        $rows[] = $data;
      }

      fclose($stream);
    }

    $this->header = $this->useHeader && count($rows) > 0 ? array_slice($rows, 0, 1)[0] : [];
    $this->rows = $this->useHeader && count($rows) > 0 ? array_slice($rows, 1) : $rows;
  }

  /**
   * Render as CSV.
   *
   * @param array $header
   *   An array containing the header row.
   * @param array $rows
   *   An array containing all non-header rows.
   * @param array $options
   *   An array of options for the renderer.
   *
   * @return string
   *   The formatted output.
   */
  public static function renderCsv($header, $rows, $options): string {
    $out = fopen('php://temp/maxmemory:' . (5 * 1024 * 1024), 'r+');

    if (count($header) > 0) {
      fputcsv($out, $header, $options['separator'], $options['enclosure'], $options['escape']);
    }
    foreach ($rows as $row) {
      fputcsv($out, $row, $options['separator'], $options['enclosure'], $options['escape']);
    }

    rewind($out);
    $output = stream_get_contents($out);
    fclose($out);

    return $output;
  }

  /**
   * Render as text table.
   *
   * @param array $header
   *   An array containing the header row.
   * @param array $rows
   *   An array containing all non-header rows.
   * @param array $options
   *   An array of options for the renderer.
   *
   * @return string
   *   The formatted output.
   */
  public static function renderTextTable($header, $rows, $options): string {
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
  }

}
