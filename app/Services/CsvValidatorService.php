<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

/**
 * CsvValidatorService
 *
 * Validasi dan parsing file CSV historis harga emas.
 *
 * Aturan validasi (sesuai blackbox test BB01–BB09):
 *  BB01: Format valid → parse berhasil
 *  BB02: Kolom tanggal tidak ditemukan → error
 *  BB03: Kolom harga tidak ditemukan → error
 *  BB04: File kosong (0 byte) → error
 *  BB05: Bukan .csv → error (ditangani di CsvUploadRequest)
 *  BB06: Harga negatif → error
 *  BB07: Format tanggal bukan YYYY-MM-DD → coba auto-parse, jika gagal error
 *  BB08: Hanya 1 baris data → warning (ARIMA)
 *  BB09: > 1000 baris → proses normal, catat waktu
 */
class CsvValidatorService
{
    /** Nama kolom yang diterima sebagai kolom "tanggal" */
    private const DATE_ALIASES = [
        'tanggal', 'date', 'tgl', 'waktu', 'time', 'period', 'bulan', 'month',
    ];

    /** Nama kolom yang diterima sebagai kolom "harga" */
    private const PRICE_ALIASES = [
        'harga', 'price', 'harga_emas', 'gold_price', 'harga_antam', 'nilai',
        'harga_beli', 'harga_jual', 'close', 'value', 'antam',
    ];

    /**
     * Validasi dan parse file CSV.
     *
     * @param  UploadedFile  $file
     * @return array{
     *     success: bool,
     *     error: string|null,
     *     warning: string|null,
     *     rows: array,
     *     avg_price: float,
     *     row_count: int,
     *     date_col: string,
     *     price_col: string,
     *     parse_ms: int,
     * }
     */
    public function validate(UploadedFile $file): array
    {
        $startMs = (int) round(microtime(true) * 1000);

        // ── BB04: File kosong ──────────────────────────────────────────────
        if ($file->getSize() === 0) {
            return $this->fail('File tidak boleh kosong');
        }

        // ── Baca konten CSV ────────────────────────────────────────────────
        $content = file_get_contents($file->getRealPath());
        if ($content === false) {
            return $this->fail('Gagal membaca file CSV');
        }

        // Normalisasi line endings
        $content = str_replace(["\r\n", "\r"], "\n", trim($content));
        $lines   = array_filter(explode("\n", $content), fn($l) => trim($l) !== '');
        $lines   = array_values($lines);

        if (count($lines) < 1) {
            return $this->fail('File tidak boleh kosong');
        }

        // ── Parse header ───────────────────────────────────────────────────
        $headers = str_getcsv($lines[0]);
        $headers = array_map(fn($h) => strtolower(trim($h)), $headers);

        // ── BB02: Kolom tanggal tidak ditemukan ────────────────────────────
        $dateCol = $this->findCol($headers, self::DATE_ALIASES);
        if ($dateCol === null) {
            return $this->fail('Kolom tanggal tidak ditemukan');
        }

        // ── BB03: Kolom harga tidak ditemukan ─────────────────────────────
        $priceCol = $this->findCol($headers, self::PRICE_ALIASES);
        if ($priceCol === null) {
            return $this->fail('Kolom harga tidak ditemukan');
        }

        $dateIdx  = array_search($dateCol, $headers);
        $priceIdx = array_search($priceCol, $headers);

        // ── Parse baris data ───────────────────────────────────────────────
        $dataLines = array_slice($lines, 1);

        // ── BB08: Hanya 1 baris data ───────────────────────────────────────
        if (count($dataLines) < 2) {
            // Masih kita proses, tapi beri warning
            $warning = 'Data minimal 2 periode (diperlukan untuk analisis ARIMA)';
        }

        $rows      = [];
        $prices    = [];
        $dateError = false;
        $rowNumber = 1;

        foreach ($dataLines as $rawLine) {
            $rowNumber++;
            $cols = str_getcsv($rawLine);

            if (! isset($cols[$dateIdx]) || ! isset($cols[$priceIdx])) {
                continue; // Lewati baris tidak lengkap
            }

            $rawDate  = trim($cols[$dateIdx]);
            $rawPrice = trim($cols[$priceIdx]);

            // ── Validasi dan parse harga ───────────────────────────────────
            $price = $this->parsePrice($rawPrice);
            if ($price === null) {
                continue; // Lewati baris dengan harga tidak valid (non-numerik)
            }

            // ── BB06: Harga negatif ────────────────────────────────────────
            if ($price < 0) {
                return $this->fail('Harga tidak boleh negatif', [
                    'row'   => $rowNumber,
                    'value' => $rawPrice,
                ]);
            }

            // ── BB07: Validasi format tanggal ──────────────────────────────
            $parsedDate = $this->parseDate($rawDate);
            if ($parsedDate === null) {
                $dateError = true;
                return $this->fail(
                    "Format tanggal tidak valid pada baris {$rowNumber}: \"{$rawDate}\". Gunakan format YYYY-MM-DD.",
                    ['row' => $rowNumber, 'value' => $rawDate]
                );
            }

            $rows[]   = ['date' => $parsedDate, 'price' => $price];
            $prices[] = $price;
        }

        if (empty($rows)) {
            return $this->fail('Tidak ada data valid yang dapat dibaca dari file CSV');
        }

        $endMs   = (int) round(microtime(true) * 1000);
        $parseMs = $endMs - $startMs;

        // ── BB08: Warning jika < 2 baris data valid ────────────────────────
        $warning = count($rows) < 2
            ? 'Data minimal 2 periode (diperlukan untuk analisis ARIMA)'
            : null;

        return [
            'success'   => true,
            'error'     => null,
            'warning'   => $warning,
            'rows'      => $rows,
            'avg_price' => round(array_sum($prices) / count($prices), 2),
            'last_price'=> end($prices),
            'row_count' => count($rows),
            'date_col'  => $dateCol,
            'price_col' => $priceCol,
            'parse_ms'  => $parseMs,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private helpers
    // ─────────────────────────────────────────────────────────────────────────

    /** Cari nama kolom dari daftar alias yang diterima */
    private function findCol(array $headers, array $aliases): ?string
    {
        foreach ($aliases as $alias) {
            if (in_array($alias, $headers, true)) {
                return $alias;
            }
        }
        // Partial match
        foreach ($aliases as $alias) {
            foreach ($headers as $header) {
                if (str_contains($header, $alias) || str_contains($alias, $header)) {
                    return $header;
                }
            }
        }
        return null;
    }

    /**
     * Parse tanggal — menerima YYYY-MM-DD, coba auto-parse format lain.
     * BB07: jika format lain berhasil dikonversi → OK. Jika tidak → null.
     */
    private function parseDate(string $raw): ?string
    {
        $raw = trim($raw);

        // Format YYYY-MM-DD (canonical)
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
            $ts = strtotime($raw);
            return $ts !== false ? date('Y-m-d', $ts) : null;
        }

        // Auto-parse format lain (DD/MM/YYYY, MM/DD/YYYY, DD-MM-YYYY, dll.)
        $formats = [
            'd/m/Y', 'm/d/Y', 'd-m-Y', 'Y/m/d',
            'd/m/y', 'm/d/y', 'j/n/Y', 'Y.m.d',
        ];

        foreach ($formats as $fmt) {
            $dt = \DateTime::createFromFormat($fmt, $raw);
            if ($dt !== false) {
                // Cek tidak ada overflow (misal: bulan 13)
                $errors = \DateTime::getLastErrors();
                if ($errors && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) {
                    continue;
                }
                return $dt->format('Y-m-d');
            }
        }

        // Coba PHP strtotime sebagai last resort
        $ts = @strtotime($raw);
        if ($ts !== false && $ts > 0) {
            return date('Y-m-d', $ts);
        }

        return null; // Tidak dapat di-parse
    }

    /**
     * Parse nilai harga dari string.
     * Menangani format: "1.000.000", "1,000,000", "1000000", "2635000.5"
     */
    private function parsePrice(string $raw): ?float
    {
        // Hapus simbol mata uang dan spasi
        $clean = preg_replace('/[Rp\s$€£¥,]/', '', $raw);
        // Ganti titik ribuan jika ada desimal di belakang (1.234.567)
        $clean = preg_replace('/\.(?=\d{3}(?:\.|$))/', '', $clean);

        if (! is_numeric($clean)) {
            return null;
        }

        return (float) $clean;
    }

    /** Helper return error */
    private function fail(string $message, array $meta = []): array
    {
        return [
            'success'    => false,
            'error'      => $message,
            'warning'    => null,
            'rows'       => [],
            'avg_price'  => 0,
            'last_price' => 0,
            'row_count'  => 0,
            'date_col'   => '',
            'price_col'  => '',
            'parse_ms'   => 0,
            ...$meta,
        ];
    }
}
