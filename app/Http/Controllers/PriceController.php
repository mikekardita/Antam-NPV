<?php

namespace App\Http\Controllers;

use App\Services\PriceService;
use Illuminate\Http\JsonResponse;

/**
 * PriceController
 *
 * Bertindak sebagai proxy server-side untuk fetch harga realtime.
 * Dengan menjalankan request di server, masalah CORS dari browser
 * dapat dihindari sepenuhnya.
 */
class PriceController extends Controller
{
    public function __construct(
        private readonly PriceService $priceService
    ) {}

    /**
     * GET /api/prices
     *
     * Mengembalikan data harga realtime ANTAM, XAU/USD, dan USD/IDR.
     *
     * @return JsonResponse
     */
    public function prices(): JsonResponse
    {
        $prices = $this->priceService->getAllPrices();

        return response()->json([
            'success' => true,
            'data'    => $prices,
        ]);
    }
}
