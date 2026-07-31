<?php

namespace App\Http\Requests;

use App\Services\NpvService;
use Illuminate\Foundation\Http\FormRequest;

/**
 * StoreAnalysisRequest
 *
 * Validasi input untuk menyimpan analisis ke database.
 */
class StoreAnalysisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'           => ['required', 'string', 'max:255'],
            'note'           => ['nullable', 'string', 'max:1000'],
            'modal'          => ['required', 'numeric', 'min:0'],
            'gram'           => ['required', 'numeric', 'min:0'],
            'harga_beli'     => ['required', 'numeric', 'min:0'],
            'horizon_months' => ['required', 'integer', 'min:1'],
            'discount_rate'  => ['required', 'numeric', 'min:0'],
            'trend'          => ['required', 'in:' . implode(',', array_keys(NpvService::TRENDS))],
            'npv'            => ['required', 'numeric'],
            'roi'            => ['required', 'numeric'],
            'final_value'    => ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama analisis harus diisi.',
            'name.max'      => 'Nama analisis maksimal 255 karakter.',
            'trend.in'      => 'Skenario tren tidak valid.',
        ];
    }
}
