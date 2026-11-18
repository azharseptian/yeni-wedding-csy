<?php

namespace App\Services\Booking;

use App\Enums\BookingStatus;
use App\Enums\JenisAcara;
use App\Exceptions\BusinessRuleException;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Client;
use App\Models\Package;
use App\Models\SystemParameter;
use App\Models\User;
use App\Services\Pricing\PricingCalculatorService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BookingRegistrationService
{
    public function __construct(private readonly PricingCalculatorService $pricing) {}

    /**
     * Buat pesanan berstatus Penawaran (tentatif) beserta rincian harga dari master terkini.
     *
     * @param  array<string, mixed>  $data  package_id, tanggal_acara, location_id, jenis_acara, jam_mulai_acara?, jam_selesai_acara?, alamat_acara?, jumlah_tamu?, catatan?, komponen[], vendor[]
     */
    public function register(Client $client, array $data, User $by): Booking
    {
        $tanggal = Carbon::parse($data['tanggal_acara'])->startOfDay();
        if ($tanggal->lte(Carbon::today())) {
            throw new BusinessRuleException('Tanggal acara harus setelah hari ini.', 'booking');
        }

        $package = Package::active()->findOrFail($data['package_id']);
        $komponen = $this->filterRows($data['komponen'] ?? [], 'component_id');
        $vendor = $this->filterRows($data['vendor'] ?? [], 'vendor_service_id');
        $quote = $this->pricing->quote($package, $komponen, $vendor);

        return DB::transaction(function () use ($client, $data, $by, $tanggal, $package, $quote) {
            $booking = Booking::create([
                'kode_booking' => $this->nextKode(),
                'client_id' => $client->id,
                'package_id' => $package->id,
                'location_id' => $data['location_id'],
                'jenis_acara' => $data['jenis_acara'] ?? JenisAcara::AkadResepsi,
                'tanggal_acara' => $tanggal,
                'jam_mulai_acara' => $data['jam_mulai_acara'] ?? null,
                'jam_selesai_acara' => $data['jam_selesai_acara'] ?? null,
                'alamat_acara' => $data['alamat_acara'] ?? null,
                'jumlah_tamu' => $data['jumlah_tamu'] ?? null,
                'status' => BookingStatus::Penawaran,
                'ambang_pelunasan_persen' => (int) SystemParameter::get('ambang_pelunasan_persen', 80),
                'batas_pelunasan' => $tanggal->copy()->subDays((int) SystemParameter::get('batas_hari_pelunasan', 7)),
                'catatan' => $data['catatan'] ?? null,
                'created_by' => $by->id,
            ]);

            $this->pricing->applyQuote($booking, $quote);
            AuditLog::record('booking.dibuat', $booking, null, ['total_tagihan' => (float) $booking->total_tagihan], 'Penawaran dibuat.', $by->id);

            return $booking->refresh();
        });
    }

    /** Buang baris dengan kuantitas kosong/0 dari input formulir. */
    public function filterRows(array $rows, string $key): array
    {
        return array_values(array_filter(
            array_map(fn ($row, $id) => ['kuantitas' => (float) ($row['kuantitas'] ?? 0), $key => (int) ($row[$key] ?? $id)] + array_filter(['ukuran_cm' => $row['ukuran_cm'] ?? null]), $rows, array_keys($rows)),
            fn ($row) => $row['kuantitas'] > 0,
        ));
    }

    public function nextKode(): string
    {
        $prefix = 'YW-'.now()->format('Y').'-';
        $last = Booking::where('kode_booking', 'like', $prefix.'%')->orderByDesc('kode_booking')->value('kode_booking');

        return $prefix.str_pad((string) ((int) substr((string) $last, -4) + 1), 4, '0', STR_PAD_LEFT);
    }
}
