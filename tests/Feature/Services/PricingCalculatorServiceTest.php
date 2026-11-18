<?php

namespace Tests\Feature\Services;

use App\Enums\PaymentMethod;
use App\Exceptions\BusinessRuleException;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Package;
use App\Services\Payment\PaymentStatusService;
use App\Services\Pricing\PricingCalculatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\BuildsBookings;
use Tests\TestCase;

class PricingCalculatorServiceTest extends TestCase
{
    use BuildsBookings, RefreshDatabase;

    private PricingCalculatorService $pricing;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedMasterData();
        $this->pricing = app(PricingCalculatorService::class);
    }

    private function bayarDp(Booking $booking, float $nominal = 2_000_000): void
    {
        app(PaymentStatusService::class)->recordPayment($booking, ['nominal' => $nominal, 'metode' => PaymentMethod::Tunai], $this->owner());
        $booking->refresh();
    }

    #[Test]
    public function tenda_dihitung_harga_per_cm_kali_ukuran(): void
    {
        $tenda = $this->komponen('TND-DKR'); // Rp2.500/cm

        $this->assertSame(3_000_000.0, $this->pricing->componentSubtotal($tenda, 1, 1200));
        $this->assertSame(6_000_000.0, $this->pricing->componentSubtotal($tenda, 2, 1200));
    }

    #[Test]
    public function ukuran_tenda_wajib_dan_dalam_rentang(): void
    {
        $tenda = $this->komponen('TND-DKR');

        $this->expectException(BusinessRuleException::class);
        $this->pricing->componentSubtotal($tenda, 1, 5000);
    }

    #[Test]
    public function penawaran_menjumlah_paket_melati_tenda_dan_margin_vendor(): void
    {
        $quote = $this->pricing->quote(
            Package::active()->where('kode', 'PKT-MNG')->first(),
            [
                ['component_id' => $this->komponen('MLT-SGR')->id, 'kuantitas' => 1],
                ['component_id' => $this->komponen('TND-DKR')->id, 'kuantitas' => 1, 'ukuran_cm' => 1200],
                ['component_id' => $this->komponen('BNG-MJA')->id, 'kuantitas' => 4],
            ],
            [['vendor_service_id' => $this->service('Catering Prasmanan')->id, 'kuantitas' => 300]],
        );

        // 15.000.000 + 1.350.000 + 3.000.000 + 300.000 + (300 x 30.000)
        $this->assertSame(28_650_000.0, $quote->totalKlien());
        $this->assertSame(6_600_000.0, $quote->totalVendor()); // 300 x 22.000
        $this->assertSame(2_400_000.0, $quote->totalMargin());
    }

    #[Test]
    public function harga_terkunci_saat_dp_dan_tidak_terpengaruh_perubahan_master(): void
    {
        $booking = $this->makeBooking([], null, [['vendor_service_id' => $this->service('MC Akad')->id, 'kuantitas' => 1]]);
        $this->assertSame(17_350_000.0, (float) $booking->total_tagihan);

        $this->bayarDp($booking);
        $this->assertTrue($booking->is_price_locked);
        $this->assertTrue($booking->items()->where('is_locked', false)->doesntExist());

        $this->komponen('MLT-SGR')->update(['harga_satuan' => 1_750_000]);
        $this->service('MC Akad')->update(['tarif_klien' => 1_500_000, 'tarif_vendor' => 900_000]);
        Package::where('kode', 'PKT-MNG')->update(['is_active' => false]);
        Package::create(['kode' => 'PKT-MNG', 'versi' => 2, 'nama' => 'Paket Menengah', 'harga_dasar' => 17_000_000, 'berlaku_mulai' => now(), 'is_active' => true]);

        $booking->refresh();
        $this->assertSame(17_350_000.0, (float) $booking->total_tagihan);
        $this->assertSame(400_000.0, (float) $booking->total_margin);
        $this->assertSame('1350000.00', $booking->items()->where('nama_item', 'like', 'Bunga Melati%')->value('harga_satuan_klien'));

        $this->expectException(BusinessRuleException::class);
        $this->pricing->refreshFromMaster($booking);
    }

    #[Test]
    public function booking_tentatif_mengikuti_harga_master_terbaru_saat_dp(): void
    {
        $booking = $this->makeBooking();
        $this->komponen('MLT-SGR')->update(['harga_satuan' => 1_500_000]);

        $this->bayarDp($booking);

        $this->assertSame(16_500_000.0, (float) $booking->total_tagihan);
    }

    #[Test]
    public function revisi_lingkup_setelah_terkunci_memakai_harga_snapshot_untuk_item_lama(): void
    {
        $booking = $this->makeBooking();
        $this->bayarDp($booking);
        $this->komponen('MLT-SGR')->update(['harga_satuan' => 1_750_000]);

        $this->pricing->reviseScope($booking, [
            ['component_id' => $this->komponen('MLT-SGR')->id, 'kuantitas' => 2],
            ['component_id' => $this->komponen('TND-DKR')->id, 'kuantitas' => 1, 'ukuran_cm' => 800],
        ], [], $this->owner(), 'Klien menambah tenda lorong dan melati ganti busana.');

        // 15.000.000 + 2 x 1.350.000 (harga snapshot) + 800 x 2.500 (harga master baru)
        $this->assertSame(19_700_000.0, (float) $booking->total_tagihan);
        $this->assertSame(1, $booking->revisi_ke);
        $this->assertTrue($booking->items()->where('is_locked', false)->doesntExist());
        $this->assertSame(1, AuditLog::where('event', 'booking.revisi')->count());
    }

    #[Test]
    public function revisi_wajib_beralasan(): void
    {
        $this->expectException(BusinessRuleException::class);
        $this->pricing->reviseScope($this->makeBooking(), [], [], $this->owner(), ' ');
    }

    #[Test]
    public function tarif_vendor_dan_margin_tersembunyi_saat_serialisasi(): void
    {
        $booking = $this->makeBooking([], null, [['vendor_service_id' => $this->service('MC Akad')->id, 'kuantitas' => 1]]);

        $this->assertArrayNotHasKey('total_biaya_vendor', $booking->toArray());
        $this->assertArrayNotHasKey('total_margin', $booking->toArray());
        $item = $booking->items()->whereNotNull('tarif_vendor_satuan')->first()->toArray();
        $this->assertArrayNotHasKey('tarif_vendor_satuan', $item);
        $this->assertArrayNotHasKey('margin', $item);
        $this->assertArrayNotHasKey('tarif_vendor', $this->service('MC Akad')->toArray());
    }
}
