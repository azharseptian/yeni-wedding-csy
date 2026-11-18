<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    public function run(): void
    {
        $user = fn (string $email) => User::where('email', $email)->value('id');

        $clients = [
            ['siti.klien@example.com', 'Siti Nurhaliza', 'Rizky Pratama', 'Siti Nurhaliza', '6287811112222', 'Kota Tasikmalaya', 'Jl. Cihideung Balong No. 12'],
            ['ayu.klien@example.com', 'Ayu Lestari', 'Dimas Saputra', 'Ayu Lestari', '6287811113333', 'Kota Bandung', 'Jl. Buahbatu No. 101'],
            ['rini.klien@example.com', 'Rini Andriani', 'Yoga Permana', 'Rini Andriani', '6287811114444', 'Kabupaten Garut', 'Jl. Cimanuk No. 7, Tarogong'],
            [null, 'Nurul Fitri', 'Fajar Nugraha', 'Ibu Hj. Aisyah (Ibu mempelai wanita)', '6281311115555', 'Kabupaten Tasikmalaya', 'Kp. Cipasung, Singaparna'],
            [null, 'Intan Permata', 'Galih Ramadhan', 'Intan Permata', '6281311116666', 'Kabupaten Ciamis', 'Dusun Sukamaju, Cijeungjing'],
            [null, 'Lestari Wulandari', 'Hendra Kusuma', 'Lestari Wulandari', '6281311117777', 'Jakarta Selatan', 'Jl. Wijaya II No. 8, Kebayoran Baru'],
            [null, 'Wulan Septiani', 'Arif Hidayat', 'Wulan Septiani', '6281311118888', 'Kota Tasikmalaya', 'Jl. Mitra Batik No. 21'],
            [null, 'Mega Puspita', 'Andri Setiawan', 'Mega Puspita', '6281311119999', 'Kota Bandung', 'Jl. Soekarno-Hatta No. 300'],
            [null, 'Dewi Anggraeni', 'Taufik Hidayat', 'Dewi Anggraeni', '6281311110000', 'Kota Tasikmalaya', 'Jl. Siliwangi No. 5'],
        ];

        foreach ($clients as [$email, $wanita, $pria, $kontak, $phone, $kota, $alamat]) {
            Client::updateOrCreate(
                ['nama_pengantin_wanita' => $wanita, 'nama_pengantin_pria' => $pria],
                [
                    'user_id' => $email ? $user($email) : null,
                    'nama_kontak' => $kontak,
                    'phone' => $phone,
                    'email' => $email,
                    'kota' => $kota,
                    'alamat' => $alamat,
                ],
            );
        }
    }
}
