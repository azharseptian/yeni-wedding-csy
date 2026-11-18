<?php

namespace Tests\Concerns;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Client;
use App\Models\DynamicComponent;
use App\Models\Location;
use App\Models\Package;
use App\Models\User;
use App\Models\VendorService;
use App\Services\Pricing\PricingCalculatorService;
use Database\Seeders\ClientSeeder;
use Database\Seeders\DynamicComponentSeeder;
use Database\Seeders\LocationSeeder;
use Database\Seeders\PackageSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SystemParameterSeeder;
use Database\Seeders\UserSeeder;
use Database\Seeders\VendorServiceSeeder;
use Illuminate\Support\Carbon;

trait BuildsBookings
{
    private int $kodeUrut = 0;

    protected function seedMasterData(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-18 10:00:00'));
        $this->seed([
            RoleSeeder::class, SystemParameterSeeder::class, LocationSeeder::class, UserSeeder::class,
            ClientSeeder::class, PackageSeeder::class, DynamicComponentSeeder::class, VendorServiceSeeder::class,
        ]);
    }

    protected function owner(): User
    {
        return $this->user('owner@yeniwedding.test');
    }

    protected function user(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    protected function loc(string $nama): Location
    {
        return Location::where('nama', 'like', "%{$nama}%")->firstOrFail();
    }

    protected function komponen(string $kode): DynamicComponent
    {
        return DynamicComponent::where('kode', $kode)->firstOrFail();
    }

    protected function service(string $nama): VendorService
    {
        return VendorService::where('nama_layanan', 'like', "{$nama}%")->firstOrFail();
    }

    protected function makeBooking(array $attrs = [], ?array $komponen = null, array $vendor = [], string $paket = 'PKT-MNG'): Booking
    {
        $tanggal = Carbon::parse($attrs['tanggal_acara'] ?? Carbon::today()->addDays(30));
        $package = Package::active()->where('kode', $paket)->orderByDesc('versi')->firstOrFail();

        $booking = Booking::create(array_merge([
            'kode_booking' => 'YW-TEST-'.str_pad((string) ++$this->kodeUrut, 4, '0', STR_PAD_LEFT),
            'client_id' => Client::first()->id,
            'package_id' => $package->id,
            'location_id' => $this->loc('Cihideung')->id,
            'status' => BookingStatus::Penawaran,
            'ambang_pelunasan_persen' => 80,
            'batas_pelunasan' => $tanggal->copy()->subDays(7),
        ], $attrs, ['tanggal_acara' => $tanggal]));

        $pricing = app(PricingCalculatorService::class);
        $komponen ??= [['component_id' => $this->komponen('MLT-SGR')->id, 'kuantitas' => 1]];
        $pricing->applyQuote($booking, $pricing->quote($package, $komponen, $vendor));

        return $booking->refresh();
    }
}
