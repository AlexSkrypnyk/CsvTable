<?php

declare(strict_types=1);

namespace AlexSkrypnyk\CsvTable;

/**
 * Class CsvTable.
 *
 * Manipulates CSV data and outputs it in various formats.
 * Implemented as a single class for portability.
 *
 * By default, the CSV data is parsed with a header row and formated as a table.
 *
 * A custom formatter allows the parsed CSV data to be output in an alternative
 * format.
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

    $this->header = $this->shouldParseHeader && count($rows) > 0
      ? array_map(fn($value): string => $value ?? '', array_slice($rows, 0, 1)[0])
      : [];

    $this->rows = $this->shouldParseHeader && count($rows) > 0
      ? array_map(
        fn($row): array => array_map(fn($value): string => $value ?? '', $row),
        array_slice($rows, 1)
      )
      : array_map(
        fn($row): array => array_map(fn($value): string => $value ?? '', $row),
        $rows
      );
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
   * Format the CSV data.
   *
   * @param string|array<int, string>|callable|null $formatter
   *   Formatter to use. Can be a function name, a class name, a closure, an
   *   array containing a class name and a method name, or a predefined
   *   formatter available as format<Name> method. If NULL is provided, the
   *   'CSV" formatter will be used.
   * @param array<mixed> $options
   *   An array of options to pass to the formatter. Defaults to an empty array.
   *
   * @return string
   *   The formated output.
   *
   * @throws \Exception
   *   When the formatter is not callable.
   */
  public function format(string|array|callable|null $formatter = NULL, array $options = []): string {
    $formatter ??= 'csv';

    if (is_string($formatter) && !function_exists($formatter)) {
      if (class_exists($formatter)) {
        $formatter = [$formatter, 'format'];
      }
      else {
        $method = 'format' . str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', strtolower($formatter))));
        $formatter = method_exists($this, $method) ? [$this, $method] : NULL;
      }
    }

    if (!is_callable($formatter)) {
      throw new \Exception('Formatter must be callable.');
    }

    $this->parse();

    return call_user_func($formatter, $this->header, $this->rows, $options);
  }

  /**
   * Format as CSV.
   *
   * @param array<string> $header
   *   An array containing the header row.
   * @param array<array<string>> $rows
   *   An array containing all non-header rows.
   * @param array<string,string> $options
   *   An array of options for the formatter.
   *
   * @return string
   *   The formatted output.
   */
  public static function formatCsv(array $header, array $rows, array $options): string {
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
   * Format as a table.
   *
   * @param array<string> $header
   *   An array containing the header row.
   * @param array<array<string>> $rows
   *   An array containing all non-header rows.
   * @param array<string,string> $options
   *   An array of options for the formatter.
   *
   * @return string
   *   The formatted output.
   */
  public static function formatTable(array $header, array $rows, array $options): string {
    $output = '';

    $options += [
      'column_separator' => '|',
      'row_separator' => "\n",
    ];

    if (count($header) > 0) {
      $output = implode($options['column_separator'], $header) . $options['row_separator'];
      $output .= str_repeat('-', strlen($output) - strlen($options['row_separator'])) . $options['row_separator'];
    }

    return $output . implode($options['row_separator'], array_map(static fn(array $row): string => implode($options['column_separator'], $row), $rows));
  }

  /**
   * Format as a Markdown table.
   *
   * @param array<string> $header
   *   An array containing the header row.
   * @param array<array<string>> $rows
   *   An array containing all non-header rows.
   * @param array<string,string> $options
   *   An array of options for the formatter.
   *
   * @return string
   *   The formatted output.
   */
  public static function formatMarkdownTable(array $header, array $rows, array $options): string {
    $output = '';

    $options += [
      'column_separator' => '|',
      'row_separator' => "\n",
      'header_separator' => '-',
      'value_row_separator' => "<br/>",
    ];

    $process_value = (fn(string $value): string => (string) preg_replace('/(\r\n|\n|\r)/', $options['value_row_separator'], $value));

    $create_row = function (array $row, $cols_widths) use ($options): string {
      // Pad row with empty strings to match the number of columns.
      $row = array_pad($row, count($cols_widths), '');

      $output = array_map(
        str_pad(...),
        $row,
        $cols_widths
      );

      return $options['column_separator'] . ' ' . implode(' ' . $options['column_separator'] . ' ', $output)
        . ' ' . $options['column_separator'] . $options['row_separator'];
    };

    $create_header_separator = function ($cols_widths) use ($options): string {
      $output = $options['column_separator'];

      for ($i = 0; $i < count($cols_widths) - 1; $i++) {
        $output .= str_repeat($options['header_separator'], $cols_widths[$i] + 2);
        $output .= $options['column_separator'];
      }

      return $output . str_repeat($options['header_separator'], $cols_widths[count($cols_widths) - 1] + 2) . $options['column_separator'] . $options['row_separator'];
    };

    $header = array_map(fn(string $col): string => $process_value($col), $header);

    $rows = array_map(fn(array $row): array => array_map(fn(string $col): string => $process_value($col), $row), $rows);

    // Calculate max column widths for each column.
    $all_rows = count($header) > 0 ? array_merge([$header], $rows) : $rows;
    $widths = [];

    if (count($all_rows) > 0) {
      // Find the maximum number of columns across all rows.
      $max_columns = max(array_map(count(...), $all_rows));

      // Calculate width for each column.
      for ($i = 0; $i < $max_columns; $i++) {
        $max_width = 0;
        foreach ($all_rows as $row) {
          if (isset($row[$i])) {
            $max_width = max($max_width, strlen($row[$i]));
          }
        }
        $widths[] = $max_width;
      }
    }

    if (count($header) > 0) {
      $output .= $create_row($header, $widths);
      $output .= $create_header_separator($widths);
    }

    return $output . array_reduce($rows, fn($carry, $row): string => $carry . $create_row($row, $widths), '');
  }

}
