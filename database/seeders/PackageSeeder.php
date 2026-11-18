<?php

namespace Database\Seeders;

use App\Models\Package;
use Illuminate\Database\Seeder;

class PackageSeeder extends Seeder
{
    public function run(): void
    {
        $packages = [
            // Paket Sederhana versi 1 sudah tidak berlaku (harga naik di versi 2) -> contoh price-lock berversi.
            ['PKT-SDH', 1, 'Paket Sederhana', 8000000, '2025-01-01', '2026-06-30', false,
                ['Rias pengantin akad & resepsi (1x ganti busana)', 'Busana pengantin sepasang', 'Dekorasi pelaminan 4 meter', 'Kursi pelaminan', 'Rias orang tua (2 pasang)']],
            ['PKT-SDH', 2, 'Paket Sederhana', 8500000, '2026-07-01', null, true,
                ['Rias pengantin akad & resepsi (1x ganti busana)', 'Busana pengantin sepasang', 'Dekorasi pelaminan 4 meter', 'Kursi pelaminan', 'Rias orang tua (2 pasang)']],
            ['PKT-MNG', 1, 'Paket Menengah', 15000000, '2025-01-01', null, true,
                ['Rias pengantin akad & resepsi (2x ganti busana)', 'Busana pengantin sepasang + busana adat Sunda', 'Dekorasi pelaminan 6 meter', 'Gerbang janur & lorong', 'Rias orang tua (2 pasang)', 'Rias among tamu (4 orang)']],
            ['PKT-PRM', 1, 'Paket Premium', 28000000, '2025-01-01', null, true,
                ['Rias pengantin akad & resepsi (3x ganti busana)', 'Busana pengantin premium + siger Sunda', 'Dekorasi pelaminan 8 meter + photobooth', 'Gerbang janur, lorong & meja penerima tamu', 'Rias orang tua (2 pasang) & keluarga inti (6 orang)', 'Rias among tamu (6 orang)', 'Upacara adat Sunda (sawer & nincak endog)']],
            ['PKT-AKD', 1, 'Paket Akad Saja', 4500000, '2025-01-01', null, true,
                ['Rias pengantin akad', 'Busana akad sepasang', 'Dekorasi meja akad', 'Rias ibu mempelai (2 orang)']],
        ];

        foreach ($packages as [$kode, $versi, $nama, $harga, $mulai, $sampai, $aktif, $rincian]) {
            Package::updateOrCreate(['kode' => $kode, 'versi' => $versi], [
                'nama' => $nama,
                'deskripsi' => "{$nama} Yeni Wedding - rias & dekorasi khas Sunda.",
                'rincian' => $rincian,
                'harga_dasar' => $harga,
                'berlaku_mulai' => $mulai,
                'berlaku_sampai' => $sampai,
                'is_active' => $aktif,
            ]);
        }
    }
}
