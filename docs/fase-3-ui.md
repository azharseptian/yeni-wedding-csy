# Fase 3 — Dasbor per Peran & UI

Blade + Tailwind CSS v4 (Vite), tanpa framework JS. PDF memakai `barryvdh/laravel-dompdf`.

| Peran | URL | Isi |
|---|---|---|
| Owner/Admin | `/admin` | Dasbor + pintasan cepat, peringatan Belum Memenuhi Ambang (tombol WA), verifikasi bukti transfer, deteksi bentrok 14 hari, pesanan (buat, detail, catat pembayaran, revisi lingkup, pembatalan + refund, penugasan + override), jadwal kalender, harga & vendor (paket berversi, komponen, dual-pricing), ketersediaan vendor, inventaris, laporan keuangan (PDF/CSV), pengaturan (parameter, waktu tempuh, lokasi), log aktivitas. |
| Tim MUA/Dekorasi | `/tim` | Mobile-first: jadwal pribadi per hari, detail acara + Google Maps, checklist barang keluar/kembali (jumlah kembali). Tanpa harga. |
| Vendor Keluarga | `/vendor` | Huruf & tombol besar: tugas saya, tarif layanan miliknya sendiri (tanpa tarif klien/margin), tombol "Saya Bisa / Tidak Bisa" (menunggu persetujuan Admin). |
| Calon Pengantin | `/`, `/daftar`, `/portal` | Katalog harga klien, daftar mandiri, formulir pemesanan daring, unggah bukti transfer, progres pembayaran & ambang, jadwal tim, unduh Lembar Kesepakatan. |

## RBAC
- Middleware `role:<peran>` per grup route; pesanan klien & checklist tim dicek kepemilikannya (403).
- `tarif_vendor`/margin tersembunyi di model; hanya dibuka eksplisit di halaman Owner. Laporan keuangan hanya di grup `role:owner`.

## PDF
- **Lembar Kesepakatan** (`/pesanan/{id}/kesepakatan.pdf`): Owner atau klien pemilik, setelah DP terverifikasi.
- **Laporan Keuangan** (`/admin/laporan/pdf?bulan=YYYY-MM`) + ekspor CSV untuk Excel.

## Menjalankan
```
composer install && npm install && npm run build
php artisan migrate:fresh --seed
php artisan serve
```
Akun contoh (kata sandi `password`): owner@yeniwedding.test, rina.mua@yeniwedding.test, dodi.catering@yeniwedding.test, siti.klien@example.com.

## Pengujian
`php artisan test` — 61 test, termasuk `tests/Feature/Http/RoleAccessTest.php` (akses tiap peran, kebocoran tarif vendor, alur daftar→pesan→unggah bukti→verifikasi, bentrok→override, PDF).
