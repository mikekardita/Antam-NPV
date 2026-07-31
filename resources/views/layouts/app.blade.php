<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- SEO --}}
    <title>@yield('title', 'ANTAM NPV — Kalkulator Investasi Emas Antam')</title>
    <meta name="description" content="@yield('description', 'Hitung Net Present Value investasi emas ANTAM dengan data pasar realtime. Proyeksi multi-skenario, visualisasi dinamis, dan riwayat analisis.')">
    <meta name="keywords" content="ANTAM, emas, investasi, NPV, ROI, kalkulator, harga emas">
    <meta name="author" content="Michael Kardita">

    {{-- Google Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=Inter:wght@300;400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

    {{-- Stylesheet Utama --}}
    <link rel="stylesheet" href="{{ asset('css/app.css', true) }}">

    {{-- Chart.js --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js" defer></script>

    {{-- Three.js --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>

    @stack('head')
</head>
<body>

    {{-- Header Navigasi --}}
    @include('components.header')

    {{-- Konten Utama --}}
    @yield('content')

    {{-- Footer --}}
    @include('components.footer')

    {{-- Toast Notifikasi --}}
    <div id="toast"></div>

    {{-- Scripts Global --}}
    <script>
        // ── Konfigurasi Global ────────────────────────────────────────────────
        window.APP = {
            csrfToken : '{{ csrf_token() }}',
            apiBase   : '{{ url("/api") }}',
        };
    </script>

    @stack('scripts')

</body>
</html>
