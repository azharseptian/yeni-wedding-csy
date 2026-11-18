<?php

namespace Database\Seeders;

use App\Enums\ApprovalStatus;
use App\Enums\AvailabilityType;
use App\Enums\BookingStatus;
use App\Enums\JenisAcara;
use App\Enums\PaymentMethod;
use App\Enums\PaymentType;
use App\Enums\PriceType;
use App\Enums\ScheduleRole;
use App\Enums\ScheduleStatus;
use App\Enums\SnapshotItemType;
use App\Enums\VerificationStatus;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Client;
use App\Models\DynamicComponent;
use App\Models\Inventory;
use App\Models\Location;
use App\Models\Package;
use App\Models\Schedule;
use App\Models\User;
use App\Models\VendorAvailabilityRequest;
use App\Models\VendorService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Data contoh yang mencakup seluruh siklus status, price-lock berversi,
 * skenario rias estafet dini hari 3 lokasi, kapasitas kru + override, dan checklist inventaris.
 * Semua tanggal relatif terhadap hari ini agar skenario H-7 selalu relevan.
 */
class BookingSeeder extends Seeder
{
    private const LOKASI = [
        'TSK' => 'Gedung Serbaguna Cihideung',
        'SGP' => 'Kediaman Mempelai Singaparna',
        'CMS' => 'Aula Desa Cijeungjing',
        'GRT' => 'Gedung Pendopo Garut',
        'BDG' => 'Gedung Pernikahan Buahbatu',
        'JKT' => 'Ballroom Kebayoran Baru',
    ];

    private int $adminId;

    private int $urut = 0;

    public function run(): void
    {
        $this->adminId = User::where('email', 'admin@yeniwedding.test')->value('id');
        $today = Carbon::today();
        $hariEstafet = $today->copy()->addDays(20);

        $b = [];

        $b['penawaran'] = $this->booking([
            'client' => 'Ayu Lestari', 'package' => ['PKT-MNG', 1], 'lokasi' => 'BDG',
            'jenis' => JenisAcara::AkadResepsi, 'tanggal' => $today->copy()->addDays(45), 'jam' => ['08:00', '14:00'], 'tamu' => 400,
            'status' => BookingStatus::Penawaran, 'dibuat' => 2,
            'komponen' => [['MLT-SGR', 1], ['TND-DKR', 1, 800], ['BNG-MJA', 20]],
            'vendor' => [['Catering Prasmanan Menu Sunda', 400], ['MC Akad & Resepsi (Bahasa Sunda/Indonesia)', 1]],
            'payments' => [],
        ]);

        // --- Skenario estafet dini hari: 3 klien di 3 lokasi pada tanggal yang sama ---
        $b['estafet1'] = $this->booking([
            'client' => 'Siti Nurhaliza', 'package' => ['PKT-PRM', 1], 'lokasi' => 'TSK',
            'jenis' => JenisAcara::AkadResepsi, 'tanggal' => $hariEstafet, 'jam' => ['07:00', '14:00'], 'tamu' => 500,
            'status' => BookingStatus::Terkonfirmasi, 'dibuat' => 40,
            'komponen' => [['MLT-SGR', 1], ['MLT-PLM', 2], ['TND-DKR', 1, 1200], ['JNR-KNG', 1]],
            'vendor' => [['Catering Prasmanan Menu Sunda', 500, 1], ['MC Akad & Resepsi (Bahasa Sunda/Indonesia)', 1], ['Dokumentasi Foto (1 fotografer + album)', 1]],
            'payments' => [[PaymentType::Dp, 5000000, PaymentMethod::Transfer, 35]],
        ]);

        $b['estafet2'] = $this->booking([
            'client' => 'Nurul Fitri', 'package' => ['PKT-SDH', 2], 'lokasi' => 'SGP',
            'jenis' => JenisAcara::AkadResepsi, 'tanggal' => $hariEstafet, 'jam' => ['08:00', '13:00'], 'tamu' => 300,
            'status' => BookingStatus::Terkonfirmasi, 'dibuat' => 30,
            'komponen' => [['MLT-SGR', 1], ['TND-PRS', 1, 600]],
            'vendor' => [['Among Tamu (4 orang, busana seragam)', 1]],
            'payments' => [[PaymentType::Dp, 1500000, PaymentMethod::Tunai, 28]],
        ]);

        $b['estafet3'] = $this->booking([
            'client' => 'Intan Permata', 'package' => ['PKT-AKD', 1], 'lokasi' => 'CMS',
            'jenis' => JenisAcara::Akad, 'tanggal' => $hariEstafet, 'jam' => ['10:00', '12:00'], 'tamu' => 100,
            'status' => BookingStatus::Terkonfirmasi, 'dibuat' => 25,
            'komponen' => [['MLT-SGR', 1]],
            'vendor' => [['Nasi Box Akad', 100]],
            'payments' => [
                [PaymentType::Dp, 1000000, PaymentMethod::Transfer, 24],
                [PaymentType::Cicilan, 2000000, PaymentMethod::Transfer, 1, VerificationStatus::Menunggu],
            ],
        ]);

        // --- H-7 sudah lewat, pembayaran baru 50% -> Belum Memenuhi Ambang ---
        $b['ambang'] = $this->booking([
            'client' => 'Rini Andriani', 'package' => ['PKT-MNG', 1], 'lokasi' => 'GRT',
            'jenis' => JenisAcara::AkadResepsi, 'tanggal' => $today->copy()->addDays(5), 'jam' => ['08:00', '14:00'], 'tamu' => 350,
            'status' => BookingStatus::BelumMemenuhiAmbang, 'dibuat' => 60,
            'komponen' => [['MLT-SGR', 1], ['TND-DKR', 1, 1000]],
            'vendor' => [['Catering Prasmanan Menu Sunda', 350]],
            'payments' => [
                [PaymentType::Dp, 3000000, PaymentMethod::Transfer, 55],
                [PaymentType::Cicilan, 'persen:50', PaymentMethod::Tunai, 20],
            ],
        ]);

        // --- H-7 lewat, pembayaran 85% -> Siap Pelaksanaan ---
        $b['siap'] = $this->booking([
            'client' => 'Lestari Wulandari', 'package' => ['PKT-PRM', 1], 'lokasi' => 'JKT',
            'jenis' => JenisAcara::AkadResepsi, 'tanggal' => $today->copy()->addDays(4), 'jam' => ['09:00', '15:00'], 'tamu' => 600,
            'status' => BookingStatus::SiapPelaksanaan, 'dibuat' => 90,
            'komponen' => [['MLT-SGR', 1], ['MLT-PLM', 3], ['TND-DKR', 1, 1500]],
            'vendor' => [['Dokumentasi Foto (1 fotografer + album)', 1], ['Dokumentasi Video Cinematic', 1]],
            'payments' => [
                [PaymentType::Dp, 10000000, PaymentMethod::Transfer, 85],
                [PaymentType::Cicilan, 'persen:85', PaymentMethod::Transfer, 12],
            ],
        ]);

        // --- Acara selesai & lunas bulan ini ---
        $b['lunas'] = $this->booking([
            'client' => 'Wulan Septiani', 'package' => ['PKT-MNG', 1], 'lokasi' => 'TSK',
            'jenis' => JenisAcara::AkadResepsi, 'tanggal' => $today->copy()->subDays(10), 'jam' => ['08:00', '14:00'], 'tamu' => 450,
            'status' => BookingStatus::Lunas, 'dibuat' => 100,
            'komponen' => [['MLT-SGR', 1], ['TND-PRS', 1, 800], ['LMP-HIAS', 6]],
            'vendor' => [['Catering Prasmanan Menu Sunda', 450], ['MC Akad & Resepsi (Bahasa Sunda/Indonesia)', 1]],
            'payments' => [
                [PaymentType::Dp, 4000000, PaymentMethod::Transfer, 95],
                [PaymentType::Cicilan, 'persen:80', PaymentMethod::Transfer, 20],
                [PaymentType::Pelunasan, 'sisa', PaymentMethod::Tunai, 11],
            ],
        ]);

        // --- Lunas bulan lalu, terkunci pada harga lama (Paket Sederhana v1 & melati Rp1.150.000) ---
        $b['lunasLama'] = $this->booking([
            'client' => 'Dewi Anggraeni', 'package' => ['PKT-SDH', 1], 'lokasi' => 'TSK',
            'jenis' => JenisAcara::AkadResepsi, 'tanggal' => $today->copy()->subDays(35), 'jam' => ['08:00', '13:00'], 'tamu' => 250,
            'status' => BookingStatus::Lunas, 'dibuat' => 150,
            'komponen' => [['MLT-SGR', 1, null, 1150000], ['TND-DKR', 1, 600, 2200]],
            'vendor' => [['Nasi Box Akad', 250]],
            'payments' => [
                [PaymentType::Dp, 2000000, PaymentMethod::Tunai, 145],
                [PaymentType::Pelunasan, 'sisa', PaymentMethod::Transfer, 45],
            ],
        ]);

        // --- Dibatalkan klien setelah DP -> refund 100% ---
        $b['batal'] = $this->booking([
            'client' => 'Mega Puspita', 'package' => ['PKT-SDH', 2], 'lokasi' => 'BDG',
            'jenis' => JenisAcara::AkadResepsi, 'tanggal' => $today->copy()->addDays(60), 'jam' => ['08:00', '13:00'], 'tamu' => 300,
            'status' => BookingStatus::BatalRefund, 'dibuat' => 30,
            'komponen' => [['MLT-SGR', 1], ['TND-DKR', 1, 600]],
            'vendor' => [],
            'payments' => [[PaymentType::Dp, 2000000, PaymentMethod::Transfer, 28]],
            'batal' => ['hari_lalu' => 3, 'alasan' => 'Keluarga memutuskan menunda pernikahan.'],
        ]);

        $this->seedRevisi($b['estafet1']);
        $this->seedSchedules($b, $hariEstafet, $today);
        $this->seedInventories($b);
        $this->seedAvailabilityRequests($today);
    }

    private function booking(array $s): Booking
    {
        $client = Client::where('nama_pengantin_wanita', $s['client'])->firstOrFail();
        $package = Package::where('kode', $s['package'][0])->where('versi', $s['package'][1])->firstOrFail();
        $tanggal = $s['tanggal']->copy();
        $dibuat = Carbon::now()->subDays($s['dibuat']);
        $status = $s['status'];

        $booking = Booking::create([
            'kode_booking' => sprintf('YW-%s-%04d', $dibuat->format('Y'), ++$this->urut),
            'client_id' => $client->id,
            'package_id' => $package->id,
            'location_id' => Location::where('nama', self::LOKASI[$s['lokasi']])->value('id'),
            'jenis_acara' => $s['jenis'],
            'tanggal_acara' => $tanggal,
            'jam_mulai_acara' => $s['jam'][0],
            'jam_selesai_acara' => $s['jam'][1],
            'alamat_acara' => $client->alamat,
            'jumlah_tamu' => $s['tamu'],
            'status' => BookingStatus::Penawaran,
            'ambang_pelunasan_persen' => 80,
            'batas_pelunasan' => $tanggal->copy()->subDays(7),
            'created_by' => $this->adminId,
            'created_at' => $dibuat,
            'updated_at' => $dibuat,
        ]);

        $this->createItems($booking, $package, $s['komponen'], $s['vendor']);
        $booking->refresh();
        $total = (float) $booking->total_tagihan;

        $dibayar = 0.0;
        $tanggalDp = null;
        $tanggalTerakhir = null;
        foreach ($s['payments'] as $p) {
            [$jenis, $nominal, $metode, $hariLalu] = $p;
            $verifikasi = $p[4] ?? VerificationStatus::Terverifikasi;

            $nominal = match (true) {
                $nominal === 'sisa' => $total - $dibayar,
                is_string($nominal) => round($total * (int) substr($nominal, 7) / 100, -3) - $dibayar,
                default => (float) $nominal,
            };

            $tglBayar = Carbon::today()->subDays($hariLalu);
            $booking->payments()->create([
                'jenis' => $jenis,
                'nominal' => $nominal,
                'tanggal_bayar' => $tglBayar,
                'metode' => $metode,
                'bank_pengirim' => $metode === PaymentMethod::Transfer ? 'BCA' : null,
                'nama_pengirim' => $metode === PaymentMethod::Transfer ? $client->nama_kontak : null,
                'bukti_transfer_path' => $metode === PaymentMethod::Transfer
                    ? "bukti-transfer/{$booking->kode_booking}-{$jenis->value}-{$hariLalu}.jpg"
                    : null,
                'penerima_id' => $verifikasi === VerificationStatus::Terverifikasi ? $this->adminId : null,
                'diunggah_oleh' => $client->user_id && $metode === PaymentMethod::Transfer ? $client->user_id : $this->adminId,
                'status_verifikasi' => $verifikasi,
                'diverifikasi_at' => $verifikasi === VerificationStatus::Terverifikasi ? $tglBayar->copy()->setTime(15, 0) : null,
                'created_at' => $tglBayar,
                'updated_at' => $tglBayar,
            ]);

            if ($verifikasi === VerificationStatus::Terverifikasi) {
                $dibayar += $nominal;
                $tanggalDp ??= $tglBayar;
                $tanggalTerakhir = $tglBayar;
            }
        }

        $attrs = ['status' => $status, 'total_dibayar' => $dibayar];

        if ($tanggalDp) {
            $kunci = $tanggalDp->copy()->setTime(15, 0);
            $attrs += [
                'is_date_locked' => true, 'date_locked_at' => $kunci,
                'is_price_locked' => true, 'price_locked_at' => $kunci,
                'dikonfirmasi_at' => $kunci,
            ];
            $booking->items()->update(['is_locked' => true, 'locked_at' => $kunci]);
        }

        $batas = $booking->batas_pelunasan->copy()->setTime(0, 5);
        if (in_array($status, [BookingStatus::BelumMemenuhiAmbang, BookingStatus::SiapPelaksanaan, BookingStatus::Lunas], true)) {
            $attrs['ambang_dicek_at'] = $batas;
        }
        if (in_array($status, [BookingStatus::SiapPelaksanaan, BookingStatus::Lunas], true)) {
            $attrs['siap_pelaksanaan_at'] = $batas;
        }
        if ($status === BookingStatus::Lunas) {
            $attrs['lunas_at'] = $tanggalTerakhir->copy()->setTime(16, 0);
        }

        if (isset($s['batal'])) {
            $tglBatal = Carbon::today()->subDays($s['batal']['hari_lalu']);
            $booking->refunds()->create([
                'nominal' => $dibayar,
                'tanggal_refund' => $tglBatal,
                'metode' => PaymentMethod::Transfer,
                'bank_tujuan' => 'BCA',
                'nomor_rekening_tujuan' => '7770123456',
                'atas_nama' => $client->nama_kontak,
                'bukti_refund_path' => "bukti-refund/{$booking->kode_booking}.jpg",
                'alasan' => $s['batal']['alasan'],
                'diproses_oleh' => $this->adminId,
            ]);
            $booking->items()->update(['is_locked' => false]);
            $attrs = array_merge($attrs, [
                'total_refund' => $dibayar,
                'is_date_locked' => false,
                'is_price_locked' => false,
                'dibatalkan_at' => $tglBatal->copy()->setTime(10, 0),
                'alasan_batal' => $s['batal']['alasan'],
            ]);
            AuditLog::create([
                'user_id' => $this->adminId,
                'event' => 'booking.status',
                'auditable_type' => $booking->getMorphClass(),
                'auditable_id' => $booking->id,
                'old_values' => ['status' => BookingStatus::Terkonfirmasi->value, 'is_date_locked' => true, 'is_price_locked' => true],
                'new_values' => ['status' => BookingStatus::BatalRefund->value, 'is_date_locked' => false, 'is_price_locked' => false, 'refund' => $dibayar],
                'keterangan' => 'Dibatalkan klien. DP dikembalikan 100%, tanggal & harga dilepas.',
                'created_at' => $tglBatal->copy()->setTime(10, 0),
            ]);
        }

        $booking->update($attrs);

        return $booking->refresh();
    }

    /**
     * Komponen: [kode, kuantitas, ukuran_cm?, harga_override?]. Vendor: [nama_layanan, kuantitas, revisi_ke?].
     */
    private function createItems(Booking $booking, Package $package, array $komponen, array $vendor): void
    {
        $booking->items()->create([
            'item_type' => SnapshotItemType::Paket,
            'source_type' => $package->getMorphClass(),
            'source_id' => $package->id,
            'nama_item' => "{$package->nama} (v{$package->versi})",
            'tipe_harga' => PriceType::Flat,
            'kuantitas' => 1,
            'satuan' => 'paket',
            'harga_satuan_klien' => $package->harga_dasar,
            'subtotal_klien' => $package->harga_dasar,
            'versi_paket' => $package->versi,
            'snapshot_data' => $package->only(['kode', 'versi', 'nama', 'harga_dasar', 'rincian']),
        ]);

        foreach ($komponen as $k) {
            $c = DynamicComponent::where('kode', $k[0])->firstOrFail();
            $qty = $k[1];
            $ukuran = $k[2] ?? null;
            $harga = (float) ($k[3] ?? $c->harga_satuan);
            $subtotal = $c->tipe_harga === PriceType::PerCm ? $harga * $ukuran * $qty : $harga * $qty;

            $booking->items()->create([
                'item_type' => SnapshotItemType::KomponenDinamis,
                'source_type' => $c->getMorphClass(),
                'source_id' => $c->id,
                'nama_item' => $ukuran ? "{$c->nama} ({$ukuran} cm)" : $c->nama,
                'tipe_harga' => $c->tipe_harga,
                'kuantitas' => $qty,
                'satuan' => $c->satuan,
                'ukuran_cm' => $ukuran,
                'harga_satuan_klien' => $harga,
                'subtotal_klien' => $subtotal,
                'snapshot_data' => ['kode' => $c->kode, 'harga_satuan' => $harga, 'tipe_harga' => $c->tipe_harga->value],
            ]);
        }

        foreach ($vendor as $v) {
            $svc = VendorService::where('nama_layanan', $v[0])->firstOrFail();
            $qty = $v[1];
            $subKlien = (float) $svc->tarif_klien * $qty;
            $subVendor = (float) $svc->tarif_vendor * $qty;

            $booking->items()->create([
                'item_type' => SnapshotItemType::LayananVendor,
                'source_type' => $svc->getMorphClass(),
                'source_id' => $svc->id,
                'nama_item' => $svc->nama_layanan,
                'tipe_harga' => PriceType::PerUnit,
                'kuantitas' => $qty,
                'satuan' => $svc->satuan,
                'harga_satuan_klien' => $svc->tarif_klien,
                'tarif_vendor_satuan' => $svc->tarif_vendor,
                'subtotal_klien' => $subKlien,
                'subtotal_vendor' => $subVendor,
                'margin' => $subKlien - $subVendor,
                'revisi_ke' => $v[2] ?? 0,
                'snapshot_data' => ['vendor_user_id' => $svc->vendor_user_id, 'tarif_klien' => $svc->tarif_klien, 'tarif_vendor' => $svc->tarif_vendor],
            ]);
        }

        $booking->update([
            'total_tagihan' => $booking->items()->sum('subtotal_klien'),
            'total_biaya_vendor' => $booking->items()->sum('subtotal_vendor'),
            'total_margin' => $booking->items()->sum('margin'),
        ]);
    }

    /** Revisi lingkup setelah price-lock: catering 450 -> 500 porsi (sudah tercermin di snapshot). */
    private function seedRevisi(Booking $booking): void
    {
        $item = $booking->items()->where('nama_item', 'Catering Prasmanan Menu Sunda')->first();
        $tambahan = 50 * (float) $item->harga_satuan_klien;

        $booking->update(['revisi_ke' => 1]);
        AuditLog::create([
            'user_id' => $this->adminId,
            'event' => 'booking.revisi',
            'auditable_type' => $booking->getMorphClass(),
            'auditable_id' => $booking->id,
            'old_values' => ['item' => $item->nama_item, 'kuantitas' => 450, 'total_tagihan' => (float) $booking->total_tagihan - $tambahan],
            'new_values' => ['item' => $item->nama_item, 'kuantitas' => 500, 'total_tagihan' => (float) $booking->total_tagihan],
            'keterangan' => 'Klien menambah 50 porsi catering (jumlah tamu bertambah). Harga satuan tetap mengikuti snapshot terkunci.',
            'created_at' => now()->subDays(10),
        ]);
    }

    private function seedSchedules(array $b, Carbon $hariEstafet, Carbon $today): void
    {
        $user = fn (string $email) => User::where('email', $email)->value('id');
        $lokasi = fn (string $kode) => Location::where('nama', self::LOKASI[$kode])->value('id');

        $add = function (Booking $booking, ?string $email, ScheduleRole $peran, Carbon $tgl, string $mulai, string $selesai, array $extra = []) use ($user) {
            $status = $tgl->isPast() && ! $tgl->isToday() ? ScheduleStatus::Selesai : ScheduleStatus::Dijadwalkan;

            return Schedule::create(array_merge([
                'booking_id' => $booking->id,
                'user_id' => $email ? $user($email) : null,
                'location_id' => $booking->location_id,
                'peran' => $peran,
                'tanggal' => $tgl->toDateString(),
                'waktu_mulai' => $tgl->copy()->setTimeFromTimeString($mulai),
                'waktu_selesai' => $tgl->copy()->setTimeFromTimeString($selesai),
                'status' => $status,
                'created_by' => $this->adminId,
            ], $extra));
        };

        // Rias estafet dini hari (MUA & asisten): TSK 02:00-04:30 -> SGP 05:30-07:30 -> CMS 09:00-11:00.
        // TSK->SGP: 30 tempuh + 30 persiapan = 60 mnt (04:30 + 60 = 05:30 OK).
        // SGP->CMS: 50 tempuh + 30 persiapan = 80 mnt (07:30 + 80 = 08:50 <= 09:00 OK).
        foreach (['rina.mua@yeniwedding.test' => ScheduleRole::Mua, 'dewi.rias@yeniwedding.test' => ScheduleRole::AsistenRias] as $email => $peran) {
            $add($b['estafet1'], $email, $peran, $hariEstafet, '02:00', '04:30');
            $add($b['estafet2'], $email, $peran, $hariEstafet, '05:30', '07:30');
            $add($b['estafet3'], $email, $peran, $hariEstafet, '09:00', '11:00');
        }

        // Pemasangan dekorasi H-1 hari estafet: 5 orang = tepat kapasitas.
        $h1 = $hariEstafet->copy()->subDay();
        $add($b['estafet1'], 'asep.dekor@yeniwedding.test', ScheduleRole::Dekorator, $h1, '08:00', '17:00');
        $add($b['estafet1'], 'ujang.kru@yeniwedding.test', ScheduleRole::KruDekorasi, $h1, '08:00', '17:00');
        $add($b['estafet1'], 'dadan.kru@yeniwedding.test', ScheduleRole::KruDekorasi, $h1, '08:00', '17:00');
        $add($b['estafet2'], 'cecep.kru@yeniwedding.test', ScheduleRole::KruDekorasi, $h1, '09:00', '16:00');
        $add($b['estafet2'], 'endang.kru@yeniwedding.test', ScheduleRole::KruDekorasi, $h1, '09:00', '16:00');
        $add($b['estafet3'], 'yayan.kru@yeniwedding.test', ScheduleRole::KruDekorasi, $hariEstafet, '07:00', '09:00');
        $add($b['estafet3'], null, ScheduleRole::KruDekorasi, $hariEstafet, '07:00', '09:00', [
            'is_tenaga_lepas' => true, 'nama_tenaga_lepas' => 'Jajang Nurjaman', 'phone_tenaga_lepas' => '6285799990001',
        ]);

        // Vendor keluarga pada hari estafet.
        $catering = VendorService::where('nama_layanan', 'Catering Prasmanan Menu Sunda')->first();
        $mc = VendorService::where('nama_layanan', 'MC Akad & Resepsi (Bahasa Sunda/Indonesia)')->first();
        $amongTamu = VendorService::where('nama_layanan', 'Among Tamu (4 orang, busana seragam)')->first();
        $foto = VendorService::where('nama_layanan', 'Dokumentasi Foto (1 fotografer + album)')->first();
        $video = VendorService::where('nama_layanan', 'Dokumentasi Video Cinematic')->first();

        $add($b['estafet1'], 'dodi.catering@yeniwedding.test', ScheduleRole::Vendor, $hariEstafet, '09:00', '14:00', ['vendor_service_id' => $catering->id]);
        $add($b['estafet1'], 'euis.mc@yeniwedding.test', ScheduleRole::Mc, $hariEstafet, '07:00', '14:00', ['vendor_service_id' => $mc->id]);
        $add($b['estafet1'], 'ade.foto@yeniwedding.test', ScheduleRole::Vendor, $hariEstafet, '06:00', '14:00', ['vendor_service_id' => $foto->id]);
        $add($b['estafet2'], null, ScheduleRole::AmongTamu, $hariEstafet, '08:00', '13:00', ['vendor_service_id' => $amongTamu->id, 'catatan' => 'Tim among tamu Bu Euis']);

        // Acara Jakarta (H+4) & Garut (H+5).
        $add($b['siap'], 'rina.mua@yeniwedding.test', ScheduleRole::Mua, $today->copy()->addDays(4), '03:00', '06:00');
        $add($b['siap'], 'dewi.rias@yeniwedding.test', ScheduleRole::AsistenRias, $today->copy()->addDays(4), '03:00', '06:00');
        $add($b['siap'], 'ade.foto@yeniwedding.test', ScheduleRole::Vendor, $today->copy()->addDays(4), '07:00', '15:00', ['vendor_service_id' => $video->id]);
        $add($b['ambang'], 'rina.mua@yeniwedding.test', ScheduleRole::Mua, $today->copy()->addDays(5), '03:00', '05:30');
        $add($b['ambang'], 'dodi.catering@yeniwedding.test', ScheduleRole::Vendor, $today->copy()->addDays(5), '09:00', '14:00', ['vendor_service_id' => $catering->id]);

        // Dekorasi berjenjang H+3: Jakarta (4 orang) + Garut (2 internal + 1 lepas) = 6 internal > kapasitas 5 -> override.
        $h3 = $today->copy()->addDays(3);
        $add($b['siap'], 'asep.dekor@yeniwedding.test', ScheduleRole::Dekorator, $h3, '08:00', '18:00');
        $add($b['siap'], 'ujang.kru@yeniwedding.test', ScheduleRole::KruDekorasi, $h3, '08:00', '18:00');
        $add($b['siap'], 'dadan.kru@yeniwedding.test', ScheduleRole::KruDekorasi, $h3, '08:00', '18:00');
        $add($b['siap'], 'cecep.kru@yeniwedding.test', ScheduleRole::KruDekorasi, $h3, '08:00', '18:00');
        $add($b['ambang'], 'endang.kru@yeniwedding.test', ScheduleRole::KruDekorasi, $h3, '09:00', '16:00');
        $add($b['ambang'], null, ScheduleRole::KruDekorasi, $h3, '09:00', '16:00', [
            'is_tenaga_lepas' => true, 'nama_tenaga_lepas' => 'Jajang Nurjaman', 'phone_tenaga_lepas' => '6285799990001',
        ]);
        $alasan = 'Dua lokasi berbeda kota pada hari yang sama; Yayan bersedia lembur dan tenaga lepas Jajang sudah dikonfirmasi.';
        $override = $add($b['ambang'], 'yayan.kru@yeniwedding.test', ScheduleRole::KruDekorasi, $h3, '09:00', '16:00', [
            'is_override' => true,
            'alasan_override' => $alasan,
            'override_by' => $this->adminId,
            'override_at' => now()->subDay(),
        ]);
        AuditLog::create([
            'user_id' => $this->adminId,
            'event' => 'schedule.override',
            'auditable_type' => $override->getMorphClass(),
            'auditable_id' => $override->id,
            'new_values' => ['tanggal' => $h3->toDateString(), 'kru_internal_terjadwal' => 6, 'kapasitas' => 5],
            'keterangan' => "BENTROK kapasitas kru dekorasi di-override. Alasan: {$alasan}",
            'created_at' => now()->subDay(),
        ]);

        // Acara yang sudah selesai.
        $lalu = $today->copy()->subDays(10);
        $add($b['lunas'], 'rina.mua@yeniwedding.test', ScheduleRole::Mua, $lalu, '04:00', '06:30');
        $add($b['lunas'], 'asep.dekor@yeniwedding.test', ScheduleRole::Dekorator, $lalu->copy()->subDay(), '08:00', '17:00');
        $add($b['lunas'], 'euis.mc@yeniwedding.test', ScheduleRole::Mc, $lalu, '08:00', '14:00', ['vendor_service_id' => $mc->id]);
    }

    private function seedInventories(array $b): void
    {
        $tim = User::where('email', 'dewi.rias@yeniwedding.test')->value('id');
        $kru = User::where('email', 'ujang.kru@yeniwedding.test')->value('id');

        $add = function (Booking $booking, string $kode, int $jumlah, array $extra = []) {
            $booking->inventories()->create(array_merge([
                'inventory_id' => Inventory::where('kode', $kode)->value('id'),
                'jumlah' => $jumlah,
            ], $extra));
        };

        // Acara mendatang (estafet klien 1): belum dikemas.
        foreach ([['AKS-BROS-EMS', 2], ['AKS-PENITI', 1], ['AKS-SIGER', 1], ['AKS-KEMBANG-GG', 1], ['BSN-KBY-M', 1], ['BSN-BSK-L', 1], ['DKR-KURSI-PLM', 1], ['DKR-BACKDROP', 1], ['RIAS-KIT', 1]] as [$kode, $jml]) {
            $add($b['estafet1'], $kode, $jml);
        }

        // Acara H+4 Jakarta: sebagian sudah dikemas keluar.
        $keluar = ['is_keluar' => true, 'keluar_at' => now()->subHours(3), 'dikeluarkan_oleh' => $tim];
        $add($b['siap'], 'AKS-BROS-EMS', 3, $keluar);
        $add($b['siap'], 'AKS-PENITI', 2, $keluar);
        $add($b['siap'], 'AKS-SIGER', 1, $keluar);
        $add($b['siap'], 'BSN-KBY-S', 1);
        $add($b['siap'], 'BSN-BSK-M', 1);
        $add($b['siap'], 'DKR-BACKDROP', 1, ['is_keluar' => true, 'keluar_at' => now()->subHours(2), 'dikeluarkan_oleh' => $kru]);

        // Acara selesai: keluar & kembali, 1 bros hilang.
        $lalu = Carbon::today()->subDays(10);
        $selesai = fn (int $kembali, ?string $catatan = null) => [
            'is_keluar' => true, 'keluar_at' => $lalu->copy()->subDay()->setTime(7, 0), 'dikeluarkan_oleh' => $tim,
            'is_kembali' => true, 'jumlah_kembali' => $kembali, 'kembali_at' => $lalu->copy()->setTime(18, 0), 'dikembalikan_oleh' => $tim,
            'kondisi_kembali' => 'baik', 'catatan' => $catatan,
        ];
        $add($b['lunas'], 'AKS-BROS-EMS', 2, $selesai(1, '1 bros tidak kembali, diduga tertinggal di lokasi.'));
        $add($b['lunas'], 'AKS-PENITI', 1, $selesai(1));
        $add($b['lunas'], 'BSN-KBY-L', 1, $selesai(1));
        $add($b['lunas'], 'BSN-BSK-XL', 1, $selesai(1));
        $add($b['lunas'], 'DKR-KURSI-PLM', 1, $selesai(1));
    }

    private function seedAvailabilityRequests(Carbon $today): void
    {
        $vendor = fn (string $email) => User::where('email', $email)->value('id');

        VendorAvailabilityRequest::create([
            'vendor_user_id' => $vendor('euis.mc@yeniwedding.test'),
            'tanggal' => $today->copy()->addDays(33),
            'ketersediaan' => AvailabilityType::TidakTersedia,
            'alasan' => 'Ada acara keluarga di Bandung.',
            'status' => ApprovalStatus::Menunggu,
        ]);

        VendorAvailabilityRequest::create([
            'vendor_user_id' => $vendor('dodi.catering@yeniwedding.test'),
            'tanggal' => $today->copy()->addDays(12),
            'jam_mulai' => '06:00',
            'jam_selesai' => '15:00',
            'ketersediaan' => AvailabilityType::Tersedia,
            'alasan' => 'Dapur siap untuk 2 acara sekaligus.',
            'status' => ApprovalStatus::Disetujui,
            'ditinjau_oleh' => $this->adminId,
            'ditinjau_at' => now()->subDays(2),
            'catatan_admin' => 'Oke, dicatat.',
        ]);
    }
}
