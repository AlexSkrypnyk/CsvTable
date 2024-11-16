<?php

declare(strict_types=1);

use AlexSkrypnyk\CsvTable\CsvTable;
use PHPUnit\Framework\TestCase;

/**
 * Class CsvTableUnitTest.
 *
 * Unit tests for CsvTable and default renderers.
 *
 * @covers \AlexSkrypnyk\CsvTable\CsvTable
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
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
   * Test the default behavior using default renderCsv() renderer.
   *
   * @dataProvider dataProviderDefault
   * @group wip3
   */
  public function testDefault(string $csv, bool|null $with_header, string $expected): void {
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

    $actual = $table->render();

    $this->assertEquals($expected, $actual);
  }

  /**
   * Data provider for testDefault().
   *
   * @return array<mixed>
   *   Data provider
   */
  public static function dataProviderDefault(): array {
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
   * Test doRender() renderer.
   */
  public function testRenderTable(): void {
    $csv = self::fixtureCsv();

    $actual = (new CsvTable($csv))
      ->render([CsvTable::class, 'renderTable']);

    $this->assertEquals(<<< EOD
    col11|col12|col13
    -----------------
    col21|col22|col23
    col31|col32|col33
    EOD, $actual);

    $actual = (new CsvTable($csv))
      ->withoutHeader()
      ->render([CsvTable::class, 'renderTable']);
    $this->assertEquals(<<< EOD
    col11|col12|col13
    col21|col22|col23
    col31|col32|col33
    EOD, $actual);
  }

  /**
   * Test pass not callable to render().
   */
  public function testRenderNotCallable(): void {
    $csv = self::fixtureCsv();

    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('Renderer must be callable.');
    (new CsvTable($csv))->render('Not callable');
  }

  /**
   * Test using a custom renderer.
   */
  public function testCustomRenderer(): void {
    $csv = self::fixtureCsv();

    $custom_renderer = static function ($header, $rows): string {
      $output = '';

      if (count($header) > 0) {
        $output = implode('|', $header);
        $output .= "\n" . str_repeat('=', strlen($output)) . "\n";
      }

      return $output . implode("\n", array_map(static function ($row): string {
        return implode('|', $row);
      }, $rows));
    };

    $actual = (new CsvTable($csv))->render($custom_renderer);

    $this->assertEquals(<<< EOD
    col11|col12|col13
    =================
    col21|col22|col23
    col31|col32|col33
    EOD, $actual);
  }

  /**
   * Test custom CSV separator.
   */
  public function testCustomCsvSeparator(): void {
    $csv = self::fixtureCsv();
    $csv_updated = str_replace(',', ';', self::fixtureCsv());

    // Custom separator for parsing, default for rendering.
    $actual = (new CsvTable($csv_updated, ';'))->render();
    $this->assertEquals($csv, $actual);

    // Custom separator for parsing and rendering.
    $actual = (new CsvTable($csv_updated, ';'))->render(NULL, ['separator' => ';']);
    $this->assertEquals($csv_updated, $actual);
  }

  /**
   * Test support for CSV multiline.
   */
  public function testCustomCsvMultiline(): void {
    $csv = <<< EOD
    col11,col12,col13
    col21,"col22\ncol22secondline",col23

    EOD;
    $actual = (new CsvTable($csv))->render();
    $this->assertEquals($csv, $actual);
  }

  /**
   * Test creating of the class instance using fromFile().
   *
   * @throws Exception
   */
  public function testFromFile(): void {
    $csv = self::fixtureCsv();
    $file = tempnam(sys_get_temp_dir(), 'csv');
    file_put_contents((string) $file, $csv);

    $actual = (CsvTable::fromFile((string) $file))->render();
    $this->assertEquals($csv, $actual);

    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('Unable to read the file non-existing-file.csv');
    CsvTable::fromFile('non-existing-file.csv');
  }

  /**
   * Test renderMarkdownTable().
   *
   * @group wip1
   */
  public function testRenderMarkdownTable(): void {
    $csv = <<< EOD
    col11a,col12ab,col13abc
    col21a,"col22ab cde",col23abc
    col31a,col32ab,"col33abcd"
    EOD;

    $actual = (new CsvTable($csv))->render([CsvTable::class, 'renderMarkdownTable']);

    $this->assertEquals(<<< EOD
    | col11a | col12ab     | col13abc  |
    |--------|-------------|-----------|
    | col21a | col22ab cde | col23abc  |
    | col31a | col32ab     | col33abcd |
    
    EOD, $actual);
  }

  /**
   * Test renderMarkdownTable() for multiline.
   */
  public function testRenderMarkdownTableMultiline(): void {
    $csv = <<< EOD
    col11a,col12ab,col13abc
    col21a,"col22ab\ncdef",col23abc
    col31a,col32ab,col33abcd
    EOD;

    $actual = (new CsvTable($csv))->render([CsvTable::class, 'renderMarkdownTable']);

    $this->assertEquals(<<< EOD
    | col11a | col12ab          | col13abc  |
    |--------|------------------|-----------|
    | col21a | col22ab<br/>cdef | col23abc  |
    | col31a | col32ab          | col33abcd |
    
    EOD, $actual);
  }

  /**
   * Test renderMarkdownTable() for multiline and no header.
   */
  public function testRenderMarkdownTableMultilineNoHeader(): void {
    $csv = <<< EOD
    col11a,col12ab,col13abc
    col21a,"col22ab\ncdef",col23abc
    col31a,col32ab,col33abcd
    EOD;

    $actual = (new CsvTable($csv))->withoutHeader()->render([CsvTable::class, 'renderMarkdownTable']);

    $this->assertEquals(<<< EOD
    | col11a | col12ab          | col13abc  |
    | col21a | col22ab<br/>cdef | col23abc  |
    | col31a | col32ab          | col33abcd |
    
    EOD, $actual);
  }

  /**
   * Test renderMarkdownTable() for custom separators.
   *
   * @group wip2
   */
  public function testRenderMarkdownTableCustomSeparators(): void {
    $csv = <<< EOD
    col11a,col12ab,col13abc
    col21a,"col22ab cde",col23abc
    col31a,col32ab,"col33abcd"
    EOD;

    $actual = (new CsvTable($csv))->render([CsvTable::class, 'renderMarkdownTable'], [
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

}
