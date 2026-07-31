# ANTAM NPV — Kalkulator Investasi Emas Laravel

Kalkulator investasi emas ANTAM berbasis Laravel 11 dengan kalkulasi NPV/ROI,
data harga realtime, grafik proyeksi, dan riwayat analisis tersimpan di database.

---

## 🚀 Cara Install

### 1. Prasyarat

Pastikan sudah terinstall:
- PHP 8.2+  (via XAMPP: `C:\xampp\php`)
- Composer   (https://getcomposer.org)
- MySQL      (via XAMPP Control Panel → Start MySQL)

### 2. Buat Project Laravel

```bash
# Buat project baru
composer create-project laravel/laravel antam-laravel

# Masuk ke folder project
cd antam-laravel
```

### 3. Copy File dari Folder Ini

Copy **seluruh isi** folder ini ke dalam folder `antam-laravel` yang baru dibuat.
Ketika diminta "overwrite?", pilih **Yes to all**.

### 4. Konfigurasi Database

1. Buka XAMPP Control Panel → Start **Apache** dan **MySQL**
2. Buka http://localhost/phpmyadmin
3. Buat database baru bernama: `antam_npv`
4. Copy `.env.example` menjadi `.env`:
   ```bash
   copy .env.example .env
   ```

### 5. Generate App Key

```bash
php artisan key:generate
```

### 6. Jalankan Migration

```bash
php artisan migrate
```

### 7. Jalankan Server

```bash
php artisan serve
```

Buka browser: **http://localhost:8000**

---

## 📁 Struktur File yang Dibuat

```
app/
├── Http/Controllers/
│   ├── HomeController.php       ← Halaman utama
│   ├── NpvController.php        ← API kalkulasi NPV
│   ├── PriceController.php      ← API proxy harga realtime
│   └── AnalysisController.php   ← CRUD riwayat analisis + export CSV
├── Http/Requests/
│   ├── NpvCalculateRequest.php  ← Validasi input NPV
│   └── StoreAnalysisRequest.php ← Validasi input simpan
├── Models/
│   └── Analysis.php             ← Model riwayat analisis
└── Services/
    ├── NpvService.php           ← Logika kalkulasi NPV (PHP murni)
    └── PriceService.php         ← Fetch harga realtime server-side

database/migrations/
└── ..._create_analyses_table.php ← Tabel riwayat

resources/views/
├── layouts/app.blade.php        ← Layout utama
├── home.blade.php               ← Halaman utama
└── components/
    ├── header.blade.php
    ├── hero.blade.php
    ├── calculator.blade.php
    ├── chart-section.blade.php
    ├── history.blade.php
    └── footer.blade.php

public/
├── css/app.css                  ← Stylesheet
└── js/app.js                    ← JavaScript (8 modul)

routes/
├── web.php                      ← Web routes
└── api.php                      ← API routes
```

## ✨ Fitur

- **Harga Realtime** — Fetch server-side (tidak ada CORS issue), auto-refresh 30 detik
- **Kalkulasi NPV** — Diproses di server PHP, bukan di browser
- **Riwayat Database** — Disimpan ke MySQL (bukan localStorage)
- **Export CSV** — Download langsung dari server
- **Validasi Server** — Form Request dengan pesan error Bahasa Indonesia
- **Kode Bersih** — Service layer, Request classes, Model scopes
