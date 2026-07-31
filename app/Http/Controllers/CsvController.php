<?php

namespace App\Http\Controllers;

use App\Http\Requests\CsvUploadRequest;
use App\Services\CsvValidatorService;
use Illuminate\Http\JsonResponse;

/**
 * CsvController
 *
 * Menerima upload file CSV, mendelegasikan validasi ke CsvValidatorService,
 * dan mengembalikan hasil parsing atau pesan error yang sesuai.
 *
 * Endpoint: POST /api/csv/validate
 */
class CsvController extends Controller
{
    public function __construct(
        private readonly CsvValidatorService $csvValidator
    ) {}

    /**
     * POST /api/csv/validate
     *
     * @param  CsvUploadRequest  $request
     * @return JsonResponse
     */
    public function validate(CsvUploadRequest $request): JsonResponse
    {
        $file   = $request->file('csv_file');
        $result = $this->csvValidator->validate($file);

        if (! $result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'row_count'  => $result['row_count'],
                'avg_price'  => $result['avg_price'],
                'last_price' => $result['last_price'],
                'date_col'   => $result['date_col'],
                'price_col'  => $result['price_col'],
                'parse_ms'   => $result['parse_ms'],
                'rows'       => array_slice($result['rows'], 0, 5), // Preview 5 baris pertama
            ],
            'warning' => $result['warning'],
            'message' => $result['warning']
                ?? "✅ {$result['row_count']} baris data berhasil dibaca dari CSV dalam {$result['parse_ms']}ms.",
        ]);
    }
}
