<?php

use MDAI\Admin\Admin;
use PHPUnit\Framework\TestCase;

final class CsvExportHardeningTest extends TestCase
{
    public function testEscapeCsvCellPrefixesFormulaLikeValues(): void
    {
        $method = new ReflectionMethod(Admin::class, 'escape_csv_cell');
        $method->setAccessible(true);

        $result = $method->invoke(null, '=2+3');

        $this->assertSame("'=2+3", $result);
    }

    public function testEscapeCsvCellLeavesSafeValuesUntouched(): void
    {
        $method = new ReflectionMethod(Admin::class, 'escape_csv_cell');
        $method->setAccessible(true);

        $result = $method->invoke(null, 'GPTBot/1.0');

        $this->assertSame('GPTBot/1.0', $result);
    }
}