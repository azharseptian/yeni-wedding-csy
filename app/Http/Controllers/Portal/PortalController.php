<?php

namespace App\Http\Controllers\Portal;

use App\Enums\JenisAcara;
use App\Http\Controllers\Controller;
use App\Http\Requests\BookingRequest;
use App\Models\Booking;
use App\Models\DynamicComponent;
use App\Models\Location;
use App\Models\Package;
use App\Models\SystemParameter;
use App\Models\VendorService;
use App\Services\Booking\BookingRegistrationService;
use App\Services\Payment\PaymentStatusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Portal mandiri Calon Pengantin. Tarif vendor & margin tidak pernah dikirim ke tampilan ini. */
class PortalController extends Controller
{
    public function index(Request $request): View
    {
        return view('portal.index', [
            'bookings' => Booking::with('location')->where('client_id', $request->user()->client?->id)->latest('tanggal_acara')->get(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('portal.create', [
            'packages' => Package::active()->orderBy('harga_dasar')->get(),
            'selected' => $request->integer('paket'),
            'components' => DynamicComponent::where('is_active', true)->orderBy('kategori')->get(),
            'services' => VendorService::where('is_active', true)->orderBy('kategori')->get(['id', 'nama_layanan', 'kategori', 'satuan', 'tarif_klien']),
            'locations' => Location::where('is_basecamp', false)->orderBy('kota')->get(),
            'jenisAcara' => JenisAcara::cases(),
            'batasHari' => (int) SystemParameter::get('batas_hari_pelunasan', 7),
            'ambang' => (int) SystemParameter::get('ambang_pelunasan_persen', 80),
        ]);
    }

    public function store(BookingRequest $request, BookingRegistrationService $service): RedirectResponse
    {
        $client = $request->user()->client;
        abort_unless($client, 403, 'Data klien tidak ditemukan.');

        $booking = $service->register($client, $request->validated(), $request->user());

        return redirect()->route('portal.pesanan', $booking)->with('success', "Pesanan {$booking->kode_booking} terkirim. Bayar DP berapa pun (> Rp0) untuk mengunci tanggal & harga.");
    }

    public function show(Request $request, Booking $booking, PaymentStatusService $payments): View
    {
        $this->authorizeOwn($request, $booking);
        $booking->load(['location', 'items', 'payments', 'refunds', 'schedules' => fn ($q) => $q->where('status', 'dijadwalkan')->with('user')->orderBy('waktu_mulai')]);

        return view('portal.show', [
            'booking' => $booking,
            'kekurangan' => $payments->shortfallToThreshold($booking),
            'rekening' => SystemParameter::get('rekening_pembayaran', []),
            'waAdmin' => SystemParameter::get('whatsapp_admin'),
        ]);
    }

    public function pay(Request $request, Booking $booking, PaymentStatusService $payments): RedirectResponse
    {
        $this->authorizeOwn($request, $booking);
        $data = $request->validate([
            'nominal' => ['required', 'numeric', 'gt:0'],
            'tanggal_bayar' => ['required', 'date', 'before_or_equal:today'],
            'bank_pengirim' => ['required', 'string', 'max:50'],
            'nama_pengirim' => ['required', 'string', 'max:100'],
            'bukti_transfer' => ['required', 'image', 'max:4096'],
        ], ['bukti_transfer.required' => 'Bukti transfer wajib diunggah.']);

        $payments->recordPayment($booking, $data + ['metode' => 'transfer'], $request->user(), $request->file('bukti_transfer'));

        return back()->with('success', 'Bukti transfer terkirim dan menunggu verifikasi Admin.');
    }

    private function authorizeOwn(Request $request, Booking $booking): void
    {
        abort_unless($booking->client_id === $request->user()->client?->id, 403);
    }
}
