<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model Analysis
 *
 * Merepresentasikan satu record riwayat analisis investasi emas ANTAM.
 *
 * @property int    $id
 * @property string $name
 * @property string|null $note
 * @property float  $modal
 * @property float  $gram
 * @property float  $harga_beli
 * @property int    $horizon_months
 * @property float  $discount_rate
 * @property string $trend
 * @property float  $npv
 * @property float  $roi
 * @property float  $final_value
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Analysis extends Model
{
    use HasFactory;

    /**
     * Nama tabel di database.
     */
    protected $table = 'analyses';

    /**
     * Kolom yang boleh diisi secara massal (mass assignment).
     */
    protected $fillable = [
        'name',
        'note',
        'modal',
        'gram',
        'harga_beli',
        'horizon_months',
        'discount_rate',
        'trend',
        'npv',
        'roi',
        'final_value',
    ];

    /**
     * Cast tipe data kolom ke tipe PHP yang sesuai.
     */
    protected $casts = [
        'modal'          => 'float',
        'gram'           => 'float',
        'harga_beli'     => 'float',
        'horizon_months' => 'integer',
        'discount_rate'  => 'float',
        'npv'            => 'float',
        'roi'            => 'float',
        'final_value'    => 'float',
    ];

    // ─────────────────────────────────────────────────────────────────────────
    // Accessors (Computed Attributes)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Apakah investasi ini layak (NPV positif)?
     */
    public function getIsLayakAttribute(): bool
    {
        return $this->npv > 0;
    }

    /**
     * Label status kelayakan.
     */
    public function getStatusLabelAttribute(): string
    {
        if ($this->npv > 0) {
            return 'Layak';
        }

        if ($this->npv < 0) {
            return 'Tidak Layak';
        }

        return 'Impas';
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Scopes
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Scope: hanya analisis yang layak (NPV > 0).
     */
    public function scopeLayak($query)
    {
        return $query->where('npv', '>', 0);
    }

    /**
     * Scope: hanya analisis yang tidak layak (NPV < 0).
     */
    public function scopeTidakLayak($query)
    {
        return $query->where('npv', '<', 0);
    }

    /**
     * Scope: pencarian berdasarkan nama atau catatan.
     */
    public function scopeSearch($query, string $keyword)
    {
        return $query->where(function ($q) use ($keyword) {
            $q->where('name', 'like', "%{$keyword}%")
              ->orWhere('note', 'like', "%{$keyword}%");
        });
    }
}
