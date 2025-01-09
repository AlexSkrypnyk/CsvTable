<?php

declare(strict_types=1);

namespace AlexSkrypnyk\CsvTable\Tests;

use AlexSkrypnyk\CsvTable\CsvTable;
use PHPUnit\Framework\TestCase;

/**
 * Class CsvTableUnitTest.
 *
 * Unit tests for CsvTable and default formatters.
 *
 * @covers \AlexSkrypnyk\CsvTable\CsvTable
 */
class CsvTableUnitTest extends TestCase {

  /**
   * Fixture CSV.
   *
   * @return string
   *   CSV string.
   */
  protected static function fixtureCsv(): string {
    return <<< EOD
    col11,col12,col13
    col21,col22,col23
    col31,col32,col33
    
    EOD;
  }

  /**
   * Test getters.
   *
   * @covers \AlexSkrypnyk\CsvTable\CsvTable::getRows
   * @covers \AlexSkrypnyk\CsvTable\CsvTable::getHeader
   */
  public function testGetters(): void {
    $csv = self::fixtureCsv();

    $table = new CsvTable($csv);
    $table->parse();

    $this->assertEquals(['col11', 'col12', 'col13'], $table->getHeader());
    $this->assertEquals([
      ['col21', 'col22', 'col23'],
      ['col31', 'col32', 'col33'],
    ], $table->getRows());

    $table = new CsvTable($csv);
    $table->withoutHeader();
    $table->parse();

    $this->assertEquals([], $table->getHeader());
    $this->assertEquals([
      ['col11', 'col12', 'col13'],
      ['col21', 'col22', 'col23'],
      ['col31', 'col32', 'col33'],
    ], $table->getRows());
  }

  /**
   * Test creating of the class instance using fromFile().
   */
  public function testFromFile(): void {
    $csv = self::fixtureCsv();
    $file = tempnam(sys_get_temp_dir(), 'csv');
    file_put_contents((string) $file, $csv);

    $actual = (CsvTable::fromFile((string) $file))->format();
    $this->assertEquals($csv, $actual);

    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('Unable to read the file non-existing-file.csv');
    CsvTable::fromFile('non-existing-file.csv');
  }

  /**
   * Test the default behavior using default formatCsv() formatter.
   *
   * @dataProvider dataProviderFormatterDefault
   */
  public function testFormatterDefault(string $csv, bool|null $with_header, string $expected): void {
    $table = new CsvTable($csv);

    // Allows to assert default behavior.
    if (!is_null($with_header)) {
      if ($with_header) {
        $table->withHeader();
      }
      else {
        $table->withoutHeader();
      }
    }

    $actual = $table->format();

    $this->assertEquals($expected, $actual);
  }

  /**
   * Data provider for testFormatterDefault().
   *
   * @return array<mixed>
   *   Data provider
   */
  public static function dataProviderFormatterDefault(): array {
    return [
      ['', NULL, ''],
      ['', TRUE, ''],
      ['', FALSE, ''],

      [self::fixtureCsv(), NULL, self::fixtureCsv()],
      [self::fixtureCsv(), TRUE, self::fixtureCsv()],
      [self::fixtureCsv(), FALSE, self::fixtureCsv()],
    ];
  }

  /**
   * Test table formatter.
   */
  public function testFormatterTable(): void {
    $csv = self::fixtureCsv();

    $actual = (new CsvTable($csv))->format('table');

    $this->assertEquals(<<< EOD
    col11|col12|col13
    -----------------
    col21|col22|col23
    col31|col32|col33
    EOD, $actual);

    $actual = (new CsvTable($csv))->withoutHeader()->format('table');
    $this->assertEquals(<<< EOD
    col11|col12|col13
    col21|col22|col23
    col31|col32|col33
    EOD, $actual);
  }

  /**
   * Test custom CSV separator.
   */
  public function testFormatterCsvSeparator(): void {
    $csv = self::fixtureCsv();
    $csv_updated = str_replace(',', ';', self::fixtureCsv());

    // Custom separator for parsing, default for formating.
    $actual = (new CsvTable($csv_updated, ';'))->format();
    $this->assertEquals($csv, $actual);

    // Custom separator for parsing and formating.
    $actual = (new CsvTable($csv_updated, ';'))->format(NULL, ['separator' => ';']);
    $this->assertEquals($csv_updated, $actual);
  }

  /**
   * Test support for CSV multiline.
   */
  public function testFormatterCsvMultiline(): void {
    $csv = <<< EOD
    col11,col12,col13
    col21,"col22\ncol22secondline",col23

    EOD;
    $actual = (new CsvTable($csv))->format();
    $this->assertEquals($csv, $actual);
  }

  /**
   * Test formatMarkdownTable().
   */
  public function testFormatterMarkdownTable(): void {
    $csv = <<< EOD
    col11a,col12ab,col13abc
    col21a,"col22ab cde",col23abc
    col31a,col32ab,"col33abcd"
    EOD;

    $actual = (new CsvTable($csv))->format('markdown_table');

    $this->assertEquals(<<< EOD
    | col11a | col12ab     | col13abc  |
    |--------|-------------|-----------|
    | col21a | col22ab cde | col23abc  |
    | col31a | col32ab     | col33abcd |
    
    EOD, $actual);
  }

  /**
   * Test Markdown table formatter for multiline.
   */
  public function testFormatterMarkdownTableMultiline(): void {
    $csv = <<< EOD
    col11a,col12ab,col13abc
    col21a,"col22ab\ncdef",col23abc
    col31a,col32ab,col33abcd
    EOD;

    $actual = (new CsvTable($csv))->format('markdown_table');

    $this->assertEquals(<<< EOD
    | col11a | col12ab          | col13abc  |
    |--------|------------------|-----------|
    | col21a | col22ab<br/>cdef | col23abc  |
    | col31a | col32ab          | col33abcd |
    
    EOD, $actual);
  }

  /**
   * Test Markdown table formatter without header.
   */
  public function testFormatterMarkdownTableMultilineNoHeader(): void {
    $csv = <<< EOD
    col11a,col12ab,col13abc
    col21a,"col22ab\ncdef",col23abc
    col31a,col32ab,col33abcd
    EOD;

    $actual = (new CsvTable($csv))->withoutHeader()->format('markdown_table');

    $this->assertEquals(<<< EOD
    | col11a | col12ab          | col13abc  |
    | col21a | col22ab<br/>cdef | col23abc  |
    | col31a | col32ab          | col33abcd |
    
    EOD, $actual);
  }

  /**
   * Test Markdown table formatter with custom separators.
   */
  public function testFormatterMarkdownTableCustomSeparators(): void {
    $csv = <<< EOD
    col11a,col12ab,col13abc
    col21a,"col22ab cde",col23abc
    col31a,col32ab,"col33abcd"
    EOD;

    $actual = (new CsvTable($csv))->format('markdown_table', [
      'column_separator' => '|',
      'row_separator' => "\n",
      'header_separator' => '=',
    ]);

    $this->assertEquals(<<< EOD
    | col11a | col12ab     | col13abc  |
    |========|=============|===========|
    | col21a | col22ab cde | col23abc  |
    | col31a | col32ab     | col33abcd |
    
    EOD, $actual);
  }

  /**
   * Test pass not callable to format().
   */
  public function testFormatterCustomNotCallable(): void {
    $csv = self::fixtureCsv();

    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('Formatter must be callable.');
    (new CsvTable($csv))->format('Not callable');
  }

  /**
   * Test using a custom formatter function.
   */
  public function testFormatterCustomFunction(): void {
    $csv = self::fixtureCsv();

    $custom_formatter = static function ($header, $rows): string {
      $output = '';

      if (count($header) > 0) {
        $output = implode('|', $header);
        $output .= "\n" . str_repeat('=', strlen($output)) . "\n";
      }

      return $output . implode("\n", array_map(static function ($row): string {
          return implode('|', $row);
      }, $rows));
    };

    $actual = (new CsvTable($csv))->format($custom_formatter);

    $this->assertEquals(<<< EOD
    col11|col12|col13
    =================
    col21|col22|col23
    col31|col32|col33
    EOD, $actual);
  }

  /**
   * Test using a custom formatter class with default callback.
   */
  public function testFormatterCustomClassDefaultCallback(): void {
    $csv = self::fixtureCsv();

    $actual = (new CsvTable($csv))->format(TestFormatter::class);

    $this->assertEquals(<<< EOD
    col11|col12|col13
    =================
    col21|col22|col23
    col31|col32|col33
    EOD, $actual);
  }

  /**
   * Test using a custom formatter class with custom callback.
   */
  public function testFormatterCustomClassCustomCallback(): void {
    $csv = self::fixtureCsv();

    $actual = (new CsvTable($csv))->format([TestFormatter::class, 'customFormat']);

    $this->assertEquals(<<< EOD
    col11!col12!col13
    =================
    col21!col22!col23
    col31!col32!col33
    EOD, $actual);
  }

}
