<?php

namespace App\Services;

/**
 * NpvService
 *
 * Menangani semua logika kalkulasi Net Present Value (NPV)
 * untuk investasi emas ANTAM.
 *
 * Formula inti (TIDAK BERUBAH):
 *   NPV = CF_T / (1+r)^T − C₀
 *
 * Yang berubah: C₀ dan CF_T sekarang memperhitungkan faktor riil:
 *   - Denominasi pecahan emas ANTAM (0.5g, 1g, 2g, ... 1000g)
 *   - Premi cetak per pecahan (kecil lebih mahal per gram)
 *   - PPh 22 sebesar 0.45%
 *   - Spread buyback (~2.5% di bawah harga jual)
 */
class NpvService
{
    /** Skenario tren harga tahunan */
    public const TRENDS = [
        'optimistic'   => 0.08,   // +8%/tahun
        'moderate'     => 0.04,   // +4%/tahun
        'conservative' => 0.01,   // +1%/tahun
        'pessimistic'  => -0.03,  // -3%/tahun
    ];

    /** Label skenario tren */
    public const TREND_LABELS = [
        'optimistic'   => 'Optimistik (+8%/thn)',
        'moderate'     => 'Moderat (+4%/thn)',
        'conservative' => 'Konservatif (+1%/thn)',
        'pessimistic'  => 'Pesimistik (−3%/thn)',
    ];

    /**
     * Denominasi pecahan emas ANTAM resmi beserta faktor premi cetak.
     *
     * Premi cetak: pecahan kecil lebih mahal per gram karena biaya cetak
     * relatif tetap. Angka ini mendekati data riil logammulia.com.
     *
     * Faktor dikalikan ke harga_per_gram untuk mendapatkan harga per gram
     * yang sebenarnya untuk pecahan tersebut.
     */
    public const DENOMINATIONS = [
        '0.5'  => ['label' => '0,5 gram',   'premium' => 1.065],  // +6.5%
        '1'    => ['label' => '1 gram',      'premium' => 1.045],  // +4.5%
        '2'    => ['label' => '2 gram',      'premium' => 1.035],  // +3.5%
        '3'    => ['label' => '3 gram',      'premium' => 1.030],  // +3.0%
        '5'    => ['label' => '5 gram',      'premium' => 1.025],  // +2.5%
        '10'   => ['label' => '10 gram',     'premium' => 1.018],  // +1.8%
        '25'   => ['label' => '25 gram',     'premium' => 1.012],  // +1.2%
        '50'   => ['label' => '50 gram',     'premium' => 1.008],  // +0.8%
        '100'  => ['label' => '100 gram',    'premium' => 1.005],  // +0.5%
        '250'  => ['label' => '250 gram',    'premium' => 1.003],  // +0.3%
        '500'  => ['label' => '500 gram',    'premium' => 1.002],  // +0.2%
        '1000' => ['label' => '1000 gram',   'premium' => 1.001],  // +0.1%
    ];

    /** Tarif PPh 22 pembelian emas batangan (dengan NPWP) */
    public const PPH22_RATE = 0.0045;  // 0.45%

    /** Spread buyback ANTAM (harga buyback ≈ harga jual × (1 - spread)) */
    public const BUYBACK_SPREAD = 0.025;  // 2.5%

    // ─────────────────────────────────────────────────────────────────────────
    // Public API
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Hitung NPV realistis berdasarkan parameter investasi.
     *
     * @param  float  $hargaBeli      Harga beli emas per gram referensi (Rp)
     * @param  float  $modal          Modal investasi client (Rp)
     * @param  int    $months         Horizon waktu investasi (bulan)
     * @param  float  $annualTrend    Tren harga tahunan (desimal, misal 0.04 = 4%)
     * @param  float  $annualDiscount Tingkat diskonto tahunan (desimal, misal 0.07 = 7%)
     * @return array
     */
    public function calculate(
        float $hargaBeli,
        float $modal,
        int   $months,
        float $annualTrend,
        float $annualDiscount
    ): array {
        // ── 1. Pilih denominasi optimal berdasarkan modal ──────────────────
        $denom = $this->findOptimalDenomination($modal, $hargaBeli);

        // ── 2. Hitung biaya riil (C₀) ─────────────────────────────────────
        $realCost = $this->calculateRealCost($hargaBeli, $denom);

        // ── 3. Bangun proyeksi bulanan ────────────────────────────────────
        $monthlyTrend    = $this->annualToMonthly($annualTrend);
        $monthlyDiscount = $this->annualToMonthly($annualDiscount);

        $rows = $this->buildProjectionRows(
            $hargaBeli,
            $denom['total_gram'],
            $months,
            $monthlyTrend,
            $monthlyDiscount,
            $realCost['total_cost']  // C₀ = biaya riil (termasuk premi + PPh)
        );

        $lastRow = end($rows);

        // ── 4. Deteksi break-even point ───────────────────────────────────
        $breakEvenMonth = $this->findBreakEvenMonth($rows);

        return [
            'rows'            => $rows,
            'c0'              => round($realCost['total_cost'], 2),
            'npv'             => round($lastRow['npv_cumulative'], 2),
            'roi'             => round((($lastRow['sale_value'] - $realCost['total_cost']) / $realCost['total_cost']) * 100, 2),
            'final_value'     => round($lastRow['sale_value'], 2),
            'final_price'     => round($lastRow['price'], 2),

            // ── Data denominasi & biaya riil ──────────────────────────────
            'denomination'    => $denom['gram'],
            'denom_label'     => $denom['label'],
            'jumlah_batang'   => $denom['jumlah_batang'],
            'total_gram'      => round($denom['total_gram'], 1),
            'sisa_kas'        => round($realCost['sisa_kas'], 2),
            'harga_per_gram'  => round($realCost['harga_per_gram_denom'], 2),
            'biaya_pph'       => round($realCost['biaya_pph'], 2),
            'premi_cetak_pct' => round(($denom['premium'] - 1) * 100, 1),
            'break_even_month' => $breakEvenMonth,
        ];
    }

    /**
     * Resolusi tren dari key string (optimistic, moderate, dll).
     *
     * @param  string  $trendKey
     * @return float
     * @throws \InvalidArgumentException
     */
    public function resolveTrend(string $trendKey): float
    {
        if (! array_key_exists($trendKey, self::TRENDS)) {
            throw new \InvalidArgumentException("Trend '{$trendKey}' tidak valid.");
        }

        return self::TRENDS[$trendKey];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private Helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Pilih denominasi pecahan ANTAM terbesar yang bisa dibeli dengan modal.
     *
     * Logika: cari pecahan terbesar dimana (harga_per_gram × premium × gram) + PPh ≤ modal.
     * Jika modal terlalu kecil untuk pecahan apapun, gunakan 0.5 gram.
     *
     * @param  float  $modal      Modal investasi client (Rp)
     * @param  float  $hargaBeli  Harga referensi per gram (Rp)
     * @return array{gram:float, label:string, premium:float, jumlah_batang:int, total_gram:float}
     */
    private function findOptimalDenomination(float $modal, float $hargaBeli): array
    {
        $bestDenom = null;
        $bestBatang = 0;

        // Iterasi dari pecahan terbesar ke terkecil
        $denomKeys = array_keys(self::DENOMINATIONS);
        usort($denomKeys, fn($a, $b) => (float)$b <=> (float)$a);

        foreach ($denomKeys as $gramKey) {
            $gram = (float) $gramKey;
            $info = self::DENOMINATIONS[$gramKey];
            $hargaPerGramDenom = $hargaBeli * $info['premium'];
            $hargaPerBatang = $hargaPerGramDenom * $gram;
            $hargaPlusPph = $hargaPerBatang * (1 + self::PPH22_RATE);

            if ($hargaPlusPph <= 0) {
                continue;
            }

            // Berapa batang yang bisa dibeli?
            $batang = (int) floor($modal / $hargaPlusPph);

            if ($batang >= 1) {
                // Pilih pecahan terbesar yang muat dalam modal
                $bestDenom = $info;
                $bestDenom['gram'] = $gram;
                $bestBatang = $batang;
                break;
            }
        }

        // Jika tidak ada pecahan yang bisa dibeli, gunakan 0.5 gram (1 batang)
        if ($bestDenom === null) {
            $bestDenom = self::DENOMINATIONS['0.5'];
            $bestDenom['gram'] = 0.5;
            $bestBatang = 1;
        }

        return [
            'gram'           => $bestDenom['gram'],
            'label'          => $bestDenom['label'],
            'premium'        => $bestDenom['premium'],
            'jumlah_batang'  => $bestBatang,
            'total_gram'     => $bestDenom['gram'] * $bestBatang,
        ];
    }

    /**
     * Hitung biaya riil investasi (C₀) termasuk premi cetak dan PPh 22.
     *
     * @param  float  $hargaBeli       Harga referensi per gram (Rp)
     * @param  array  $denom           Data denominasi dari findOptimalDenomination()
     * @return array{harga_per_gram_denom:float, harga_emas:float, biaya_pph:float, total_cost:float, sisa_kas:float}
     */
    private function calculateRealCost(float $hargaBeli, array $denom): array
    {
        $hargaPerGramDenom = $hargaBeli * $denom['premium'];
        $hargaEmas = $hargaPerGramDenom * $denom['total_gram'];
        $biayaPph  = $hargaEmas * self::PPH22_RATE;
        $totalCost = $hargaEmas + $biayaPph;

        // Sisa kas = modal - total cost (dihitung di controller, tapi kita siapkan structure)
        return [
            'harga_per_gram_denom' => $hargaPerGramDenom,
            'harga_emas'           => round($hargaEmas, 2),
            'biaya_pph'            => round($biayaPph, 2),
            'total_cost'           => round($totalCost, 2),
            'sisa_kas'             => 0, // akan dihitung di controller
        ];
    }

    /**
     * Bangun array baris proyeksi bulanan.
     *
     * Cash inflow menggunakan HARGA BUYBACK (harga jual × (1 − spread)),
     * bukan harga jual ANTAM, karena client mendapat harga buyback saat menjual.
     *
     * @return array<int, array{period:int, price:float, buyback_price:float, sale_value:float, present_value:float, npv_cumulative:float, change_pct:float}>
     */
    private function buildProjectionRows(
        float $hargaBeli,
        float $gram,
        int   $months,
        float $monthlyTrend,
        float $monthlyDiscount,
        float $initialCost
    ): array {
        $rows = [];

        for ($t = 1; $t <= $months; $t++) {
            // Harga jual ANTAM di bulan ke-t (proyeksi)
            $price = $hargaBeli * (1 + $monthlyTrend) ** $t;

            // Harga buyback = harga jual × (1 − spread)
            // Ini yang sebenarnya didapat client saat menjual
            $buybackPrice = $price * (1 - self::BUYBACK_SPREAD);

            // Nilai penjualan = gram × harga buyback (bukan harga jual!)
            $saleValue    = $buybackPrice * $gram;

            // Present Value = nilai masa depan didiskontokan ke hari ini
            $presentValue = $saleValue / (1 + $monthlyDiscount) ** $t;

            // NPV = PV - C₀ (RUMUS INTI TETAP SAMA)
            $npvNow = $presentValue - $initialCost;

            // Perubahan harga vs bulan sebelumnya
            $prevPrice = $hargaBeli * (1 + $monthlyTrend) ** ($t - 1);
            $changePct = $prevPrice > 0 ? (($price - $prevPrice) / $prevPrice) * 100 : 0;

            $rows[] = [
                'period'         => $t,
                'price'          => round($price, 2),
                'buyback_price'  => round($buybackPrice, 2),
                'sale_value'     => round($saleValue, 2),
                'present_value'  => round($presentValue, 2),
                'npv_cumulative' => round($npvNow, 2),
                'change_pct'     => round($changePct, 4),
            ];
        }

        return $rows;
    }

    /**
     * Temukan bulan pertama di mana NPV ≥ 0 (break-even point).
     *
     * @param  array  $rows  Baris proyeksi dari buildProjectionRows()
     * @return int|null      Nomor bulan, atau null jika tidak pernah break-even
     */
    private function findBreakEvenMonth(array $rows): ?int
    {
        foreach ($rows as $row) {
            if ($row['npv_cumulative'] >= 0) {
                return $row['period'];
            }
        }

        return null;
    }

    /**
     * Konversi tingkat bunga tahunan ke bulanan menggunakan rumus geometri.
     *
     * Formula: r_monthly = (1 + r_annual)^(1/12) - 1
     */
    private function annualToMonthly(float $annual): float
    {
        return (1 + $annual) ** (1 / 12) - 1;
    }
}
