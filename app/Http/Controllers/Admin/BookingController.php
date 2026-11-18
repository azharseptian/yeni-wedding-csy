<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Enums\JenisAcara;
use App\Enums\PaymentMethod;
use App\Enums\ScheduleRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\BookingRequest;
use App\Models\Booking;
use App\Models\Client;
use App\Models\DynamicComponent;
use App\Models\Inventory;
use App\Models\Location;
use App\Models\Package;
use App\Models\User;
use App\Models\VendorService;
use App\Services\Booking\BookingRegistrationService;
use App\Services\Payment\PaymentStatusService;
use App\Services\Pricing\PricingCalculatorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function index(Request $request): View
    {
        $bookings = Booking::with(['client', 'location'])
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->q, fn ($q, $term) => $q->where(fn ($q) => $q->where('kode_booking', 'like', "%{$term}%")
                ->orWhereHas('client', fn ($c) => $c->where('nama_pengantin_wanita', 'like', "%{$term}%")->orWhere('nama_pengantin_pria', 'like', "%{$term}%")->orWhere('nama_kontak', 'like', "%{$term}%"))))
            ->orderByRaw('tanggal_acara < ? , tanggal_acara', [now()->toDateString()])
            ->paginate(20)->withQueryString();

        return view('admin.bookings.index', ['bookings' => $bookings, 'statuses' => BookingStatus::cases()]);
    }

    public function create(): View
    {
        return view('admin.bookings.create', $this->formData() + ['clients' => Client::orderBy('nama_kontak')->get()]);
    }

    public function store(BookingRequest $request, BookingRegistrationService $service): RedirectResponse
    {
        $client = $request->filled('client_id')
            ? Client::findOrFail($request->integer('client_id'))
            : Client::create($request->validate([
                'nama_pengantin_wanita' => ['required', 'string', 'max:100'],
                'nama_pengantin_pria' => ['required', 'string', 'max:100'],
                'nama_kontak' => ['required', 'string', 'max:100'],
                'phone' => ['required', 'string', 'max:20'],
                'email' => ['nullable', 'email'],
            ]));

        $booking = $service->register($client, $request->validated(), $request->user());

        return redirect()->route('admin.bookings.show', $booking)->with('success', "Penawaran {$booking->kode_booking} dibuat. Catat DP untuk mengunci tanggal & harga.");
    }

    public function show(Booking $booking, PaymentStatusService $payments): View
    {
        $booking->load(['client', 'location', 'package', 'items', 'payments.penerima', 'refunds.pemroses', 'schedules.user', 'schedules.location', 'inventories.inventory', 'auditLogs.user']);
        $booking->makeVisible(['total_biaya_vendor', 'total_margin']);
        $booking->items->each->makeVisible(['tarif_vendor_satuan', 'subtotal_vendor', 'margin']);

        return view('admin.bookings.show', $this->formData() + [
            'booking' => $booking,
            'reminderLink' => $booking->status === BookingStatus::BelumMemenuhiAmbang ? $payments->reminderLink($booking) : null,
            'kekurangan' => $payments->shortfallToThreshold($booking),
            'staff' => User::with('role')->whereHas('role', fn ($q) => $q->whereIn('name', ['tim_internal', 'vendor_keluarga']))->where('is_active', true)->orderBy('name')->get(),
            'scheduleRoles' => ScheduleRole::cases(),
            'inventories' => Inventory::orderBy('kategori')->orderBy('nama')->get(),
            'methods' => PaymentMethod::cases(),
        ]);
    }

    public function revise(Request $request, Booking $booking, PricingCalculatorService $pricing, BookingRegistrationService $service): RedirectResponse
    {
        abort_if($booking->status === BookingStatus::BatalRefund, 422);
        $request->validate(['alasan' => ['required', 'string', 'max:500']] + BookingRequest::itemRules());

        $pricing->reviseScope(
            $booking,
            $service->filterRows($request->input('komponen', []), 'component_id'),
            $service->filterRows($request->input('vendor', []), 'vendor_service_id'),
            $request->user(),
            $request->string('alasan'),
        );

        return back()->with('success', 'Rincian pesanan direvisi dan tercatat di log.');
    }

    public function cancel(Request $request, Booking $booking, PaymentStatusService $payments): RedirectResponse
    {
        $data = $request->validate([
            'alasan' => ['required', 'string', 'max:500'],
            'metode' => ['nullable', Rule::enum(PaymentMethod::class)],
            'tanggal_refund' => ['nullable', 'date'],
            'bank_tujuan' => ['nullable', 'string', 'max:50'],
            'nomor_rekening_tujuan' => ['nullable', 'string', 'max:50'],
            'atas_nama' => ['nullable', 'string', 'max:100'],
            'bukti_refund' => ['nullable', 'image', 'max:4096'],
        ]);

        $refund = $payments->cancel($booking, $data, $request->user(), $request->file('bukti_refund'));

        return back()->with('success', 'Pesanan dibatalkan. '.($refund ? 'Refund '.rupiah($refund->nominal).' tercatat.' : 'Tidak ada dana yang perlu dikembalikan.').' Tanggal & harga sudah dilepas.');
    }

    public function reminder(Booking $booking, PaymentStatusService $payments): RedirectResponse
    {
        return redirect()->away($payments->reminderLink($booking));
    }

    public function attachInventory(Request $request, Booking $booking): RedirectResponse
    {
        $data = $request->validate([
            'inventory_id' => ['required', 'exists:inventories,id'],
            'jumlah' => ['required', 'integer', 'min:1'],
        ]);

        $booking->inventories()->updateOrCreate(['inventory_id' => $data['inventory_id']], ['jumlah' => $data['jumlah']]);

        return back()->with('success', 'Barang ditambahkan ke checklist acara.');
    }

    /** @return array<string, mixed> */
    private function formData(): array
    {
        return [
            'packages' => Package::active()->orderBy('harga_dasar')->get(),
            'components' => DynamicComponent::where('is_active', true)->orderBy('kategori')->get(),
            'services' => VendorService::with('vendor')->where('is_active', true)->orderBy('kategori')->get()->each->makeVisible(['tarif_vendor', 'margin']),
            'locations' => Location::orderBy('kota')->orderBy('nama')->get(),
            'jenisAcara' => JenisAcara::cases(),
        ];
    }
}
