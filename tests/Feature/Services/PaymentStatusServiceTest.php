<?php

namespace Tests\Feature\Services;

use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use App\Enums\ScheduleRole;
use App\Enums\ScheduleStatus;
use App\Enums\VerificationStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Booking;
use App\Services\Payment\PaymentStatusService;
use App\Services\Scheduling\ConflictDetectionService;
use App\Support\Whatsapp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\BuildsBookings;
use Tests\TestCase;

class PaymentStatusServiceTest extends TestCase
{
    use BuildsBookings, RefreshDatabase;

    private PaymentStatusService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedMasterData();
        $this->service = app(PaymentStatusService::class);
    }

    private function bayar(Booking $booking, float $nominal, PaymentMethod $metode = PaymentMethod::Tunai): Booking
    {
        $this->service->recordPayment($booking, ['nominal' => $nominal, 'metode' => $metode], $this->owner());

        return $booking->refresh();
    }

    #[Test]
    public function dp_nol_ditolak(): void
    {
        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('lebih dari Rp0');
        $this->bayar($this->makeBooking(), 0);
    }

    #[Test]
    public function dp_berapapun_di_atas_nol_mengonfirmasi_dan_mengunci_tanggal(): void
    {
        $booking = $this->bayar($this->makeBooking(), 100_000);

        $this->assertSame(BookingStatus::Terkonfirmasi, $booking->status);
        $this->assertTrue($booking->is_date_locked);
        $this->assertTrue($booking->is_price_locked);
        $this->assertNotNull($booking->dikonfirmasi_at);
    }

    #[Test]
    public function transfer_wajib_bukti(): void
    {
        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('Bukti transfer wajib');
        $this->bayar($this->makeBooking(), 1_000_000, PaymentMethod::Transfer);
    }

    #[Test]
    public function unggahan_klien_menunggu_verifikasi_owner(): void
    {
        Storage::fake('local');
        $booking = $this->makeBooking();
        $klien = $this->user('siti.klien@example.com');

        $payment = $this->service->recordPayment($booking, ['nominal' => 3_000_000, 'metode' => 'transfer'], $klien, UploadedFile::fake()->image('bukti.jpg'));

        Storage::disk('local')->assertExists($payment->bukti_transfer_path);
        $this->assertSame(VerificationStatus::Menunggu, $payment->status_verifikasi);
        $this->assertSame(BookingStatus::Penawaran, $booking->refresh()->status);

        $this->expectException(BusinessRuleException::class);
        $this->service->verifyPayment($payment, $klien, true);
    }

    #[Test]
    public function verifikasi_owner_mengonfirmasi_booking(): void
    {
        Storage::fake('local');
        $booking = $this->makeBooking();
        $payment = $this->service->recordPayment($booking, ['nominal' => 3_000_000, 'metode' => 'transfer'], $this->user('siti.klien@example.com'), UploadedFile::fake()->image('bukti.jpg'));

        $this->service->verifyPayment($payment, $this->owner(), true);

        $this->assertSame(BookingStatus::Terkonfirmasi, $booking->refresh()->status);
        $this->assertSame(3_000_000.0, (float) $booking->total_dibayar);
    }

    #[Test]
    public function nominal_melebihi_sisa_tagihan_ditolak(): void
    {
        $this->expectException(BusinessRuleException::class);
        $this->bayar($this->makeBooking(), 20_000_000);
    }

    #[Test]
    public function h7_kurang_dari_80_persen_menjadi_belum_memenuhi_ambang_dengan_link_wa(): void
    {
        $booking = $this->bayar($this->makeBooking(), 5_000_000); // tagihan 16.350.000
        Carbon::setTestNow($booking->batas_pelunasan->copy()->setTime(0, 5));

        $belum = $this->service->evaluateThresholds();

        $this->assertCount(1, $belum);
        $booking->refresh();
        $this->assertSame(BookingStatus::BelumMemenuhiAmbang, $booking->status);
        $this->assertSame(8_080_000.0, $this->service->shortfallToThreshold($booking)); // 13.080.000 - 5.000.000

        $link = $this->service->reminderLink($booking);
        $this->assertStringStartsWith('https://wa.me/'.Whatsapp::normalize($booking->client->phone).'?text=', $link);
        $pesan = rawurldecode(substr($link, strpos($link, '=') + 1));
        $this->assertStringContainsString('Rp16.350.000', $pesan);
        $this->assertStringContainsString('Rp8.080.000', $pesan);
        $this->assertStringContainsString('80%', $pesan);
    }

    #[Test]
    public function sebelum_h7_status_tetap_terkonfirmasi(): void
    {
        $booking = $this->bayar($this->makeBooking(), 5_000_000);
        Carbon::setTestNow($booking->batas_pelunasan->copy()->subDay());

        $this->assertCount(0, $this->service->evaluateThresholds());
        $this->assertSame(BookingStatus::Terkonfirmasi, $booking->refresh()->status);
    }

    #[Test]
    public function memenuhi_80_persen_menjadi_siap_pelaksanaan_lalu_lunas(): void
    {
        $booking = $this->bayar($this->makeBooking(), 5_000_000);
        Carbon::setTestNow($booking->batas_pelunasan);
        $this->service->evaluateThresholds();

        $booking = $this->bayar($booking->refresh(), 8_080_000); // tepat 80%
        $this->assertSame(BookingStatus::SiapPelaksanaan, $booking->status);

        $booking = $this->bayar($booking, 3_270_000);
        $this->assertSame(BookingStatus::Lunas, $booking->status);
        $this->assertSame(0.0, $booking->sisaTagihan());
    }

    #[Test]
    public function booking_baru_dalam_h7_wajib_langsung_80_persen(): void
    {
        $booking = $this->makeBooking(['tanggal_acara' => Carbon::today()->addDays(5)]);

        $booking = $this->bayar($booking, 8_000_000);
        $this->assertSame(BookingStatus::Penawaran, $booking->status);
        $this->assertFalse($booking->is_date_locked);
        $this->assertFalse($booking->is_price_locked);

        $booking = $this->bayar($booking, 5_080_000); // total 13.080.000 = 80%
        $this->assertSame(BookingStatus::SiapPelaksanaan, $booking->status);
        $this->assertTrue($booking->is_date_locked);
    }

    #[Test]
    public function pembatalan_klien_refund_100_persen_dan_melepas_kunci(): void
    {
        $booking = $this->bayar($this->makeBooking(), 4_000_000);
        app(ConflictDetectionService::class)->assign([
            'booking_id' => $booking->id, 'user_id' => $this->user('rina.mua@yeniwedding.test')->id,
            'peran' => ScheduleRole::Mua, 'location_id' => $booking->location_id, 'tanggal' => $booking->tanggal_acara,
            'waktu_mulai' => $booking->tanggal_acara->copy()->setTime(4, 0), 'waktu_selesai' => $booking->tanggal_acara->copy()->setTime(6, 0),
        ], $this->owner());

        $refund = $this->service->cancel($booking, [
            'metode' => 'transfer', 'bank_tujuan' => 'BCA', 'nomor_rekening_tujuan' => '1234567890',
            'atas_nama' => 'Siti Nurhaliza', 'bukti_refund_path' => 'bukti-refund/contoh.jpg', 'alasan' => 'Keluarga berduka.',
        ], $this->owner());

        $booking->refresh();
        $this->assertSame(4_000_000.0, (float) $refund->nominal);
        $this->assertSame(BookingStatus::BatalRefund, $booking->status);
        $this->assertFalse($booking->is_date_locked);
        $this->assertFalse($booking->is_price_locked);
        $this->assertSame(4_000_000.0, (float) $booking->total_refund);
        $this->assertSame(ScheduleStatus::Dibatalkan, $booking->schedules()->first()->status);
        $this->assertTrue(Booking::dateLocked()->whereKey($booking->id)->doesntExist());
    }

    #[Test]
    public function refund_transfer_wajib_bukti_dan_hanya_owner(): void
    {
        $booking = $this->bayar($this->makeBooking(), 4_000_000);

        try {
            $this->service->cancel($booking, ['metode' => 'transfer'], $this->owner());
            $this->fail('Refund transfer tanpa bukti seharusnya ditolak.');
        } catch (BusinessRuleException $e) {
            $this->assertStringContainsString('Bukti refund', $e->getMessage());
        }

        $this->expectException(BusinessRuleException::class);
        $this->service->cancel($booking, ['metode' => 'tunai'], $this->user('rina.mua@yeniwedding.test'));
    }

    #[Test]
    public function pembayaran_ditolak_setelah_batal(): void
    {
        $booking = $this->bayar($this->makeBooking(), 1_000_000);
        $this->service->cancel($booking, ['metode' => 'tunai'], $this->owner());

        $this->expectException(BusinessRuleException::class);
        $this->bayar($booking->refresh(), 1_000_000);
    }

    #[Test]
    public function perintah_harian_cek_ambang_menampilkan_tautan_wa(): void
    {
        $booking = $this->bayar($this->makeBooking(), 5_000_000);

        $this->artisan('booking:cek-ambang', ['--tanggal' => $booking->batas_pelunasan->toDateString()])
            ->expectsOutputToContain('1 pesanan Belum Memenuhi Ambang')
            ->assertSuccessful();

        $this->assertSame(BookingStatus::BelumMemenuhiAmbang, $booking->refresh()->status);
    }
}
