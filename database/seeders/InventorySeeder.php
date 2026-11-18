<?php

namespace Database\Seeders;

use App\Models\Inventory;
use Illuminate\Database\Seeder;

class InventorySeeder extends Seeder
{
    public function run(): void
    {
        // [kode, nama, kategori, ukuran, jumlah, satuan, rawan_hilang]
        $items = [
            ['AKS-BROS-EMS', 'Bros Pengantin Emas', 'aksesoris', null, 12, 'buah', true],
            ['AKS-PENITI', 'Peniti Hijab & Kebaya', 'aksesoris', null, 20, 'kotak', true],
            ['AKS-SIGER', 'Siger Sunda', 'aksesoris', null, 3, 'buah', true],
            ['AKS-KEMBANG-GG', 'Kembang Goyang', 'aksesoris', null, 10, 'set', true],
            ['AKS-MLT-IMT', 'Ronce Melati Imitasi (cadangan)', 'aksesoris', null, 6, 'set', true],
            ['AKS-SELOP', 'Selop Pengantin', 'aksesoris', null, 8, 'pasang', false],
            ['BSN-KBY-S', 'Kebaya Akad Putih', 'busana', 'S', 2, 'stel', false],
            ['BSN-KBY-M', 'Kebaya Akad Putih', 'busana', 'M', 3, 'stel', false],
            ['BSN-KBY-L', 'Kebaya Akad Putih', 'busana', 'L', 3, 'stel', false],
            ['BSN-KBY-XL', 'Kebaya Akad Putih', 'busana', 'XL', 2, 'stel', false],
            ['BSN-BSK-M', 'Beskap Pria Putih', 'busana', 'M', 2, 'stel', false],
            ['BSN-BSK-L', 'Beskap Pria Putih', 'busana', 'L', 3, 'stel', false],
            ['BSN-BSK-XL', 'Beskap Pria Putih', 'busana', 'XL', 2, 'stel', false],
            ['BSN-JARIK', 'Kain Jarik Batik', 'busana', null, 10, 'lembar', false],
            ['DKR-KURSI-PLM', 'Kursi Pelaminan Ukir', 'dekorasi', null, 3, 'set', false],
            ['DKR-BACKDROP', 'Backdrop Pelaminan Kayu', 'dekorasi', null, 4, 'set', false],
            ['DKR-KARPET', 'Karpet Merah 10 m', 'dekorasi', null, 4, 'gulung', false],
            ['RIAS-KIT', 'Koper Kit Rias Lengkap', 'perlengkapan_rias', null, 3, 'koper', false],
        ];

        foreach ($items as [$kode, $nama, $kategori, $ukuran, $jumlah, $satuan, $rawan]) {
            Inventory::updateOrCreate(['kode' => $kode], [
                'nama' => $nama,
                'kategori' => $kategori,
                'ukuran' => $ukuran,
                'jumlah_total' => $jumlah,
                'satuan' => $satuan,
                'is_rawan_hilang' => $rawan,
                'kondisi' => 'baik',
            ]);
        }
    }
}
