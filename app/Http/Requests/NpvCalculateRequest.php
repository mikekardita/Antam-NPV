<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * NpvCalculateRequest
 *
 * Validasi input untuk kalkulasi NPV.
 * Sesuai skenario blackbox testing modul PI & HR.
 */
class NpvCalculateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'modal'          => ['required', 'numeric', 'min:1'],          // PI02: negatif → error
            'harga_beli'     => ['required', 'numeric', 'min:1'],          // PI05: 0 → error
            'horizon_months' => ['required', 'integer', 'min:1', 'max:120'], // PI08: 0 → error
            'discount_rate'  => ['required', 'numeric', 'min:0', 'max:100'], // PI11: negatif → error
            'trend'          => ['required', 'in:optimistic,moderate,conservative,pessimistic'],
        ];
    }

    public function messages(): array
    {
        return [
            'modal.required'          => 'Modal harus diisi',
            'modal.numeric'           => 'Modal harus berupa angka',
            'modal.min'               => 'Modal harus lebih dari 0',

            'harga_beli.required'     => 'Harga beli harus diisi',
            'harga_beli.numeric'      => 'Harga beli harus berupa angka',
            'harga_beli.min'          => 'Harga harus > 0',               // PI05

            'horizon_months.required' => 'Horizon waktu harus diisi',
            'horizon_months.min'      => 'Minimal 1 bulan',               // PI08
            'horizon_months.max'      => 'Horizon maksimum 120 bulan',
            'horizon_months.integer'  => 'Periode tidak valid',

            'discount_rate.required'  => 'Tingkat diskonto harus diisi',
            'discount_rate.min'       => 'Diskonto tidak boleh negatif',  // PI11
            'discount_rate.max'       => 'Diskonto maksimum 100%',

            'trend.required'          => 'Skenario tren harus dipilih',
            'trend.in'                => 'Skenario tren tidak valid',
        ];
    }
}
