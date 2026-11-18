# Fase 2 — Core Backend Logic & Algorithms

| Service | Lokasi | Aturan |
|---|---|---|
| `ConflictDetectionService` | `app/Services/Scheduling` | Rumus bentrok `s(i) < e(j)+t(j,i) AND s(j) < e(i)+t(i,j)`; `t` = waktu tempuh + persiapan (`travel_times`, fallback parameter sistem); kapasitas 5 kru dekorasi internal/hari (tenaga lepas tidak dihitung); ketersediaan vendor yang disetujui; saran otomatis (geser jam, MUA alternatif, dekorasi bertahap H-2/H-1, tenaga lepas); override hanya Owner/Admin + alasan wajib + `audit_logs`. |
| `PricingCalculatorService` | `app/Services/Pricing` | Paket berversi, komponen flat/per unit/per cm (tenda = harga/cm × cm × qty, validasi rentang ukuran), melati harga pasar, dual-pricing vendor (margin = (tarif klien − tarif vendor) × qty). Snapshot `price-lock` saat DP pertama: harga master terbaru disalin lalu dikunci; perubahan master setelahnya tidak berpengaruh. Revisi lingkup: item lama tetap harga snapshot, item baru harga master saat ini, tercatat `booking.revisi`. |
| `PaymentStatusService` | `app/Services/Payment` | DP > 0 (tanpa minimal %); transfer wajib bukti; unggahan klien menunggu verifikasi Owner; nominal tidak boleh melebihi sisa; state machine Penawaran → Terkonfirmasi → (H-7) Belum Memenuhi Ambang / Siap Pelaksanaan → Lunas; booking baru ≤ H-7 wajib ≥ 80% agar terkonfirmasi; tautan `wa.me` pengingat; pembatalan → refund 100% dana terverifikasi, lepas kunci tanggal & harga, jadwal dibatalkan, `Batal - Refund`. |

## Penjadwal harian

`php artisan booking:cek-ambang [--tanggal=Y-m-d]` — terjadwal setiap hari 00:05 (`routes/console.php`). Mengevaluasi pesanan yang sudah melewati batas H-7, menandai **Belum Memenuhi Ambang**, dan menampilkan tautan WhatsApp. Tidak membatalkan otomatis.

Produksi: tambahkan cron `* * * * * php /path/artisan schedule:run`.

## Pengujian

`php artisan test` — 34 test (SQLite in-memory; juga lulus di MySQL 8):

- `ConflictDetectionServiceTest`: estafet 3 lokasi 0 bentrok, jeda kurang, lintas tengah malam, kapasitas 5/6 kru, override + log, non-owner ditolak, vendor tidak tersedia.
- `PricingCalculatorServiceTest`: tenda per cm, rentang ukuran, total + margin, price-lock tahan perubahan master, revisi lingkup, tarif vendor tersembunyi.
- `PaymentStatusServiceTest`: DP 0 ditolak, DP kecil mengunci, bukti transfer, verifikasi, H-7 < 80% + link WA, ≥ 80% → Siap Pelaksanaan → Lunas, booking ≤ H-7, refund 100%, perintah harian.
