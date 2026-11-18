<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            SystemParameterSeeder::class,
            LocationSeeder::class,
            UserSeeder::class,
            ClientSeeder::class,
            PackageSeeder::class,
            DynamicComponentSeeder::class,
            VendorServiceSeeder::class,
            InventorySeeder::class,
            BookingSeeder::class,
        ]);
    }
}
