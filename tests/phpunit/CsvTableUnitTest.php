<?php

declare(strict_types=1);

namespace AlexSkrypnyk\CsvTable\Tests;

use AlexSkrypnyk\CsvTable\CsvTable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CsvTable and default formatters.
 */
#[CoversClass(CsvTable::class)]
final class CsvTableUnitTest extends TestCase {

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

  public function testFromFile(): void {
    $csv = self::fixtureCsv();
    $file = tempnam(sys_get_temp_dir(), 'csv');
    file_put_contents((string) $file, $csv);

    $actual = (CsvTable::fromFile((string) $file))->format();
    $this->assertSame($csv, $actual);
  }

  public function testFromFileNotReadableThrowsException(): void {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('Unable to read the file non-existing-file.csv');
    CsvTable::fromFile('non-existing-file.csv');
  }

  #[DataProvider('dataProviderFormatterDefault')]
  public function testFormatterDefault(string $csv, ?bool $with_header, string $expected): void {
    $table = new CsvTable($csv);

    // The NULL case asserts the default behavior.
    if ($with_header !== NULL) {
      if ($with_header) {
        $table->withHeader();
      }
      else {
        $table->withoutHeader();
      }
    }

    $actual = $table->format();

    $this->assertSame($expected, $actual);
  }

  /**
   * Data provider for testFormatterDefault().
   *
   * @return \Iterator<(int | string), mixed>
   *   Data provider
   */
  public static function dataProviderFormatterDefault(): \Iterator {
    yield ['', NULL, ''];
    yield ['', TRUE, ''];
    yield ['', FALSE, ''];
    yield [self::fixtureCsv(), NULL, self::fixtureCsv()];
    yield [self::fixtureCsv(), TRUE, self::fixtureCsv()];
    yield [self::fixtureCsv(), FALSE, self::fixtureCsv()];
  }

  public function testFormatterTable(): void {
    $csv = self::fixtureCsv();

    $actual = (new CsvTable($csv))->format('table');

    $this->assertSame(<<< EOD
    col11|col12|col13
    -----------------
    col21|col22|col23
    col31|col32|col33
    EOD, $actual);

    $actual = (new CsvTable($csv))->withoutHeader()->format('table');
    $this->assertSame(<<< EOD
    col11|col12|col13
    col21|col22|col23
    col31|col32|col33
    EOD, $actual);
  }

  public function testFormatterCsvSeparator(): void {
    $csv = self::fixtureCsv();
    $csv_updated = str_replace(',', ';', self::fixtureCsv());

    $actual = (new CsvTable($csv_updated, ';'))->format();
    $this->assertSame($csv, $actual);

    $actual = (new CsvTable($csv_updated, ';'))->format(NULL, ['separator' => ';']);
    $this->assertSame($csv_updated, $actual);
  }

  public function testFormatterCsvMultiline(): void {
    $csv = <<< EOD
    col11,col12,col13
    col21,"col22\ncol22secondline",col23

    EOD;
    $actual = (new CsvTable($csv))->format();
    $this->assertSame($csv, $actual);
  }

  public function testFormatterCsvEnclosureInValue(): void {
    $csv = <<< EOD
    col11,col12,col13
    col21,"col22 ""quoted"" value",col23

    EOD;

    $table = new CsvTable($csv);
    $table->parse();

    $this->assertSame([['col21', 'col22 "quoted" value', 'col23']], $table->getRows());
    $this->assertSame($csv, (new CsvTable($csv))->format());
  }

  public function testFormatterCsvCustomEnclosure(): void {
    $csv = <<< EOD
    col11,col12
    col21,'col22,more'

    EOD;

    $table = new CsvTable($csv, ',', "'");
    $table->parse();

    $this->assertSame([['col21', 'col22,more']], $table->getRows());
    $this->assertSame($csv, $table->format(NULL, ['enclosure' => "'"]));
  }

  public function testParseCustomEscape(): void {
    $csv = <<< EOD
    col11,col12
    col21,"col22~"more"

    EOD;

    $table = new CsvTable($csv, ',', '"', '~');
    $table->parse();

    // fgetcsv() keeps the escape character in the parsed value.
    $this->assertSame([['col21', 'col22~"more']], $table->getRows());
  }

  public function testFormatterMarkdownTableEnclosureInValue(): void {
    $csv = <<< EOD
    col11,col12,col13
    col21,"col22 ""quoted"" value",col23
    EOD;

    $actual = (new CsvTable($csv))->format('markdown_table');

    $this->assertSame(<<< EOD
    | col11 | col12                | col13 |
    |-------|----------------------|-------|
    | col21 | col22 "quoted" value | col23 |

    EOD, $actual);
  }

  public function testFormatterMarkdownTable(): void {
    $csv = <<< EOD
    col11a,col12ab,col13abc
    col21a,"col22ab cde",col23abc
    col31a,col32ab,"col33abcd"
    EOD;

    $actual = (new CsvTable($csv))->format('markdown_table');

    $this->assertSame(<<< EOD
    | col11a | col12ab     | col13abc  |
    |--------|-------------|-----------|
    | col21a | col22ab cde | col23abc  |
    | col31a | col32ab     | col33abcd |
    
    EOD, $actual);
  }

  public function testFormatterMarkdownTableMultiline(): void {
    $csv = <<< EOD
    col11a,col12ab,col13abc
    col21a,"col22ab\ncdef",col23abc
    col31a,col32ab,col33abcd
    EOD;

    $actual = (new CsvTable($csv))->format('markdown_table');

    $this->assertSame(<<< EOD
    | col11a | col12ab          | col13abc  |
    |--------|------------------|-----------|
    | col21a | col22ab<br/>cdef | col23abc  |
    | col31a | col32ab          | col33abcd |
    
    EOD, $actual);
  }

  public function testFormatterMarkdownTableMultilineNoHeader(): void {
    $csv = <<< EOD
    col11a,col12ab,col13abc
    col21a,"col22ab\ncdef",col23abc
    col31a,col32ab,col33abcd
    EOD;

    $actual = (new CsvTable($csv))->withoutHeader()->format('markdown_table');

    $this->assertSame(<<< EOD
    | col11a | col12ab          | col13abc  |
    | col21a | col22ab<br/>cdef | col23abc  |
    | col31a | col32ab          | col33abcd |
    
    EOD, $actual);
  }

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

    $this->assertSame(<<< EOD
    | col11a | col12ab     | col13abc  |
    |========|=============|===========|
    | col21a | col22ab cde | col23abc  |
    | col31a | col32ab     | col33abcd |
    
    EOD, $actual);
  }

  public function testFormatterMarkdownTableEmptyNoHeader(): void {
    $csv = '';

    $actual = (new CsvTable($csv))->withoutHeader()->format('markdown_table');

    $this->assertSame('', $actual);
  }

  public function testFormatterMarkdownTableSingleRowNoHeader(): void {
    $csv = 'col11,col12,col13';

    $actual = (new CsvTable($csv))->withoutHeader()->format('markdown_table');

    $this->assertSame(<<< EOD
    | col11 | col12 | col13 |

    EOD, $actual);
  }

  public function testFormatterMarkdownTableVaryingColumns(): void {
    $csv = <<< EOD
    col11,col12,col13
    col21,col22
    col31,col32,col33,col34
    EOD;

    $actual = (new CsvTable($csv))->format('markdown_table');

    $this->assertSame(<<< EOD
    | col11 | col12 | col13 |       |
    |-------|-------|-------|-------|
    | col21 | col22 |       |       |
    | col31 | col32 | col33 | col34 |

    EOD, $actual);
  }

  public function testFormatterMarkdownTableVaryingColumnsNoHeader(): void {
    $csv = <<< EOD
    col11,col12,col13
    col21,col22
    col31,col32,col33,col34
    EOD;

    $actual = (new CsvTable($csv))->withoutHeader()->format('markdown_table');

    $this->assertSame(<<< EOD
    | col11 | col12 | col13 |       |
    | col21 | col22 |       |       |
    | col31 | col32 | col33 | col34 |

    EOD, $actual);
  }

  public function testFormatterCustomNotCallable(): void {
    $csv = self::fixtureCsv();

    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('Formatter must be callable.');
    (new CsvTable($csv))->format('Not callable');
  }

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

    $this->assertSame(<<< EOD
    col11|col12|col13
    =================
    col21|col22|col23
    col31|col32|col33
    EOD, $actual);
  }

  public function testFormatterCustomClassDefaultCallback(): void {
    $csv = self::fixtureCsv();

    $actual = (new CsvTable($csv))->format(TestFormatter::class);

    $this->assertSame(<<< EOD
    col11|col12|col13
    =================
    col21|col22|col23
    col31|col32|col33
    EOD, $actual);
  }

  public function testFormatterCustomClassCustomCallback(): void {
    $csv = self::fixtureCsv();

    $actual = (new CsvTable($csv))->format(TestFormatter::customFormat(...));

    $this->assertSame(<<< EOD
    col11!col12!col13
    =================
    col21!col22!col23
    col31!col32!col33
    EOD, $actual);
  }

  public function testColumnOrderWithNames(): void {
    $csv = <<< EOD
    Name,Age,City,Country
    John,30,New York,USA
    Jane,25,London,UK
    EOD;

    $actual = (new CsvTable($csv))->columnOrder(['City', 'Name'])->format();

    $this->assertSame(<<< EOD
    City,Name,Age,Country
    "New York",John,30,USA
    London,Jane,25,UK

    EOD, $actual);
  }

  public function testColumnOrderWithIndices(): void {
    $csv = <<< EOD
    Name,Age,City,Country
    John,30,New York,USA
    Jane,25,London,UK
    EOD;

    $actual = (new CsvTable($csv))->columnOrder([2, 0])->format();

    $this->assertSame(<<< EOD
    City,Name,Age,Country
    "New York",John,30,USA
    London,Jane,25,UK

    EOD, $actual);
  }

  public function testColumnOrderWithMixed(): void {
    $csv = <<< EOD
    Name,Age,City,Country
    John,30,New York,USA
    Jane,25,London,UK
    EOD;

    $actual = (new CsvTable($csv))->columnOrder(['Country', 1])->format();

    $this->assertSame(<<< EOD
    Country,Age,Name,City
    USA,30,John,"New York"
    UK,25,Jane,London

    EOD, $actual);
  }

  public function testColumnOrderNoHeader(): void {
    $csv = <<< EOD
    John,30,New York,USA
    Jane,25,London,UK
    EOD;

    $actual = (new CsvTable($csv))->withoutHeader()->columnOrder([2, 0])->format();

    $this->assertSame(<<< EOD
    "New York",John,30,USA
    London,Jane,25,UK

    EOD, $actual);
  }

  public function testOnlyColumnsWithNames(): void {
    $csv = <<< EOD
    Name,Age,City,Country
    John,30,New York,USA
    Jane,25,London,UK
    EOD;

    $actual = (new CsvTable($csv))->onlyColumns(['City', 'Name'])->format();

    $this->assertSame(<<< EOD
    City,Name
    "New York",John
    London,Jane

    EOD, $actual);
  }

  public function testOnlyColumnsWithIndices(): void {
    $csv = <<< EOD
    Name,Age,City,Country
    John,30,New York,USA
    Jane,25,London,UK
    EOD;

    $actual = (new CsvTable($csv))->onlyColumns([0, 2])->format();

    $this->assertSame(<<< EOD
    Name,City
    John,"New York"
    Jane,London

    EOD, $actual);
  }

  public function testWithoutColumnsWithNames(): void {
    $csv = <<< EOD
    Name,Age,City,Country
    John,30,New York,USA
    Jane,25,London,UK
    EOD;

    $actual = (new CsvTable($csv))->withoutColumns(['Age', 'Country'])->format();

    $this->assertSame(<<< EOD
    Name,City
    John,"New York"
    Jane,London

    EOD, $actual);
  }

  public function testWithoutColumnsWithIndices(): void {
    $csv = <<< EOD
    Name,Age,City,Country
    John,30,New York,USA
    Jane,25,London,UK
    EOD;

    $actual = (new CsvTable($csv))->withoutColumns([1, 3])->format();

    $this->assertSame(<<< EOD
    Name,City
    John,"New York"
    Jane,London

    EOD, $actual);
  }

  public function testCombinedColumnTransformations(): void {
    $csv = <<< EOD
    Name,Age,City,Country,Email
    John,30,New York,USA,john@example.com
    Jane,25,London,UK,jane@example.com
    EOD;

    $actual = (new CsvTable($csv))
      ->withoutColumns(['Email'])
      ->columnOrder(['Country', 'City'])
      ->format();

    $this->assertSame(<<< EOD
    Country,City,Name,Age
    USA,"New York",John,30
    UK,London,Jane,25

    EOD, $actual);
  }

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

    $this->assertSame(<<< EOD
    Country,Name,City
    USA,John,"New York"
    UK,Jane,London

    EOD, $actual);
  }

  public function testResetColumnOrder(): void {
    $csv = <<< EOD
    Name,Age,City
    John,30,New York
    EOD;

    $table = new CsvTable($csv);
    $table->columnOrder(['City', 'Name']);

    $actual = $table->format();
    $this->assertSame(<<< EOD
    City,Name,Age
    "New York",John,30

    EOD, $actual);

    $actual = $table->resetColumnOrder()->format();
    $this->assertSame(<<< EOD
    Name,Age,City
    John,30,"New York"

    EOD, $actual);
  }

  public function testResetOnlyColumns(): void {
    $csv = <<< EOD
    Name,Age,City
    John,30,New York
    EOD;

    $table = new CsvTable($csv);
    $table->onlyColumns(['Name', 'City']);

    $actual = $table->format();
    $this->assertSame(<<< EOD
    Name,City
    John,"New York"

    EOD, $actual);

    $actual = $table->resetOnlyColumns()->format();
    $this->assertSame(<<< EOD
    Name,Age,City
    John,30,"New York"

    EOD, $actual);
  }

  public function testResetWithoutColumns(): void {
    $csv = <<< EOD
    Name,Age,City
    John,30,New York
    EOD;

    $table = new CsvTable($csv);
    $table->withoutColumns(['Age']);

    $actual = $table->format();
    $this->assertSame(<<< EOD
    Name,City
    John,"New York"

    EOD, $actual);

    $actual = $table->resetWithoutColumns()->format();
    $this->assertSame(<<< EOD
    Name,Age,City
    John,30,"New York"

    EOD, $actual);
  }

  public function testResetColumns(): void {
    $csv = <<< EOD
    Name,Age,City,Country
    John,30,New York,USA
    EOD;

    $table = new CsvTable($csv);
    $table->onlyColumns(['Name', 'City'])->columnOrder(['City', 'Name']);

    $actual = $table->format();
    $this->assertSame(<<< EOD
    City,Name
    "New York",John

    EOD, $actual);

    $actual = $table->resetColumns()->format();
    $this->assertSame(<<< EOD
    Name,Age,City,Country
    John,30,"New York",USA

    EOD, $actual);
  }

  public function testInvalidColumnNameThrowsException(): void {
    $csv = <<< EOD
    Name,Age,City
    John,30,New York
    EOD;

    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Column "InvalidColumn" not found in header.');
    (new CsvTable($csv))->columnOrder(['InvalidColumn'])->format();
  }

  public function testInvalidColumnIndexThrowsException(): void {
    $csv = <<< EOD
    Name,Age,City
    John,30,New York
    EOD;

    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Column index 10 is out of bounds (0-2).');
    (new CsvTable($csv))->columnOrder([10])->format();
  }

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

    $this->assertSame(<<< EOD
    | Country | City     | Name |
    |---------|----------|------|
    | USA     | New York | John |
    | UK      | London   | Jane |

    EOD, $actual);
  }

  public function testColumnTransformationsWithEmptyCsv(): void {
    $csv = '';

    $actual = (new CsvTable($csv))->columnOrder([0, 1])->format();
    $this->assertSame('', $actual);
  }

}
