<?php

namespace Tests\Unit;

use App\Services\NpvService;
use PHPUnit\Framework\TestCase;

class NpvServiceTest extends TestCase
{
    private NpvService $npvService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->npvService = new NpvService();
    }

    public function test_calculate_with_small_modal_selects_small_denomination(): void
    {
        $hargaBeli = 1650000;
        $modal = 1000000; // Rp 1.000.000 (cukup untuk 0.5 gram)

        $result = $this->npvService->calculate(
            hargaBeli: $hargaBeli,
            modal: $modal,
            months: 24,
            annualTrend: 0.08,
            annualDiscount: 0.05
        );

        $this->assertEquals(0.5, $result['denomination']);
        $this->assertEquals(1, $result['jumlah_batang']);
        $this->assertEquals(0.5, $result['total_gram']);
        $this->assertGreaterThan(0, $result['c0']);
    }

    public function test_calculate_with_large_modal_selects_large_denomination(): void
    {
        $hargaBeli = 1650000;
        $modal = 100000000; // Rp 100.000.000 (cukup untuk batang 50 gram)

        $result = $this->npvService->calculate(
            hargaBeli: $hargaBeli,
            modal: $modal,
            months: 24,
            annualTrend: 0.08,
            annualDiscount: 0.05
        );

        $this->assertEquals(50, $result['denomination']);
        $this->assertEquals(1, $result['jumlah_batang']);
        $this->assertEquals(50, $result['total_gram']);
    }

    public function test_break_even_month_is_detected(): void
    {
        $hargaBeli = 1650000;
        $modal = 50000000;

        $result = $this->npvService->calculate(
            hargaBeli: $hargaBeli,
            modal: $modal,
            months: 36,
            annualTrend: 0.08,
            annualDiscount: 0.05
        );

        $this->assertNotNull($result['break_even_month']);
        $this->assertGreaterThanOrEqual(1, $result['break_even_month']);
        $this->assertLessThanOrEqual(36, $result['break_even_month']);
    }
}
