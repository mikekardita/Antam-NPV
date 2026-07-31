<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Buat tabel analyses untuk menyimpan riwayat analisis investasi.
 */
return new class extends Migration
{
    /**
     * Jalankan migration (buat tabel).
     */
    public function up(): void
    {
        Schema::create('analyses', function (Blueprint $table) {
            $table->id();

            // Identifikasi
            $table->string('name');                         // Nama analisis
            $table->text('note')->nullable();               // Catatan opsional

            // Parameter investasi
            $table->decimal('modal', 15, 2);                // Modal investasi (Rp)
            $table->decimal('gram', 10, 4);                 // Jumlah gram emas
            $table->decimal('harga_beli', 12, 2);           // Harga beli per gram (Rp)
            $table->unsignedSmallInteger('horizon_months'); // Horizon waktu (bulan)
            $table->decimal('discount_rate', 5, 2);         // Tingkat diskonto (%/tahun)
            $table->string('trend', 20);                    // Skenario tren (optimistic, dll)

            // Hasil kalkulasi
            $table->decimal('npv', 15, 2);                  // Net Present Value (Rp)
            $table->decimal('roi', 8, 2);                   // Return on Investment (%)
            $table->decimal('final_value', 15, 2);          // Nilai akhir investasi (Rp)

            $table->timestamps(); // created_at & updated_at
        });
    }

    /**
     * Batalkan migration (hapus tabel).
     */
    public function down(): void
    {
        Schema::dropIfExists('analyses');
    }
};
