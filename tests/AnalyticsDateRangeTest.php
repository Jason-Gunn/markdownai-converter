<?php

use MDAI\Analytics;
use PHPUnit\Framework\TestCase;

final class AnalyticsDateRangeTest extends TestCase
{
    public function testSanitizeDateRangeUsesProvidedDates(): void
    {
        $range = Analytics::sanitize_date_range('2026-02-01', '2026-02-21');

        $this->assertSame('2026-02-01', $range['from']);
        $this->assertSame('2026-02-21', $range['to']);
        $this->assertSame('2026-02-01 00:00:00', $range['from_datetime']);
        $this->assertSame('2026-02-21 23:59:59', $range['to_datetime']);
    }

    public function testSanitizeDateRangeSwapsWhenFromGreaterThanTo(): void
    {
        $range = Analytics::sanitize_date_range('2026-02-21', '2026-02-01');

        $this->assertSame('2026-02-01', $range['from']);
        $this->assertSame('2026-02-21', $range['to']);
    }

    public function testSanitizeDateRangeFallsBackForInvalidInput(): void
    {
        $range = Analytics::sanitize_date_range('invalid-date', 'also-invalid');

        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $range['from']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $range['to']);
    }
}
