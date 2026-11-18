<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentMethod;
use App\Enums\PaymentType;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Services\Payment\PaymentStatusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentController extends Controller
{
    public function store(Request $request, Booking $booking, PaymentStatusService $service): RedirectResponse
    {
        $data = $request->validate([
            'nominal' => ['required', 'numeric', 'gt:0'],
            'metode' => ['required', Rule::enum(PaymentMethod::class)],
            'jenis' => ['nullable', Rule::enum(PaymentType::class)],
            'tanggal_bayar' => ['required', 'date', 'before_or_equal:today'],
            'bank_pengirim' => ['nullable', 'string', 'max:50'],
            'nama_pengirim' => ['nullable', 'string', 'max:100'],
            'bukti_transfer' => ['required_if:metode,transfer', 'nullable', 'image', 'max:4096'],
            'catatan' => ['nullable', 'string', 'max:500'],
        ], ['bukti_transfer.required_if' => 'Bukti transfer wajib diunggah untuk pembayaran transfer.']);

        $service->recordPayment($booking, array_filter($data), $request->user(), $request->file('bukti_transfer'));

        return back()->with('success', 'Pembayaran '.rupiah($data['nominal']).' tercatat. Status pesanan: '.$booking->refresh()->status->label().'.');
    }

    public function verify(Request $request, Payment $payment, PaymentStatusService $service): RedirectResponse
    {
        $data = $request->validate(['keputusan' => ['required', 'in:terima,tolak'], 'catatan' => ['nullable', 'string', 'max:500']]);
        $service->verifyPayment($payment, $request->user(), $data['keputusan'] === 'terima', $data['catatan'] ?? null);

        return back()->with('success', $data['keputusan'] === 'terima' ? 'Pembayaran diverifikasi.' : 'Pembayaran ditolak.');
    }

    public function proof(Payment $payment): StreamedResponse
    {
        abort_unless($payment->bukti_transfer_path && Storage::disk('local')->exists($payment->bukti_transfer_path), 404, 'File bukti tidak ditemukan.');

        return Storage::disk('local')->response($payment->bukti_transfer_path);
    }
}
