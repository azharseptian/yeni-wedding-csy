<?php

namespace Tests\Feature\Services;

use App\Enums\ApprovalStatus;
use App\Enums\AvailabilityType;
use App\Enums\ScheduleRole;
use App\Exceptions\BusinessRuleException;
use App\Exceptions\ScheduleConflictException;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\VendorAvailabilityRequest;
use App\Services\Scheduling\Conflict;
use App\Services\Scheduling\ConflictDetectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\BuildsBookings;
use Tests\TestCase;

class ConflictDetectionServiceTest extends TestCase
{
    use BuildsBookings, RefreshDatabase;

    private ConflictDetectionService $service;

    private Carbon $hari;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedMasterData();
        $this->service = app(ConflictDetectionService::class);
        $this->hari = Carbon::today()->addDays(20);
    }

    private function mua(Booking $booking, string $lokasi, string $mulai, string $selesai, string $email = 'rina.mua@yeniwedding.test', ?Carbon $tgl = null): array
    {
        $tgl ??= $this->hari;

        return [
            'booking_id' => $booking->id,
            'user_id' => $this->user($email)->id,
            'peran' => ScheduleRole::Mua,
            'location_id' => $this->loc($lokasi)->id,
            'tanggal' => $tgl->toDateString(),
            'waktu_mulai' => $tgl->copy()->setTimeFromTimeString($mulai),
            'waktu_selesai' => $tgl->copy()->setTimeFromTimeString($selesai),
        ];
    }

    private function kru(Booking $booking, ?string $email, Carbon $tgl, bool $lepas = false): array
    {
        return [
            'booking_id' => $booking->id,
            'user_id' => $email ? $this->user($email)->id : null,
            'peran' => ScheduleRole::KruDekorasi,
            'location_id' => $booking->location_id,
            'is_tenaga_lepas' => $lepas,
            'nama_tenaga_lepas' => $lepas ? 'Tenaga Lepas' : null,
            'tanggal' => $tgl->toDateString(),
            'waktu_mulai' => $tgl->copy()->setTime(8, 0),
            'waktu_selesai' => $tgl->copy()->setTime(16, 0),
        ];
    }

    #[Test]
    public function estafet_dini_hari_tiga_lokasi_tanpa_bentrok(): void
    {
        $owner = $this->owner();
        $b1 = $this->makeBooking(['tanggal_acara' => $this->hari]);
        $b2 = $this->makeBooking(['tanggal_acara' => $this->hari, 'location_id' => $this->loc('Singaparna')->id]);
        $b3 = $this->makeBooking(['tanggal_acara' => $this->hari, 'location_id' => $this->loc('Cijeungjing')->id]);

        $this->service->assign($this->mua($b1, 'Cihideung', '02:00', '04:30'), $owner);
        $this->service->assign($this->mua($b2, 'Singaparna', '05:30', '07:30'), $owner); // 04:30 + 30 + 30
        $this->service->assign($this->mua($b3, 'Cijeungjing', '08:50', '11:00'), $owner); // 07:30 + 50 + 30, tepat batas

        $this->assertSame([], $this->service->auditDay($this->hari));
        $this->assertDatabaseCount('schedules', 3);
        $this->assertDatabaseMissing('schedules', ['is_override' => true]);
    }

    #[Test]
    public function jeda_tempuh_kurang_terdeteksi_bentrok_dengan_saran(): void
    {
        $b1 = $this->makeBooking();
        $b2 = $this->makeBooking(['location_id' => $this->loc('Singaparna')->id]);
        $this->service->assign($this->mua($b1, 'Cihideung', '02:00', '04:30'), $this->owner());

        $result = $this->service->check($this->mua($b2, 'Singaparna', '05:00', '07:00'));

        $this->assertTrue($result->hasConflict());
        $this->assertCount(1, $result->ofType(Conflict::WAKTU));
        $this->assertStringContainsString('05:30', implode(' ', $result->suggestions()));
    }

    #[Test]
    public function bentrok_lintas_tengah_malam_terdeteksi(): void
    {
        $b1 = $this->makeBooking();
        $b2 = $this->makeBooking(['location_id' => $this->loc('Buahbatu')->id]);
        $kemarin = $this->hari->copy()->subDay();
        $this->service->assign($this->mua($b1, 'Cihideung', '22:00', '23:30', tgl: $kemarin), $this->owner());

        // Tasik -> Bandung 180 + 45 menit; 23:30 + 225 menit = 03:15 hari berikutnya.
        $this->assertTrue($this->service->check($this->mua($b2, 'Buahbatu', '02:00', '05:00'))->hasConflict());
        $this->assertFalse($this->service->check($this->mua($b2, 'Buahbatu', '03:15', '05:00'))->hasConflict());
    }

    #[Test]
    public function saran_mua_alternatif_hanya_yang_tersedia(): void
    {
        $b1 = $this->makeBooking();
        $b2 = $this->makeBooking();
        $this->service->assign($this->mua($b1, 'Cihideung', '04:00', '06:00'), $this->owner());

        // Dewi berjabatan asisten rias, jadi bukan alternatif MUA.
        $result = $this->service->check($this->mua($b2, 'Cihideung', '05:00', '07:00'));
        $this->assertStringNotContainsString('Dewi', implode(' ', $result->suggestions()));
    }

    #[Test]
    public function kapasitas_lima_kru_internal_dan_tenaga_lepas_tidak_dihitung(): void
    {
        $booking = $this->makeBooking();
        $tgl = $booking->tanggal_acara->copy()->subDay();
        foreach (['asep.dekor', 'ujang.kru', 'dadan.kru', 'cecep.kru', 'endang.kru'] as $i => $kru) {
            $data = $this->kru($booking, "{$kru}@yeniwedding.test", $tgl);
            if ($i === 0) {
                $data['peran'] = ScheduleRole::Dekorator;
            }
            $this->service->assign($data, $this->owner());
        }

        $this->assertFalse($this->service->check($this->kru($booking, null, $tgl, lepas: true))->hasConflict());

        $result = $this->service->check($this->kru($booking, 'yayan.kru@yeniwedding.test', $tgl));
        $conflict = $result->ofType(Conflict::KAPASITAS)[0];
        $this->assertStringContainsString('6 dari maksimal 5', $conflict->message);
        $this->assertStringContainsString('H-2', implode(' ', $conflict->suggestions));
        $this->assertStringContainsString('Tambah tenaga lepas sebanyak 1 orang', implode(' ', $conflict->suggestions));
    }

    #[Test]
    public function bentrok_tidak_tersimpan_tanpa_alasan_override(): void
    {
        $b1 = $this->makeBooking();
        $b2 = $this->makeBooking();
        $this->service->assign($this->mua($b1, 'Cihideung', '04:00', '06:00'), $this->owner());

        $this->expectException(ScheduleConflictException::class);
        $this->service->assign($this->mua($b2, 'Cihideung', '05:00', '07:00'), $this->owner(), '   ');
    }

    #[Test]
    public function override_tersimpan_dengan_alasan_dan_log(): void
    {
        $b1 = $this->makeBooking();
        $b2 = $this->makeBooking();
        $this->service->assign($this->mua($b1, 'Cihideung', '04:00', '06:00'), $this->owner());

        $schedule = $this->service->assign($this->mua($b2, 'Cihideung', '05:00', '07:00'), $this->owner(), 'Klien setuju dirias bergantian.');

        $this->assertTrue($schedule->is_override);
        $this->assertSame('Klien setuju dirias bergantian.', $schedule->alasan_override);
        $this->assertSame(1, AuditLog::where('event', 'schedule.override')->where('auditable_id', $schedule->id)->count());
    }

    #[Test]
    public function non_owner_tidak_boleh_override(): void
    {
        $b1 = $this->makeBooking();
        $b2 = $this->makeBooking();
        $this->service->assign($this->mua($b1, 'Cihideung', '04:00', '06:00'), $this->owner());

        $this->expectException(BusinessRuleException::class);
        $this->service->assign($this->mua($b2, 'Cihideung', '05:00', '07:00'), $this->user('rina.mua@yeniwedding.test'), 'Alasan');
    }

    #[Test]
    public function vendor_tidak_tersedia_disetujui_menjadi_bentrok(): void
    {
        $booking = $this->makeBooking(['tanggal_acara' => $this->hari]);
        $euis = $this->user('euis.mc@yeniwedding.test');
        VendorAvailabilityRequest::create([
            'vendor_user_id' => $euis->id, 'tanggal' => $this->hari->toDateString(),
            'ketersediaan' => AvailabilityType::TidakTersedia, 'status' => ApprovalStatus::Disetujui,
        ]);

        $data = ['peran' => ScheduleRole::Mc] + $this->mua($booking, 'Cihideung', '08:00', '12:00', 'euis.mc@yeniwedding.test');

        $this->assertCount(1, $this->service->check($data)->ofType(Conflict::KETERSEDIAAN));
    }

    #[Test]
    public function waktu_selesai_harus_setelah_mulai(): void
    {
        $this->expectException(BusinessRuleException::class);
        $this->service->check($this->mua($this->makeBooking(), 'Cihideung', '06:00', '05:00'));
    }
}
