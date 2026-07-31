<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAnalysisRequest;
use App\Models\Analysis;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * AnalysisController
 *
 * Menangani operasi CRUD untuk riwayat analisis investasi emas:
 * - Tampilkan daftar (dengan search & filter)
 * - Simpan analisis baru
 * - Hapus satu / semua analisis
 * - Export ke CSV
 */
class AnalysisController extends Controller
{
    /**
     * GET /api/analysis
     *
     * Kembalikan daftar riwayat analisis dengan filter & pencarian.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = Analysis::query()->latest();

        // Filter pencarian (nama atau catatan)
        if ($search = $request->input('search')) {
            $query->search($search);
        }

        // Filter kelayakan
        match ($request->input('filter', 'all')) {
            'layak'  => $query->layak(),
            'tidak'  => $query->tidakLayak(),
            default  => null,
        };

        $analyses = $query->get()->map(fn(Analysis $a) => [
            'id'             => $a->id,
            'name'           => $a->name,
            'note'           => $a->note,
            'modal'          => $a->modal,
            'gram'           => $a->gram,
            'harga_beli'     => $a->harga_beli,
            'horizon_months' => $a->horizon_months,
            'discount_rate'  => $a->discount_rate,
            'trend'          => $a->trend,
            'npv'            => $a->npv,
            'roi'            => $a->roi,
            'final_value'    => $a->final_value,
            'is_layak'       => $a->is_layak,
            'status_label'   => $a->status_label,
            'created_at'     => $a->created_at->format('d/m/Y'),
        ]);

        return response()->json([
            'success' => true,
            'data'    => $analyses,
            'total'   => $analyses->count(),
        ]);
    }

    /**
     * POST /api/analysis
     *
     * Simpan analisis baru ke database.
     *
     * @param  StoreAnalysisRequest  $request
     * @return JsonResponse
     */
    public function store(StoreAnalysisRequest $request): JsonResponse
    {
        $analysis = Analysis::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Analisis berhasil disimpan.',
            'data'    => [
                'id'         => $analysis->id,
                'name'       => $analysis->name,
                'created_at' => $analysis->created_at->format('d/m/Y'),
            ],
        ], 201);
    }

    /**
     * DELETE /api/analysis/{analysis}
     *
     * Hapus satu analisis berdasarkan ID.
     *
     * @param  Analysis  $analysis  (route model binding)
     * @return JsonResponse
     */
    public function destroy(Analysis $analysis): JsonResponse
    {
        $analysis->delete();

        return response()->json([
            'success' => true,
            'message' => 'Analisis berhasil dihapus.',
        ]);
    }

    /**
     * DELETE /api/analysis
     *
     * Hapus semua analisis dari database.
     *
     * @return JsonResponse
     */
    public function destroyAll(): JsonResponse
    {
        $count = Analysis::count();
        Analysis::truncate();

        return response()->json([
            'success' => true,
            'message' => "{$count} analisis berhasil dihapus.",
        ]);
    }

    /**
     * GET /api/analysis/export
     *
     * Export seluruh riwayat analisis ke file CSV.
     *
     * @return Response
     */
    public function export(): Response
    {
        $analyses = Analysis::latest()->get();

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="antam-npv-riwayat-' . now()->format('Ymd') . '.csv"',
        ];

        // BOM untuk Excel agar bisa membaca UTF-8
        $bom  = "\xEF\xBB\xBF";
        $rows = [];

        // Header baris CSV
        $rows[] = implode(',', [
            'ID', 'Nama', 'Catatan', 'Tanggal',
            'Modal (Rp)', 'Gram', 'Harga Beli (Rp/g)',
            'Horizon (Bulan)', 'Diskonto (%)', 'Skenario',
            'NPV (Rp)', 'ROI (%)', 'Nilai Akhir (Rp)', 'Status',
        ]);

        // Data baris
        foreach ($analyses as $a) {
            $rows[] = implode(',', [
                $a->id,
                '"' . str_replace('"', '""', $a->name) . '"',
                '"' . str_replace('"', '""', $a->note ?? '') . '"',
                $a->created_at->format('d/m/Y'),
                number_format($a->modal, 2, '.', ''),
                number_format($a->gram, 4, '.', ''),
                number_format($a->harga_beli, 2, '.', ''),
                $a->horizon_months,
                number_format($a->discount_rate, 2, '.', ''),
                $a->trend,
                number_format($a->npv, 2, '.', ''),
                number_format($a->roi, 2, '.', ''),
                number_format($a->final_value, 2, '.', ''),
                $a->status_label,
            ]);
        }

        $content = $bom . implode("\r\n", $rows);

        return response($content, 200, $headers);
    }
}
