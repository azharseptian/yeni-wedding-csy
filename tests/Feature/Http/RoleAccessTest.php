<?php

namespace Tests\Feature\Http;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\DynamicComponent;
use App\Models\Location;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function as(string $email): static
    {
        return $this->actingAs(User::where('email', $email)->firstOrFail());
    }

    public static function adminPages(): array
    {
        return array_map(fn ($r) => [$r], ['admin.dashboard', 'admin.bookings.index', 'admin.bookings.create', 'admin.schedules.index', 'admin.pricing.index',
            'admin.settings.index', 'admin.availability.index', 'admin.inventories.index', 'admin.reports.index', 'admin.audit.index']);
    }

    #[Test]
    #[DataProvider('adminPages')]
    public function owner_dapat_membuka_semua_halaman_admin(string $route): void
    {
        $this->as('owner@yeniwedding.test')->get(route($route))->assertOk();
    }

    #[Test]
    #[DataProvider('adminPages')]
    public function peran_lain_ditolak_dari_halaman_admin(string $route): void
    {
        foreach (['rina.mua@yeniwedding.test', 'dodi.catering@yeniwedding.test', 'siti.klien@example.com'] as $email) {
            $this->as($email)->get(route($route))->assertForbidden();
        }
    }

    #[Test]
    public function tamu_diarahkan_ke_halaman_masuk(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
        $this->get(route('katalog'))->assertOk()->assertSee('Paket Dasar');
    }

    #[Test]
    public function detail_pesanan_owner_dan_pdf_dapat_dibuka(): void
    {
        $this->as('owner@yeniwedding.test');
        foreach (Booking::all() as $booking) {
            $this->get(route('admin.bookings.show', $booking))->assertOk()->assertSee($booking->kode_booking);
        }

        $locked = Booking::where('is_price_locked', true)->first();
        $this->get(route('kesepakatan.pdf', $locked))->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->get(route('admin.reports.pdf'))->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->get(route('admin.reports.excel'))->assertOk()->assertDownload();
    }

    #[Test]
    public function setiap_peran_diarahkan_ke_beranda_masing_masing(): void
    {
        $map = [
            'owner@yeniwedding.test' => 'admin.dashboard',
            'rina.mua@yeniwedding.test' => 'tim.jadwal',
            'dodi.catering@yeniwedding.test' => 'vendor.beranda',
            'siti.klien@example.com' => 'portal.beranda',
        ];
        foreach ($map as $email => $route) {
            $this->as($email)->get(route('home'))->assertRedirect(route($route));
            $this->get(route($route))->assertOk();
        }
    }

    #[Test]
    public function tim_hanya_melihat_acara_yang_ditugaskan(): void
    {
        $rina = User::where('email', 'rina.mua@yeniwedding.test')->first();
        $ditugaskan = $rina->schedules()->where('status', 'dijadwalkan')->value('booking_id');
        $lain = Booking::whereDoesntHave('schedules', fn ($q) => $q->where('user_id', $rina->id))->value('id');

        $this->actingAs($rina)->get(route('tim.acara', $ditugaskan))->assertOk()->assertDontSee('Rp');
        $this->get(route('tim.acara', $lain))->assertForbidden();
    }

    #[Test]
    public function vendor_hanya_melihat_tarif_miliknya_tanpa_margin(): void
    {
        $this->as('dodi.catering@yeniwedding.test')->get(route('vendor.beranda'))
            ->assertOk()
            ->assertSee('Catering Prasmanan Menu Sunda')->assertSee('Rp22.000')
            ->assertDontSee('Rp30.000')
            ->assertDontSee('MC Akad')
            ->assertDontSee('Margin');
    }

    #[Test]
    public function klien_tidak_melihat_tarif_vendor_dan_tidak_bisa_membuka_pesanan_orang_lain(): void
    {
        $siti = User::where('email', 'siti.klien@example.com')->first();
        $milik = Booking::where('client_id', $siti->client->id)->first();
        $orangLain = Booking::where('client_id', '!=', $siti->client->id)->first();

        $this->actingAs($siti)->get(route('portal.pesan'))->assertOk()->assertSee('Rp30.000')->assertDontSee('Rp22.000')->assertDontSee('margin');
        $this->get(route('katalog'))->assertDontSee('Rp22.000');
        $this->get(route('portal.pesanan', $milik))->assertOk()->assertDontSee('Biaya Vendor');
        $this->get(route('portal.pesanan', $orangLain))->assertForbidden();
        $this->get(route('kesepakatan.pdf', $orangLain))->assertForbidden();
    }

    #[Test]
    public function klien_mendaftar_memesan_dan_mengunggah_bukti(): void
    {
        Storage::fake('local');
        $this->post(route('register'), [
            'nama_pengantin_wanita' => 'Nisa', 'nama_pengantin_pria' => 'Fajar', 'nama_kontak' => 'Nisa',
            'phone' => '081299990000', 'email' => 'nisa@example.com', 'password' => 'rahasia123', 'password_confirmation' => 'rahasia123',
        ])->assertRedirect(route('portal.beranda'));

        $paket = Package::active()->where('kode', 'PKT-AKD')->first();
        $melati = DynamicComponent::where('kode', 'MLT-SGR')->first();
        $this->post(route('portal.pesan.store'), [
            'package_id' => $paket->id, 'tanggal_acara' => Carbon::today()->addDays(60)->toDateString(), 'jenis_acara' => 'akad',
            'location_id' => Location::where('is_basecamp', false)->value('id'),
            'komponen' => [$melati->id => ['component_id' => $melati->id, 'kuantitas' => 1]],
        ])->assertRedirect();

        $booking = Booking::latest('id')->first();
        $this->assertSame(BookingStatus::Penawaran, $booking->status);
        $this->assertSame(5_850_000.0, (float) $booking->total_tagihan);

        $this->post(route('portal.bayar', $booking), [
            'nominal' => 500000, 'tanggal_bayar' => today()->toDateString(), 'bank_pengirim' => 'BCA', 'nama_pengirim' => 'Nisa',
        ])->assertSessionHasErrors('bukti_transfer');

        $this->post(route('portal.bayar', $booking), [
            'nominal' => 500000, 'tanggal_bayar' => today()->toDateString(), 'bank_pengirim' => 'BCA', 'nama_pengirim' => 'Nisa',
            'bukti_transfer' => UploadedFile::fake()->image('bukti.jpg'),
        ])->assertSessionHas('success');

        $this->as('owner@yeniwedding.test')->post(route('admin.payments.verify', $booking->payments()->first()), ['keputusan' => 'terima']);
        $this->assertSame(BookingStatus::Terkonfirmasi, $booking->refresh()->status);
        $this->assertTrue($booking->is_date_locked);
    }

    #[Test]
    public function penugasan_bentrok_ditolak_lalu_disimpan_dengan_override(): void
    {
        $this->as('owner@yeniwedding.test');
        $rina = User::where('email', 'rina.mua@yeniwedding.test')->first();
        $existing = $rina->schedules()->where('status', 'dijadwalkan')->orderBy('waktu_mulai')->first();
        $lain = Booking::where('status', 'terkonfirmasi')->where('id', '!=', $existing->booking_id)->first();
        $payload = [
            'booking_id' => $lain->id, 'peran' => 'mua', 'user_id' => $rina->id, 'tanggal' => $existing->tanggal->toDateString(),
            'jam_mulai' => $existing->waktu_mulai->format('H:i'), 'jam_selesai' => $existing->waktu_selesai->format('H:i'),
        ];

        $this->from(route('admin.bookings.show', $lain))->post(route('admin.schedules.store'), $payload)
            ->assertRedirect()->assertSessionHas('error')->assertSessionHas('conflict');
        $this->get(route('admin.bookings.show', $lain))->assertSee('BENTROK');

        $this->post(route('admin.schedules.store'), $payload + ['alasan_override' => 'Klien setuju bergantian.'])->assertSessionHas('success');
        $this->assertDatabaseHas('schedules', ['booking_id' => $lain->id, 'is_override' => true, 'alasan_override' => 'Klien setuju bergantian.']);
    }
}
