<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

/**
 * HomeController
 *
 * Menangani halaman utama aplikasi ANTAM NPV.
 */
class HomeController extends Controller
{
    /**
     * GET /
     *
     * Tampilkan halaman utama kalkulator.
     *
     * @return View
     */
    public function index(): View
    {
        return view('home');
    }
}
