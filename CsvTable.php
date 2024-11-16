<?php

declare(strict_types=1);

namespace AlexSkrypnyk\CsvTable;

/**
 * Class CsvTable.
 *
 * Manipulates CSV data and renders it in various formats.
 * Implemented as a single class for portability.
 *
 * By default, the CSV data is parsed with a header row and rendered as a table.
 *
 * Custom renderers can be used to render the CSV data in different formats.
 */
class CsvTable {

  /**
   * Array containing the header row from the CSV data.
   *
   * @var array<string>
   */
  protected array $header = [];

  /**
   * Array containing all non-header rows from the CSV data.
   *
   * @var array<array<string>>
   */
  protected array $rows = [];

  /**
   * Set to TRUE if the CSV data should be parsed with a header row.
   */
  protected bool $shouldParseHeader = TRUE;

  /**
   * Constructs a CsvTable object.
   *
   * @param string $csvString
   *   The CSV data as a string.
   * @param string $csvSeparator
   *   The character used to separate values in the CSV data. Defaults to ','.
   * @param string $csvEnclosure
   *   The character used to enclose values in the CSV data. Defaults to '"'.
   * @param string $csvEscape
   *   The character used to escape special characters in the CSV data.
   *   Defaults to '\\'.
   */
  final public function __construct(
    protected string $csvString,
    protected string $csvSeparator = ',',
    protected string $csvEnclosure = '"',
    protected string $csvEscape = '\\',
  ) {
  }

  /**
   * Get header columns.
   *
   * @return array<string>
   *   Array of header columns.
   */
  public function getHeader(): array {
    return $this->header;
  }

  /**
   * Get rows without the header.
   *
   * @return array<array<string>>
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
  public function withHeader(): static {
    $this->shouldParseHeader = TRUE;

    return $this;
  }

  /**
   * Indicates that the CSV data does not have a header row.
   *
   * @return $this
   */
  public function withoutHeader(): static {
    $this->shouldParseHeader = FALSE;

    return $this;
  }

  /**
   * Parse the CSV string into header and rows.
   */
  public function parse(): void {
    $rows = [];

    $stream = fopen('php://memory', 'r+');

    if (!$stream) {
      // @codeCoverageIgnoreStart
      throw new \Exception('Unable to open memory stream.');
      // @codeCoverageIgnoreEnd
    }

    fwrite($stream, $this->csvString);
    rewind($stream);
    while (($data = fgetcsv($stream, 0, $this->csvSeparator, $this->csvEnclosure, $this->csvEscape)) !== FALSE) {
      $rows[] = $data;
    }
    fclose($stream);

    $this->header = $this->shouldParseHeader && count($rows) > 0 ? array_slice($rows, 0, 1)[0] : [];
    $this->rows = $this->shouldParseHeader && count($rows) > 0 ? array_slice($rows, 1) : $rows;
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
   * @throws \Exception
   *   When the file is not readable.
   */
  public static function fromFile($filepath, $separator = ',', $enclosure = '"', $escape = '\\'): CsvTable {
    if (!is_readable($filepath)) {
      throw new \Exception(sprintf('Unable to read the file %s.', $filepath));
    }

    $content = file_get_contents($filepath);

    if ($content === FALSE) {
      // @codeCoverageIgnoreStart
      throw new \Exception(sprintf('Unable to read the file %s.', $filepath));
      // @codeCoverageIgnoreEnd
    }

    return new static($content, $separator, $enclosure, $escape);
  }

  /**
   * Render the CSV data.
   *
   * @param callable|string|null $renderer
   *   A callable to renderer the output. Can be a function name, a class name,
   *   a closure, or an array containing a class name and a method name. If NULL
   *   is provided, the default renderer will be used.
   * @param array<mixed> $options
   *   An array of options to pass to the renderer. Defaults to an empty array.
   *
   * @return string
   *   The rendered output.
   *
   * @throws \Exception
   *   When the renderer is not callable.
   */
  public function render(callable|string|null $renderer = NULL, array $options = []): string {
    $renderer = $renderer ?? [static::class, 'renderCsv'];
    $renderer = is_string($renderer) && class_exists($renderer) ? [$renderer, 'render'] : $renderer;

    if (!is_callable($renderer)) {
      throw new \Exception('Renderer must be callable.');
    }

    $this->parse();

    return call_user_func($renderer, $this->header, $this->rows, $options);
  }

  /**
   * Render as CSV.
   *
   * @param array<string> $header
   *   An array containing the header row.
   * @param array<array<string>> $rows
   *   An array containing all non-header rows.
   * @param array<string,string> $options
   *   An array of options for the renderer.
   *
   * @return string
   *   The formatted output.
   */
  public static function renderCsv(array $header, array $rows, array $options): string {
    $options += [
      'separator' => ',',
      'enclosure' => '"',
      'escape' => '\\',
    ];

    $stream = fopen('php://temp/maxmemory:' . (5 * 1024 * 1024), 'r+');
    if (!$stream) {
      // @codeCoverageIgnoreStart
      throw new \Exception('Unable to open temporary memory stream.');
      // @codeCoverageIgnoreEnd
    }

    if (count($header) > 0) {
      fputcsv($stream, $header, $options['separator'], $options['enclosure'], $options['escape']);
    }

    foreach ($rows as $row) {
      fputcsv($stream, $row, $options['separator'], $options['enclosure'], $options['escape']);
    }

    rewind($stream);
    $output = (string) stream_get_contents($stream);
    fclose($stream);

    return $output;
  }

  /**
   * Render as a table.
   *
   * @param array<string> $header
   *   An array containing the header row.
   * @param array<array<string>> $rows
   *   An array containing all non-header rows.
   * @param array<string,string> $options
   *   An array of options for the renderer.
   *
   * @return string
   *   The formatted output.
   */
  public static function renderTable(array $header, array $rows, array $options): string {
    $output = '';

    $options += [
      'column_separator' => '|',
      'row_separator' => "\n",
    ];

    if (count($header) > 0) {
      $output = implode($options['column_separator'], $header) . $options['row_separator'];
      $output .= str_repeat('-', strlen($output) - strlen($options['row_separator'])) . $options['row_separator'];
    }

    return $output . implode($options['row_separator'], array_map(static function ($row) use ($options): string {
      return implode($options['column_separator'], $row);
    }, $rows));
  }

}
