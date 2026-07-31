<?php

namespace App\Http\Controllers;

use App\Http\Requests\NpvCalculateRequest;
use App\Services\NpvService;
use Illuminate\Http\JsonResponse;

/**
 * NpvController
 *
 * Menerima parameter dari client, mendelegasikan kalkulasi ke NpvService,
 * lalu mengembalikan hasil dalam format JSON.
 */
class NpvController extends Controller
{
    public function __construct(
        private readonly NpvService $npvService
    ) {}

    /**
     * POST /api/npv/calculate
     *
     * @param  NpvCalculateRequest  $request
     * @return JsonResponse
     */
    public function calculate(NpvCalculateRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $modal      = (float) $validated['modal'];
        $hargaBeli  = (float) $validated['harga_beli'];
        $months     = (int)   $validated['horizon_months'];
        $discount   = (float) $validated['discount_rate'] / 100;
        $trendKey   = $validated['trend'];
        $annualTrend = $this->npvService->resolveTrend($trendKey);

        // Kalkulasi NPV realistis (denominasi, premi, PPh, buyback spread)
        $result = $this->npvService->calculate(
            hargaBeli:      $hargaBeli,
            modal:          $modal,
            months:         $months,
            annualTrend:    $annualTrend,
            annualDiscount: $discount
        );

        // Hitung sisa kas = modal - biaya aktual
        $sisaKas = max(0, $modal - $result['c0']);

        return response()->json([
            'success' => true,
            'data'    => [
                // ── Parameter input ──────────────────────────────────────
                'modal'           => $modal,
                'harga_beli'      => $hargaBeli,
                'trend'           => $trendKey,
                'trend_label'     => NpvService::TREND_LABELS[$trendKey],
                'horizon_months'  => $months,
                'discount_rate'   => (float) $validated['discount_rate'],

                // ── Denominasi & biaya riil ──────────────────────────────
                'denomination'    => $result['denomination'],
                'denom_label'     => $result['denom_label'],
                'jumlah_batang'   => $result['jumlah_batang'],
                'total_gram'      => $result['total_gram'],
                'gram'            => $result['total_gram'],
                'harga_per_gram'  => $result['harga_per_gram'],
                'biaya_pph'       => $result['biaya_pph'],
                'premi_cetak_pct' => $result['premi_cetak_pct'],
                'sisa_kas'        => round($sisaKas, 2),

                // ── Hasil NPV ───────────────────────────────────────────
                'c0'              => $result['c0'],
                'npv'             => $result['npv'],
                'roi'             => $result['roi'],
                'final_value'     => $result['final_value'],
                'final_price'     => $result['final_price'],
                'break_even_month' => $result['break_even_month'],

                // ── Proyeksi bulanan ────────────────────────────────────
                'rows'            => $result['rows'],
            ],
        ]);
    }
}
