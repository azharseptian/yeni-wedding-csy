<?php

namespace Database\Seeders;

use App\Enums\PriceType;
use App\Models\DynamicComponent;
use Illuminate\Database\Seeder;

class DynamicComponentSeeder extends Seeder
{
    public function run(): void
    {
        $components = [
            ['MLT-SGR', 'Bunga Melati Segar (ronce pengantin & sanggul)', 'bunga', PriceType::Flat, 1350000, 'set', null, null, 'Harga mengikuti pasar bunga; bisa > Rp1.000.000 saat musim nikah.'],
            ['MLT-PLM', 'Bunga Melati Segar Hiasan Pelaminan', 'bunga', PriceType::PerUnit, 450000, 'rangkai', null, null, null],
            ['BNG-MJA', 'Bunga Segar Meja Tamu', 'bunga', PriceType::PerUnit, 75000, 'vas', null, null, null],
            ['TND-DKR', 'Tenda Dekorasi Lorong', 'tenda', PriceType::PerCm, 2500, 'cm', 300, 3000, 'Total = harga_per_cm x panjang (cm).'],
            ['TND-PRS', 'Tenda Prasmanan / Plafon Rumahan', 'tenda', PriceType::PerCm, 1800, 'cm', 400, 4000, 'Total = harga_per_cm x panjang (cm).'],
            ['JNR-KNG', 'Janur Kuning Gerbang', 'dekorasi', PriceType::PerUnit, 350000, 'set', null, null, null],
            ['LMP-HIAS', 'Lampu Hias Taman', 'dekorasi', PriceType::PerUnit, 150000, 'titik', null, null, null],
        ];

        foreach ($components as [$kode, $nama, $kategori, $tipe, $harga, $satuan, $min, $max, $ket]) {
            DynamicComponent::updateOrCreate(['kode' => $kode], [
                'nama' => $nama,
                'kategori' => $kategori,
                'tipe_harga' => $tipe,
                'harga_satuan' => $harga,
                'satuan' => $satuan,
                'ukuran_min_cm' => $min,
                'ukuran_max_cm' => $max,
                'harga_diperbarui_at' => now()->subDays(3),
                'is_active' => true,
                'keterangan' => $ket,
            ]);
        }
    }
}
