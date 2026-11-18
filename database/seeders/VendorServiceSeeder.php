<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\VendorService;
use Illuminate\Database\Seeder;

class VendorServiceSeeder extends Seeder
{
    public function run(): void
    {
        $vendor = fn (string $email) => User::where('email', $email)->value('id');

        // [vendor, layanan, kategori, satuan, tarif_vendor (modal), tarif_klien (jual)]
        $services = [
            ['dodi.catering@yeniwedding.test', 'Catering Prasmanan Menu Sunda', 'catering', 'porsi', 22000, 30000],
            ['dodi.catering@yeniwedding.test', 'Nasi Box Akad', 'catering', 'box', 17000, 23000],
            ['euis.mc@yeniwedding.test', 'MC Akad & Resepsi (Bahasa Sunda/Indonesia)', 'mc', 'acara', 600000, 1000000],
            ['euis.mc@yeniwedding.test', 'Among Tamu (4 orang, busana seragam)', 'among_tamu', 'tim', 400000, 700000],
            ['ade.foto@yeniwedding.test', 'Dokumentasi Foto (1 fotografer + album)', 'dokumentasi', 'acara', 1500000, 2300000],
            ['ade.foto@yeniwedding.test', 'Dokumentasi Video Cinematic', 'dokumentasi', 'acara', 2000000, 3000000],
        ];

        foreach ($services as [$email, $nama, $kategori, $satuan, $tarifVendor, $tarifKlien]) {
            VendorService::updateOrCreate(
                ['vendor_user_id' => $vendor($email), 'nama_layanan' => $nama],
                ['kategori' => $kategori, 'satuan' => $satuan, 'tarif_vendor' => $tarifVendor, 'tarif_klien' => $tarifKlien, 'is_active' => true],
            );
        }
    }
}
