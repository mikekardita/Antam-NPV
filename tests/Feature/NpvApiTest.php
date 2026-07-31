<?php

namespace Tests\Feature;

use Tests\TestCase;

class NpvApiTest extends TestCase
{
    public function test_api_npv_calculate_returns_denomination_and_break_even_data(): void
    {
        $response = $this->postJson('/api/npv/calculate', [
            'modal'          => 10000000,
            'harga_beli'     => 1650000,
            'horizon_months' => 24,
            'discount_rate'  => 5,
            'trend'          => 'moderate',
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'modal',
                         'harga_beli',
                         'trend',
                         'denomination',
                         'denom_label',
                         'jumlah_batang',
                         'total_gram',
                         'sisa_kas',
                         'c0',
                         'npv',
                         'roi',
                         'final_value',
                         'final_price',
                         'break_even_month',
                         'rows',
                     ],
                 ]);
    }
}
