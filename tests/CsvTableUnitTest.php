<?php

declare(strict_types = 1);

use AlexSkrypnyk\CsvTable\CsvTable;
use PHPUnit\Framework\TestCase;

/**
 * Class CsvTableUnitTest.
 *
 * Unit tests for CsvTable and default renderers.
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
   *
   * @covers \AlexSkrypnyk\CsvTable\CsvTable::render
   */
  public function testDefault(string $csv, bool|null $hasHeader, string $expected): void {
    $table = new CsvTable($csv);

    if (!is_null($hasHeader)) {
      if ($hasHeader) {
        $table->hasHeader();
      }
      else {
        $table->noHeader();
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
   * @covers \CsvTable::getRows
   * @covers \CsvTable::getHeader
   */
  public function testGetters(): void {
    $csv = self::fixtureCsv();

    $table = new CsvTable($csv);

    $this->assertEquals(['col11', 'col12', 'col13'], $table->getHeader());
    $this->assertEquals([
      ['col21', 'col22', 'col23'],
      ['col31', 'col32', 'col33'],
    ], $table->getRows());

    $table = new CsvTable($csv);
    $table->noHeader();

    $this->assertEquals([], $table->getHeader());
    $this->assertEquals([
      ['col11', 'col12', 'col13'],
      ['col21', 'col22', 'col23'],
      ['col31', 'col32', 'col33'],
    ], $table->getRows());
  }

  /**
   * Test renderTable() renderer.
   *
   * @covers \AlexSkrypnyk\CsvTable\CsvTable::render
   * @covers \AlexSkrypnyk\CsvTable\CsvTable::renderTable
   */
  public function testRenderTable(): void {
    $csv = self::fixtureCsv();

    $actual = (new CsvTable($csv))->render([CsvTable::class, 'renderTable']);

    $this->assertEquals(<<< EOD
    col11|col12|col13
    -----------------
    col21|col22|col23
    col31|col32|col33
    EOD, $actual);
  }

  /**
   * Test using a custom formatter.
   *
   * @covers \AlexSkrypnyk\CsvTable\CsvTable::render
   */
  public function testAnotherFormatter(): void {
    $csv = self::fixtureCsv();

    $custom_renderer = function ($header, $rows) {
      if (count($header) > 0) {
        $header = implode('|', $header);
        $header = $header . "\n" . str_repeat('=', strlen($header)) . "\n";
      }
      else {
        $header = '';
      }

      return $header . implode("\n", array_map(function ($row) {
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
   *
   * @covers \AlexSkrypnyk\CsvTable\CsvTable::render
   */
  public function testCustomCsvSeparator(): void {
    $csv = str_replace(',', ';', self::fixtureCsv());
    $actual = (new CsvTable($csv, ';'))
      ->render();
    $this->assertEquals($csv, $actual);
  }

  /**
   * Test support for CSV multiline.
   *
   * @covers \AlexSkrypnyk\CsvTable\CsvTable::render
   */
  public function testCustomCsvMultiline(): void {
    $csv = <<< EOD
    col11,col12,col13
    col21,"col22\ncol22secondline",col23

    EOD;
    $actual = (new CsvTable($csv))
      ->render();
    $this->assertEquals($csv, $actual);
  }

  /**
   * Test creating of the class instance using fromFile().
   *
   * @covers \AlexSkrypnyk\CsvTable\CsvTable::render
   */
  public function testFromFile(): void {
    $csv = self::fixtureCsv();
    $file = tempnam(sys_get_temp_dir(), 'csv');
    file_put_contents((string) $file, $csv);
    $actual = (CsvTable::fromFile((string) $file))->render();
    $this->assertEquals($csv, $actual);
  }

}
