<?php

declare(strict_types=1);

namespace AlexSkrypnyk\CsvTable\Tests;

use AlexSkrypnyk\CsvTable\CsvTable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Class CsvTableUnitTest.
 *
 * Unit tests for CsvTable and default formatters.
 */
#[CoversClass(CsvTable::class)]
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
   */
  #[DataProvider('dataProviderFormatterDefault')]
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
   * Test Markdown table formatter with empty CSV and no header.
   */
  public function testFormatterMarkdownTableEmptyNoHeader(): void {
    $csv = '';

    $actual = (new CsvTable($csv))->withoutHeader()->format('markdown_table');

    $this->assertEquals('', $actual);
  }

  /**
   * Test Markdown table formatter with single row and no header.
   */
  public function testFormatterMarkdownTableSingleRowNoHeader(): void {
    $csv = 'col11,col12,col13';

    $actual = (new CsvTable($csv))->withoutHeader()->format('markdown_table');

    $this->assertEquals(<<< EOD
    | col11 | col12 | col13 |

    EOD, $actual);
  }

  /**
   * Test Markdown table formatter with varying column counts.
   */
  public function testFormatterMarkdownTableVaryingColumns(): void {
    $csv = <<< EOD
    col11,col12,col13
    col21,col22
    col31,col32,col33,col34
    EOD;

    $actual = (new CsvTable($csv))->format('markdown_table');

    $this->assertEquals(<<< EOD
    | col11 | col12 | col13 |       |
    |-------|-------|-------|-------|
    | col21 | col22 |       |       |
    | col31 | col32 | col33 | col34 |

    EOD, $actual);
  }

  /**
   * Test Markdown table formatter with varying column counts and no header.
   */
  public function testFormatterMarkdownTableVaryingColumnsNoHeader(): void {
    $csv = <<< EOD
    col11,col12,col13
    col21,col22
    col31,col32,col33,col34
    EOD;

    $actual = (new CsvTable($csv))->withoutHeader()->format('markdown_table');

    $this->assertEquals(<<< EOD
    | col11 | col12 | col13 |       |
    | col21 | col22 |       |       |
    | col31 | col32 | col33 | col34 |

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

      return $output . implode("\n", array_map(static fn($row): string => implode('|', $row), $rows));
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

    $actual = (new CsvTable($csv))->format(TestFormatter::customFormat(...));

    $this->assertEquals(<<< EOD
    col11!col12!col13
    =================
    col21!col22!col23
    col31!col32!col33
    EOD, $actual);
  }

  /**
   * Test columnOrder() with column names.
   */
  public function testColumnOrderWithNames(): void {
    $csv = <<< EOD
    Name,Age,City,Country
    John,30,New York,USA
    Jane,25,London,UK
    EOD;

    $actual = (new CsvTable($csv))->columnOrder(['City', 'Name'])->format();

    $this->assertEquals(<<< EOD
    City,Name,Age,Country
    "New York",John,30,USA
    London,Jane,25,UK

    EOD, $actual);
  }

  /**
   * Test columnOrder() with indices.
   */
  public function testColumnOrderWithIndices(): void {
    $csv = <<< EOD
    Name,Age,City,Country
    John,30,New York,USA
    Jane,25,London,UK
    EOD;

    $actual = (new CsvTable($csv))->columnOrder([2, 0])->format();

    $this->assertEquals(<<< EOD
    City,Name,Age,Country
    "New York",John,30,USA
    London,Jane,25,UK

    EOD, $actual);
  }

  /**
   * Test columnOrder() with mixed names and indices.
   */
  public function testColumnOrderWithMixed(): void {
    $csv = <<< EOD
    Name,Age,City,Country
    John,30,New York,USA
    Jane,25,London,UK
    EOD;

    $actual = (new CsvTable($csv))->columnOrder(['Country', 1])->format();

    $this->assertEquals(<<< EOD
    Country,Age,Name,City
    USA,30,John,"New York"
    UK,25,Jane,London

    EOD, $actual);
  }

  /**
   * Test columnOrder() without header using indices.
   */
  public function testColumnOrderWithoutHeader(): void {
    $csv = <<< EOD
    John,30,New York,USA
    Jane,25,London,UK
    EOD;

    $actual = (new CsvTable($csv))->withoutHeader()->columnOrder([2, 0])->format();

    $this->assertEquals(<<< EOD
    "New York",John,30,USA
    London,Jane,25,UK

    EOD, $actual);
  }

  /**
   * Test onlyColumns() with column names.
   */
  public function testOnlyColumnsWithNames(): void {
    $csv = <<< EOD
    Name,Age,City,Country
    John,30,New York,USA
    Jane,25,London,UK
    EOD;

    $actual = (new CsvTable($csv))->onlyColumns(['City', 'Name'])->format();

    $this->assertEquals(<<< EOD
    City,Name
    "New York",John
    London,Jane

    EOD, $actual);
  }

  /**
   * Test onlyColumns() with indices.
   */
  public function testOnlyColumnsWithIndices(): void {
    $csv = <<< EOD
    Name,Age,City,Country
    John,30,New York,USA
    Jane,25,London,UK
    EOD;

    $actual = (new CsvTable($csv))->onlyColumns([0, 2])->format();

    $this->assertEquals(<<< EOD
    Name,City
    John,"New York"
    Jane,London

    EOD, $actual);
  }

  /**
   * Test withoutColumns() with column names.
   */
  public function testWithoutColumnsWithNames(): void {
    $csv = <<< EOD
    Name,Age,City,Country
    John,30,New York,USA
    Jane,25,London,UK
    EOD;

    $actual = (new CsvTable($csv))->withoutColumns(['Age', 'Country'])->format();

    $this->assertEquals(<<< EOD
    Name,City
    John,"New York"
    Jane,London

    EOD, $actual);
  }

  /**
   * Test withoutColumns() with indices.
   */
  public function testWithoutColumnsWithIndices(): void {
    $csv = <<< EOD
    Name,Age,City,Country
    John,30,New York,USA
    Jane,25,London,UK
    EOD;

    $actual = (new CsvTable($csv))->withoutColumns([1, 3])->format();

    $this->assertEquals(<<< EOD
    Name,City
    John,"New York"
    Jane,London

    EOD, $actual);
  }

  /**
   * Test combined column transformations.
   */
  public function testCombinedColumnTransformations(): void {
    $csv = <<< EOD
    Name,Age,City,Country,Email
    John,30,New York,USA,john@example.com
    Jane,25,London,UK,jane@example.com
    EOD;

    // Exclude Email, then reorder remaining.
    $actual = (new CsvTable($csv))
      ->withoutColumns(['Email'])
      ->columnOrder(['Country', 'City'])
      ->format();

    $this->assertEquals(<<< EOD
    Country,City,Name,Age
    USA,"New York",John,30
    UK,London,Jane,25

    EOD, $actual);
  }

  /**
   * Test onlyColumns combined with columnOrder.
   */
  public function testOnlyColumnsWithColumnOrder(): void {
    $csv = <<< EOD
    Name,Age,City,Country
    John,30,New York,USA
    Jane,25,London,UK
    EOD;

    $actual = (new CsvTable($csv))
      ->onlyColumns(['Name', 'City', 'Country'])
      ->columnOrder(['Country', 'Name'])
      ->format();

    $this->assertEquals(<<< EOD
    Country,Name,City
    USA,John,"New York"
    UK,Jane,London

    EOD, $actual);
  }

  /**
   * Test resetColumnOrder().
   */
  public function testResetColumnOrder(): void {
    $csv = <<< EOD
    Name,Age,City
    John,30,New York
    EOD;

    $table = new CsvTable($csv);
    $table->columnOrder(['City', 'Name']);

    // First format with reorder.
    $actual = $table->format();
    $this->assertEquals(<<< EOD
    City,Name,Age
    "New York",John,30

    EOD, $actual);

    // Reset and format again.
    $actual = $table->resetColumnOrder()->format();
    $this->assertEquals(<<< EOD
    Name,Age,City
    John,30,"New York"

    EOD, $actual);
  }

  /**
   * Test resetOnlyColumns().
   */
  public function testResetOnlyColumns(): void {
    $csv = <<< EOD
    Name,Age,City
    John,30,New York
    EOD;

    $table = new CsvTable($csv);
    $table->onlyColumns(['Name', 'City']);

    // First format with filter.
    $actual = $table->format();
    $this->assertEquals(<<< EOD
    Name,City
    John,"New York"

    EOD, $actual);

    // Reset and format again.
    $actual = $table->resetOnlyColumns()->format();
    $this->assertEquals(<<< EOD
    Name,Age,City
    John,30,"New York"

    EOD, $actual);
  }

  /**
   * Test resetWithoutColumns().
   */
  public function testResetWithoutColumns(): void {
    $csv = <<< EOD
    Name,Age,City
    John,30,New York
    EOD;

    $table = new CsvTable($csv);
    $table->withoutColumns(['Age']);

    // First format with exclusion.
    $actual = $table->format();
    $this->assertEquals(<<< EOD
    Name,City
    John,"New York"

    EOD, $actual);

    // Reset and format again.
    $actual = $table->resetWithoutColumns()->format();
    $this->assertEquals(<<< EOD
    Name,Age,City
    John,30,"New York"

    EOD, $actual);
  }

  /**
   * Test resetColumns() clears all transformations.
   */
  public function testResetColumns(): void {
    $csv = <<< EOD
    Name,Age,City,Country
    John,30,New York,USA
    EOD;

    $table = new CsvTable($csv);
    $table->onlyColumns(['Name', 'City'])->columnOrder(['City', 'Name']);

    // First format with transformations.
    $actual = $table->format();
    $this->assertEquals(<<< EOD
    City,Name
    "New York",John

    EOD, $actual);

    // Reset all and format again.
    $actual = $table->resetColumns()->format();
    $this->assertEquals(<<< EOD
    Name,Age,City,Country
    John,30,"New York",USA

    EOD, $actual);
  }

  /**
   * Test invalid column name throws exception.
   */
  public function testInvalidColumnNameThrowsException(): void {
    $csv = <<< EOD
    Name,Age,City
    John,30,New York
    EOD;

    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Column "InvalidColumn" not found in header.');
    (new CsvTable($csv))->columnOrder(['InvalidColumn'])->format();
  }

  /**
   * Test invalid column index throws exception.
   */
  public function testInvalidColumnIndexThrowsException(): void {
    $csv = <<< EOD
    Name,Age,City
    John,30,New York
    EOD;

    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Column index 10 is out of bounds (0-2).');
    (new CsvTable($csv))->columnOrder([10])->format();
  }

  /**
   * Test column transformations with Markdown table formatter.
   */
  public function testColumnTransformationsWithMarkdownTable(): void {
    $csv = <<< EOD
    Name,Age,City,Country
    John,30,New York,USA
    Jane,25,London,UK
    EOD;

    $actual = (new CsvTable($csv))
      ->withoutColumns(['Age'])
      ->columnOrder(['Country', 'City'])
      ->format('markdown_table');

    $this->assertEquals(<<< EOD
    | Country | City     | Name |
    |---------|----------|------|
    | USA     | New York | John |
    | UK      | London   | Jane |

    EOD, $actual);
  }

  /**
   * Test column transformations with empty CSV.
   */
  public function testColumnTransformationsWithEmptyCsv(): void {
    $csv = '';

    $actual = (new CsvTable($csv))->columnOrder([0, 1])->format();
    $this->assertEquals('', $actual);
  }

}
