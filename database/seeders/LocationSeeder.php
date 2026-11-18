<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\TravelTime;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $locations = [
            'BASE' => ['Sanggar Yeni Wedding', 'Kota Tasikmalaya', 'Jl. Sutisna Senjaya No. 45, Cihideung', -7.3274, 108.2207, true],
            'TSK' => ['Gedung Serbaguna Cihideung', 'Kota Tasikmalaya', 'Jl. HZ. Mustofa, Cihideung', -7.3260, 108.2196, false],
            'SGP' => ['Kediaman Mempelai Singaparna', 'Kabupaten Tasikmalaya', 'Kp. Cipasung, Singaparna', -7.3517, 108.1105, false],
            'CMS' => ['Aula Desa Cijeungjing', 'Kabupaten Ciamis', 'Jl. Raya Ciamis-Banjar, Cijeungjing', -7.3337, 108.3724, false],
            'GRT' => ['Gedung Pendopo Garut', 'Kabupaten Garut', 'Jl. Ahmad Yani, Garut Kota', -7.2167, 107.9000, false],
            'BDG' => ['Gedung Pernikahan Buahbatu', 'Kota Bandung', 'Jl. Terusan Buahbatu, Bandung', -6.9500, 107.6400, false],
            'JKT' => ['Ballroom Kebayoran Baru', 'Jakarta Selatan', 'Jl. Wijaya I, Kebayoran Baru', -6.2440, 106.8000, false],
        ];

        $ids = [];
        foreach ($locations as $kode => [$nama, $kota, $alamat, $lat, $lng, $base]) {
            $ids[$kode] = Location::updateOrCreate(
                ['nama' => $nama, 'kota' => $kota],
                ['alamat' => $alamat, 'latitude' => $lat, 'longitude' => $lng, 'is_basecamp' => $base],
            )->id;
        }

        // Waktu tempuh (menit) - simetris. Persiapan default 30 menit, 45 untuk antarkota jauh.
        $matrix = [
            ['BASE', 'TSK', 10], ['BASE', 'SGP', 35], ['BASE', 'CMS', 45], ['BASE', 'GRT', 90],
            ['BASE', 'BDG', 180], ['BASE', 'JKT', 330],
            ['TSK', 'SGP', 30], ['TSK', 'CMS', 45], ['TSK', 'GRT', 90], ['TSK', 'BDG', 180], ['TSK', 'JKT', 330],
            ['SGP', 'CMS', 50], ['SGP', 'GRT', 75], ['SGP', 'BDG', 170], ['SGP', 'JKT', 320],
            ['CMS', 'GRT', 120], ['CMS', 'BDG', 210], ['CMS', 'JKT', 360],
            ['GRT', 'BDG', 90], ['GRT', 'JKT', 270],
            ['BDG', 'JKT', 150],
        ];

        foreach ($matrix as [$a, $b, $menit]) {
            $persiapan = $menit >= 150 ? 45 : 30;
            foreach ([[$a, $b], [$b, $a]] as [$from, $to]) {
                TravelTime::updateOrCreate(
                    ['from_location_id' => $ids[$from], 'to_location_id' => $ids[$to]],
                    ['waktu_tempuh_menit' => $menit, 'waktu_persiapan_menit' => $persiapan],
                );
            }
        }
    }
}
