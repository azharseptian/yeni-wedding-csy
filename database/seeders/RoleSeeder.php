<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $deskripsi = [
            RoleName::Owner->value => 'Akses penuh: data klien, booking, harga, vendor, jadwal, parameter, dan laporan keuangan.',
            RoleName::TimInternal->value => 'Melihat jadwal penugasan pribadi dan mencentang checklist inventaris.',
            RoleName::VendorKeluarga->value => 'Melihat penugasan & tarif miliknya sendiri, mengajukan ketersediaan jadwal.',
            RoleName::Klien->value => 'Portal mandiri: katalog, booking daring, unggah bukti transfer, pantau status.',
        ];

        foreach (RoleName::cases() as $role) {
            Role::updateOrCreate(
                ['name' => $role->value],
                ['label' => $role->label(), 'deskripsi' => $deskripsi[$role->value]],
            );
        }
    }
}
