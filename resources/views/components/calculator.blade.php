{{-- components/calculator.blade.php --}}
<section id="analisis" style="margin-bottom:16px">
    <div class="sec-eye reveal">Input &amp; Kalkulasi</div>
    <h2 class="sec-title reveal">Parameter Investasi</h2>

    <div class="analysis-layout reveal">

        {{-- ── Panel Kiri: Form Input ────────────────────────────────── --}}
        <div class="glass" style="padding:28px">

            {{-- Panduan Singkat --}}
            <div class="guide-box" style="margin-bottom:20px">
                <div class="guide-title">📋 Panduan Singkat</div>
                <div class="guide-text">
                    Isi form di bawah sesuai kondisi Anda, lalu klik <strong style="color:var(--gold)">Hitung NPV & ROI</strong>.
                    Sistem akan otomatis memilihkan pecahan emas terbaik dan menghitung apakah investasi ini
                    <strong style="color:var(--green)">Layak</strong> atau
                    <strong style="color:var(--red)">Tidak Layak</strong> untuk Anda.
                </div>
            </div>

            {{-- Modal Investasi --}}
            <div class="fg" style="margin-bottom:18px">
                <label for="inp-modal">💰 Modal Investasi (Rp)</label>
                <input type="number" id="inp-modal" value="10000000"
                       step="1000000" min="1" placeholder="10,000,000">
                <span class="input-hint">Jumlah uang yang ingin Anda investasikan ke emas. Contoh: 1.000.000, 10.000.000, atau 100.000.000</span>
            </div>

            {{-- Harga Beli & Jumlah Gram --}}
            <div class="form-grid" style="margin-bottom:14px">
                <div class="fg">
                    <label for="inp-harga">🏷️ Harga Beli (Rp/gram)</label>
                    <input type="number" id="inp-harga" value="1650000" step="5000" min="1">
                    <span class="input-hint">Harga emas per gram hari ini. Otomatis terisi dari data realtime.</span>
                    <button class="btn btn-ghost" id="resetHargaBtn" style="margin-top:6px;padding:7px">
                        ↺ Reset ke Harga Hari Ini
                    </button>
                </div>
                <div class="fg">
                    <label>📦 Pecahan & Gram (otomatis)</label>
                    <input type="text" id="inp-gram" readonly
                           style="background:rgba(255,255,255,.03);color:var(--text-muted)"
                           placeholder="Terisi otomatis setelah Hitung">
                    <span class="input-hint">Sistem memilihkan pecahan batang emas paling efisien untuk modal Anda.</span>
                </div>
            </div>

            {{-- Horizon & Diskonto --}}
            <div class="form-grid" style="margin-bottom:14px">
                <div class="fg">
                    <label for="inp-horizon">⏱ Berapa Lama Disimpan? (Bulan)</label>
                    <input type="number" id="inp-horizon" value="12" min="1" max="120">
                    <span class="input-hint">Durasi rencana penyimpanan emas Anda. Contoh: 12 bulan = 1 tahun, 36 bulan = 3 tahun.</span>
                </div>
                <div class="fg">
                    <label for="inp-diskonto">📉 Tingkat Inflasi / Bunga Bank (%/tahun)</label>
                    <input type="number" id="inp-diskonto" value="7" step="0.5" min="0" max="100">
                    <span class="input-hint">Berapa persen inflasi atau bunga deposito per tahun yang ingin dikalahkan oleh emas? Isi 5–7% untuk kondisi normal Indonesia.</span>
                </div>
            </div>

            {{-- Skenario Tren & Contoh --}}
            <div class="form-grid" style="margin-bottom:8px">
                <div class="fg">
                    <label for="inp-trend">📈 Prediksi Kenaikan Harga Emas</label>
                    <select id="inp-trend">
                        <option value="optimistic">🚀 Optimistik (+8%/thn) — Emas naik cepat</option>
                        <option value="moderate" selected>📊 Moderat (+4%/thn) — Kenaikan normal</option>
                        <option value="conservative">🐢 Konservatif (+1%/thn) — Naik sangat pelan</option>
                        <option value="pessimistic">📉 Pesimistik (−3%/thn) — Harga turun</option>
                    </select>
                    <span class="input-hint">Pilih seberapa optimis Anda terhadap kenaikan harga emas ke depan.</span>
                </div>
                <div class="fg">
                    <label>&nbsp;</label>
                    <button class="btn btn-ghost" id="exampleBtn">✨ Lihat Contoh yang Layak</button>
                    <span class="input-hint">Klik untuk mengisi form otomatis dengan contoh investasi yang menghasilkan keuntungan.</span>
                </div>
            </div>

            {{-- Formula NPV --}}
            <div class="formula">
                <div style="font-size:10px;color:var(--text-muted);margin-bottom:4px">RUMUS YANG DIGUNAKAN:</div>
                NPV = CF<sub>T</sub> / (1+r)<sup>T</sup> − C₀
                <div style="font-size:10px;color:var(--text-muted);margin-top:4px">
                    CF = Nilai Jual Emas &nbsp;|&nbsp; r = Diskonto &nbsp;|&nbsp; T = Bulan &nbsp;|&nbsp; C₀ = Modal Beli
                </div>
            </div>

            {{-- Tombol Hitung --}}
            <button class="btn btn-gold" id="btn-analyze" style="padding:14px;font-size:15px">
                ◆ &nbsp;Hitung NPV &amp; ROI
            </button>

            {{-- Tombol Simpan (tersembunyi awalnya) --}}
            <button id="btn-save" class="btn btn-ghost" style="margin-top:10px;display:none">
                💾 Simpan Analisis
            </button>
        </div>

        {{-- ── Panel Kanan: Hasil & AI Insight ─────────────────────── --}}
        <div class="right-panel">

            {{-- Kartu Info Pecahan Emas --}}
            <div class="glass denom-card" id="denom-card" style="padding:20px;display:none">
                <div class="sec-eye" style="margin-bottom:4px">📦 Pecahan Emas yang Dibeli</div>
                <div style="font-size:11px;color:var(--text-muted);margin-bottom:12px">
                    Sistem otomatis memilihkan pecahan paling efisien sesuai modal Anda.
                </div>
                <div class="denom-grid">
                    <div class="denom-item">
                        <span class="denom-lbl">Ukuran Pecahan</span>
                        <span class="denom-val gld" id="res-denom">—</span>
                    </div>
                    <div class="denom-item">
                        <span class="denom-lbl">Jumlah Batang</span>
                        <span class="denom-val" id="res-batang">—</span>
                    </div>
                    <div class="denom-item">
                        <span class="denom-lbl">Total Emas Anda</span>
                        <span class="denom-val gld" id="res-total-gram">—</span>
                    </div>
                    <div class="denom-item">
                        <span class="denom-lbl">Harga/Gram (sudah termasuk biaya cetak)</span>
                        <span class="denom-val" id="res-harga-gram">—</span>
                    </div>
                    <div class="denom-item">
                        <span class="denom-lbl">Biaya Cetak Batang</span>
                        <span class="denom-val" id="res-premi">—</span>
                    </div>
                    <div class="denom-item">
                        <span class="denom-lbl">Pajak Beli (PPh 22)</span>
                        <span class="denom-val" id="res-pph">—</span>
                    </div>
                    <div class="denom-item">
                        <span class="denom-lbl">Total Uang Keluar</span>
                        <span class="denom-val gld" id="res-c0-total">—</span>
                    </div>
                    <div class="denom-item">
                        <span class="denom-lbl">Sisa Uang Kembali</span>
                        <span class="denom-val" id="res-sisa-kas">—</span>
                    </div>
                </div>
            </div>

            {{-- Kartu Break-Even Point --}}
            <div class="glass break-even-card" id="break-even-card" style="padding:18px;display:none">
                <div class="break-even-inner">
                    <div class="break-even-icon" id="break-even-icon">⏳</div>
                    <div>
                        <div class="break-even-title" id="break-even-title">Kapan Mulai Untung?</div>
                        <div class="break-even-desc" id="break-even-desc">—</div>
                    </div>
                </div>
            </div>

            {{-- Kartu Hasil Kalkulasi --}}
            <div class="glass glass-gold" style="padding:24px">
                <div class="sec-eye" style="margin-bottom:4px">📊 Kesimpulan Hasil</div>
                <div style="font-size:11px;color:var(--text-muted);margin-bottom:14px">
                    Hijau = Untung &nbsp;|&nbsp; Merah = Rugi &nbsp;|&nbsp; Emas = Netral
                </div>
                <div class="rc-grid">
                    <div class="rc">
                        <div class="rc-lbl">KEUNTUNGAN BERSIH (NPV)</div>
                        <div class="rc-val gld" id="res-npv">—</div>
                    </div>
                    <div class="rc">
                        <div class="rc-lbl">PERSENTASE UNTUNG/RUGI (ROI)</div>
                        <div class="rc-val" id="res-roi">—</div>
                    </div>
                    <div class="rc">
                        <div class="rc-lbl">STATUS KELAYAKAN</div>
                        <div class="rc-val" id="res-status">—</div>
                    </div>
                    <div class="rc">
                        <div class="rc-lbl">HARGA EMAS DI AKHIR PERIODE</div>
                        <div class="rc-val" id="res-harga-akhir">—</div>
                    </div>
                    <div class="rc">
                        <div class="rc-lbl">NILAI EMAS ANDA DI AKHIR</div>
                        <div class="rc-val" id="res-nilai-akhir">—</div>
                    </div>
                    <div class="rc">
                        <div class="rc-lbl">SELISIH UNTUNG / RUGI (Rp)</div>
                        <div class="rc-val" id="res-profit">—</div>
                    </div>
                </div>
            </div>

            {{-- Panel Analisis AI --}}
            <div class="ai-panel">
                <div class="ai-header">
                    <div class="ai-icon">✦</div>
                    <div>
                        <div class="ai-title">Analisis &amp; Rekomendasi</div>
                        <div class="ai-sub">Penjelasan otomatis hasil kalkulasi</div>
                    </div>
                </div>
                <div id="ai-content">
                    Masukkan parameter di sebelah kiri lalu klik
                    <strong style="color:var(--gold)">Hitung NPV &amp; ROI</strong>
                    untuk mendapatkan analisis lengkap dan rekomendasi apakah investasi ini layak atau tidak.
                </div>
            </div>

        </div>
    </div>
</section>
