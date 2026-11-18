<?php

namespace Database\Seeders;

use App\Enums\JabatanInternal;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Semua akun contoh memakai kata sandi: password
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        $roles = Role::pluck('id', 'name');
        $password = Hash::make('password');

        $users = [
            [RoleName::Owner, null, 'Yeni Rahmawati', 'owner@yeniwedding.test', '6281223456789'],
            [RoleName::Owner, null, 'Admin Yeni Wedding', 'admin@yeniwedding.test', '6281223456790'],

            [RoleName::TimInternal, JabatanInternal::Mua, 'Rina Marlina', 'rina.mua@yeniwedding.test', '6285211110001'],
            [RoleName::TimInternal, JabatanInternal::AsistenRias, 'Dewi Sartika', 'dewi.rias@yeniwedding.test', '6285211110002'],
            [RoleName::TimInternal, JabatanInternal::Dekorator, 'Asep Saepudin', 'asep.dekor@yeniwedding.test', '6285211110003'],
            [RoleName::TimInternal, JabatanInternal::KruDekorasi, 'Ujang Suherman', 'ujang.kru@yeniwedding.test', '6285211110004'],
            [RoleName::TimInternal, JabatanInternal::KruDekorasi, 'Dadan Ramdani', 'dadan.kru@yeniwedding.test', '6285211110005'],
            [RoleName::TimInternal, JabatanInternal::KruDekorasi, 'Cecep Hidayat', 'cecep.kru@yeniwedding.test', '6285211110006'],
            [RoleName::TimInternal, JabatanInternal::KruDekorasi, 'Endang Kurnia', 'endang.kru@yeniwedding.test', '6285211110007'],
            [RoleName::TimInternal, JabatanInternal::KruDekorasi, 'Yayan Sopyan', 'yayan.kru@yeniwedding.test', '6285211110008'],

            [RoleName::VendorKeluarga, null, 'Pak Dodi (Catering Keluarga)', 'dodi.catering@yeniwedding.test', '6281322220001'],
            [RoleName::VendorKeluarga, null, 'Bu Euis (MC & Among Tamu)', 'euis.mc@yeniwedding.test', '6281322220002'],
            [RoleName::VendorKeluarga, null, 'Mang Ade (Dokumentasi)', 'ade.foto@yeniwedding.test', '6281322220003'],

            [RoleName::Klien, null, 'Siti Nurhaliza', 'siti.klien@example.com', '6287811112222'],
            [RoleName::Klien, null, 'Ayu Lestari', 'ayu.klien@example.com', '6287811113333'],
            [RoleName::Klien, null, 'Rini Andriani', 'rini.klien@example.com', '6287811114444'],
        ];

        foreach ($users as [$role, $jabatan, $name, $email, $phone]) {
            User::updateOrCreate(['email' => $email], [
                'role_id' => $roles[$role->value],
                'jabatan' => $jabatan,
                'name' => $name,
                'phone' => $phone,
                'password' => $password,
                'is_active' => true,
                'email_verified_at' => now(),
            ]);
        }
    }
}
