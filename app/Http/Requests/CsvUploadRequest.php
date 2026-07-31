<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * CsvUploadRequest
 *
 * Validasi awal file upload CSV (BB05: ekstensi, ukuran).
 */
class CsvUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // BB05: Hanya menerima file .csv
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'csv_file.required' => 'File CSV harus diunggah.',
            'csv_file.file'     => 'Upload harus berupa file.',
            'csv_file.mimes'    => 'Format file harus .csv',   // BB05
            'csv_file.max'      => 'Ukuran file maksimum 10MB.',
        ];
    }
}
