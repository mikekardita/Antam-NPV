{{-- components/chart-section.blade.php --}}
<section id="proyeksi">
    <div class="sec-eye reveal">Visualisasi</div>
    <h2 class="sec-title reveal">Grafik &amp; Proyeksi</h2>

    {{-- Panel Grafik --}}
    <div class="chart-panel reveal">
        <div class="chart-header">
            <div>
                <div style="font-weight:600;font-size:15px">Proyeksi Harga Emas &amp; Keuntungan Bersih (NPV)</div>
                <div style="font-size:12px;color:var(--text-muted)" id="chart-sub">
                    Klik "Hitung NPV & ROI" untuk melihat grafik proyeksi
                </div>
            </div>
            <div class="chart-legend" id="chart-legend">
                <div class="cl-item">
                    <div class="cl-dot" style="background:var(--gold)"></div>
                    <span>Harga Emas/gram <span style="color:var(--text-muted)">(sumbu kiri)</span></span>
                </div>
                <div class="cl-item">
                    <div class="cl-dot" id="npv-legend-dot" style="background:var(--green);border-top:2px dashed var(--green);height:0"></div>
                    <span>Keuntungan Bersih / NPV <span style="color:var(--text-muted)">(sumbu kanan)</span></span>
                </div>
            </div>
        </div>

        {{-- Panduan membaca grafik --}}
        <div class="chart-guide" id="chart-guide" style="display:none">
            <span style="color:var(--gold)">●</span> Garis emas = pergerakan harga emas per gram &nbsp;&nbsp;
            <span id="npv-guide-dot" style="color:var(--green)">● ● ●</span> Garis putus-putus = keuntungan bersih (NPV) Anda &nbsp;&nbsp;
            <span style="color:var(--text-muted)">|</span>&nbsp;&nbsp;
            <strong style="color:var(--green)">Hijau = Untung</strong> &nbsp;
            <strong style="color:var(--red)">Merah = Masih Rugi</strong>
        </div>

        <div class="chart-wrap">
            <canvas id="priceChart"></canvas>
        </div>
    </div>

    {{-- Tabel Proyeksi Bulanan --}}
    <div class="table-panel reveal">
        <div class="table-header">
            <div>
                <div style="font-weight:600">Tabel Rincian Per Bulan</div>
                <div style="font-size:11px;color:var(--text-muted);margin-top:2px">
                    Keterangan warna: <strong style="color:var(--green)">Hijau = Sudah Untung</strong> &nbsp;|&nbsp; <strong style="color:var(--red)">Merah = Masih Rugi</strong>
                </div>
            </div>
            <span style="font-size:12px;color:var(--text-muted)">Harga & Keuntungan per Bulan</span>
        </div>
        <div class="scroll-wrap">
            <table>
                <thead>
                    <tr>
                        <th>BULAN KE-</th>
                        <th>HARGA EMAS /gram</th>
                        <th>NILAI JUAL EMAS ANDA</th>
                        <th>NILAI SETELAH INFLASI</th>
                        <th>NAIK / TURUN</th>
                        <th>UNTUNG / RUGI (NPV)</th>
                    </tr>
                </thead>
                <tbody id="proj-tbody">
                    <tr>
                        <td colspan="6" style="text-align:center;padding:32px;color:var(--text-muted)">
                            Klik "Hitung NPV &amp; ROI" terlebih dahulu untuk melihat proyeksi detail
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</section>
