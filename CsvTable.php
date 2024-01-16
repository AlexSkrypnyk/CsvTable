<?php

declare(strict_types = 1);

namespace AlexSkrypnyk\CsvTable;

/**
 * Class CsvTable.
 *
 * Represents and manipulates CSV data.
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
   * Boolean flag indicating whether the header should be parsed.
   */
  protected bool $useHeader = TRUE;

  /**
   * Constructs a CsvTable object.
   *
   * @param string|null $csvString
   *   The CSV data as a string. Defaults to NULL.
   * @param string $csvSeparator
   *   The character used to separate values in the CSV data. Defaults to ','.
   * @param string $csvEnclosure
   *   The character used to enclose values in the CSV data. Defaults to '"'.
   * @param string $csvEscape
   *   The character used to escape special characters in the CSV data.
   *   Defaults to '\\'.
   */
  public function __construct(protected ?string $csvString = NULL,
                              protected string $csvSeparator = ',',
                              protected string $csvEnclosure = '"',
                              protected string $csvEscape = '\\') {
    $this->parse();
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
  public function hasHeader(): static {
    $this->useHeader = TRUE;
    $this->parse();
    return $this;
  }

  /**
   * Indicates that the CSV data does not have a header row.
   *
   * @return $this
   */
  public function noHeader(): static {
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
   * @throws \Exception
   *   When the file is not readable.
   */
  public static function fromFile($filepath, $separator = ',', $enclosure = '"', $escape = '\\'): CsvTable {
    if (!is_readable($filepath)) {
      throw new \Exception('File not readable');
    }
    /* @phpstan-ignore-next-line */
    return new static(file_get_contents($filepath), $separator, $enclosure, $escape);
  }

  /**
   * Render the CSV data.
   *
   * @param callable|string|null $renderer
   *   A callable to renderer the output. Defaults to NULL, which uses the
   *   default renderer.
   * @param array<mixed> $options
   *   An array of options to pass to the renderer. Defaults to an empty array.
   *
   * @return string
   *   The formatted output.
   *
   * @throws \Exception
   *   When the renderer is not callable.
   */
  public function render(callable|string $renderer = NULL, array $options = []): string {
    $renderer = $renderer
      ? (is_string($renderer) && class_exists($renderer) ? [$renderer, 'render'] : $renderer)
      : $this->renderCsv(...);

    if (!is_callable($renderer)) {
      throw new \Exception('Renderer must be callable');
    }

    $options += [
      'separator' => $this->csvSeparator,
      'enclosure' => $this->csvEnclosure,
      'escape' => $this->csvEscape,
    ];

    return call_user_func($renderer, $this->header, $this->rows, $options);
  }

  /**
   * Parse the CSV string into header and rows.
   */
  protected function parse(): void {
    $rows = [];

    if (!empty($this->csvString)) {
      $stream = fopen('php://memory', 'r+');
      if ($stream) {
        fwrite($stream, $this->csvString);
        rewind($stream);
        while (($data = fgetcsv($stream, 0, $this->csvSeparator, $this->csvEnclosure, $this->csvEscape)) !== FALSE) {
          $rows[] = $data;
        }
        fclose($stream);
      }
    }

    $this->header = $this->useHeader && count($rows) > 0 ? array_slice($rows, 0, 1)[0] : [];
    $this->rows = $this->useHeader && count($rows) > 0 ? array_slice($rows, 1) : $rows;
  }

  /**
   * Render as CSV.
   *
   * @param array<string> $header
   *   An array containing the header row.
   * @param array<array<string>> $rows
   *   An array containing all non-header rows.
   * @param array<mixed> $options
   *   An array of options for the renderer.
   *
   * @return string
   *   The formatted output.
   */
  public static function renderCsv(array $header, array $rows, array $options): string {
    $output = '';
    $out = fopen('php://temp/maxmemory:' . (5 * 1024 * 1024), 'r+');
    if ($out) {
      if (count($header) > 0) {
        /* @phpstan-ignore-next-line */
        fputcsv($out, $header, (string) $options['separator'], (string) $options['enclosure'], (string) $options['escape']);
      }
      foreach ($rows as $row) {
        /* @phpstan-ignore-next-line */
        fputcsv($out, $row, (string) $options['separator'], (string) $options['enclosure'], (string) $options['escape']);
      }

      rewind($out);
      $output = (string) stream_get_contents($out);
      fclose($out);
    }

    return $output;
  }

  /**
   * Render as a table.
   *
   * @param array<string> $header
   *   An array containing the header row.
   * @param array<array<string>> $rows
   *   An array containing all non-header rows.
   *
   * @return string
   *   The formatted output.
   */
  public static function renderTable(array $header, array $rows): string {
    if (count($header) > 0) {
      $header = implode('|', $header);
      $header = $header . "\n" . str_repeat('-', strlen($header)) . "\n";
    }
    else {
      $header = '';
    }

    return $header . implode("\n", array_map(static function ($row): string {
      return implode('|', $row);
    }, $rows));
  }

}
