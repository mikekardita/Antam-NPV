{{-- components/header.blade.php --}}
<header>
    <a class="logo" href="{{ route('home') }}">
        <div class="logo-cube">
            <div class="logo-cube-inner">
                <div class="face face-front">A</div>
                <div class="face face-back">N</div>
                <div class="face face-top">T</div>
                <div class="face face-bottom">M</div>
                <div class="face face-left">A</div>
                <div class="face face-right">M</div>
            </div>
        </div>
        <div class="logo-name"><span>ANTAM</span> · NPV</div>
    </a>

    <nav>
        <a href="#analisis">Analisis</a>
        <a href="#proyeksi">Proyeksi</a>
        <a href="#riwayat">Riwayat</a>
    </nav>

    <div class="live-pill">
        <div class="live-dot"></div>
        <span id="price-status">Memuat...</span>
    </div>
</header>
