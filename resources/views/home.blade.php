{{--
    home.blade.php
    Halaman utama ANTAM NPV — merakit semua komponen Blade.
--}}
@extends('layouts.app')

@section('title', 'ANTAM NPV — Kalkulator Investasi Emas Antam')
@section('description', 'Hitung Net Present Value investasi emas ANTAM dengan data pasar realtime. Proyeksi multi-skenario dan riwayat analisis.')

@section('content')

    {{-- Hero Section (3D canvas + price metrics + ticker) --}}
    @include('components.hero')

    {{-- Konten Utama --}}
    <div class="main">

        {{-- Section: Kalkulator & Hasil --}}
        @include('components.calculator')

        <div class="gold-line reveal"></div>

        {{-- Section: Grafik & Tabel Proyeksi --}}
        @include('components.chart-section')

        <div class="gold-line reveal"></div>

        {{-- Section: Riwayat Analisis (CRUD) --}}
        @include('components.history')

    </div>

@endsection

@push('scripts')
<script src="{{ asset('js/app.js') }}"></script>
@endpush
