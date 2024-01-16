<?php

declare(strict_types = 1);

use AlexSkrypnyk\CsvTable\CsvTable;
use AlexSkrypnyk\CsvTable\Markdown;
use PHPUnit\Framework\TestCase;

/**
 * Class MarkdownUnitTest.
 *
 * Unit tests for Markdown renderer.
 *
 * @covers \AlexSkrypnyk\CsvTable\Markdown
 */
class MarkdownUnitTest extends TestCase {

  /**
   * Test render().
   */
  public function testRender(): void {
    $csv = <<< EOD
    col11a,col12ab,col13abc
    col21a,"col22ab cde",col23abc
    col31a,col32ab,"col33abcd"
    EOD;

    $actual = (new CsvTable($csv))->render(Markdown::class);

    $this->assertEquals(<<< EOD
    | col11a | col12ab     | col13abc  |
    |--------|-------------|-----------|
    | col21a | col22ab cde | col23abc  |
    | col31a | col32ab     | col33abcd |
    
    EOD, $actual);
  }

  /**
   * Test render() for multiline.
   */
  public function testRenderMultiline(): void {
    $csv = <<< EOD
    col11a,col12ab,col13abc
    col21a,"col22ab\ncdef",col23abc
    col31a,col32ab,col33abcd
    EOD;

    $actual = (new CsvTable($csv))->render(Markdown::class);

    $this->assertEquals(<<< EOD
    | col11a | col12ab           | col13abc  |
    |--------|-------------------|-----------|
    | col21a | col22ab<br />cdef | col23abc  |
    | col31a | col32ab           | col33abcd |
    
    EOD, $actual);
  }

  /**
   * Test render() for multiline and no header.
   */
  public function testRenderMultilineNoHeader(): void {
    $csv = <<< EOD
    col11a,col12ab,col13abc
    col21a,"col22ab\ncdef",col23abc
    col31a,col32ab,col33abcd
    EOD;

    $actual = (new CsvTable($csv))->noHeader()->render(Markdown::class);

    $this->assertEquals(<<< EOD
    | col11a | col12ab           | col13abc  |
    | col21a | col22ab<br />cdef | col23abc  |
    | col31a | col32ab           | col33abcd |
    
    EOD, $actual);
  }

}
