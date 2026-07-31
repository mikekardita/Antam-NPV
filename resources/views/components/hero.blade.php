{{-- components/hero.blade.php --}}
<div class="hero" style="padding-top:68px">
    <canvas id="hero-canvas"></canvas>
    <canvas id="particles-canvas"></canvas>

    <div class="hero-content" style="padding-top:80px; padding-bottom:60px">
        <div class="hero-eyebrow">Kalkulator Investasi Emas Antam</div>

        <h1>
            Analisis <em>NPV</em><br>
            Emas <span class="dim">ANTAM</span>
        </h1>

        <p class="hero-desc">
            Hitung Net Present Value investasi emas dengan data pasar langsung dari sumber resmi.
            Proyeksi multi-skenario, visualisasi dinamis, riwayat tersimpan.
        </p>

        <div class="hero-metrics">
            {{-- Harga ANTAM --}}
            <div class="hm">
                <div class="hm-val">
                    <span class="u">Rp</span>
                    <span id="hero-price" data-raw="0">—</span>
                </div>
                <div class="hm-label">Harga ANTAM / gram</div>
            </div>

            {{-- XAU/USD --}}
            <div class="hm">
                <div class="hm-val">
                    <span class="u">$</span>
                    <span id="hero-xau" data-raw="0">—</span>
                </div>
                <div class="hm-label">XAU / USD</div>
            </div>

            {{-- USD/IDR --}}
            <div class="hm">
                <div class="hm-val">
                    <span id="hero-idr" data-raw="0">—</span>
                    <span class="u" style="font-size:13px"> /USD</span>
                </div>
                <div class="hm-label">USD / IDR</div>
            </div>
        </div>
    </div>

    {{-- Price Ticker --}}
    <div class="ticker">
        <div class="ticker-track" id="ticker-inner">
            <span class="ticker-item">
                <span class="nm">ANTAM</span> <span>Memuat...</span>
            </span>
        </div>
    </div>
</div>
