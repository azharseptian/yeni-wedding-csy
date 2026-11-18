<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\SystemParameter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** AB-07: Lembar Kesepakatan PDF (Owner atau klien pemilik pesanan, setelah DP masuk). */
class AgreementController extends Controller
{
    public function __invoke(Request $request, Booking $booking): Response
    {
        $user = $request->user();
        abort_unless($user->isOwner() || $booking->client->user_id === $user->id, 403);
        abort_if(! $booking->is_price_locked || $booking->status === BookingStatus::BatalRefund, 404, 'Lembar Kesepakatan tersedia setelah DP terverifikasi.');

        $booking->load(['client', 'location', 'items', 'verifiedPayments']);

        return Pdf::loadView('pdf.kesepakatan', [
            'booking' => $booking,
            'usaha' => [
                'nama' => SystemParameter::get('nama_usaha', 'Yeni Wedding'),
                'alamat' => SystemParameter::get('alamat_usaha'),
                'wa' => SystemParameter::get('whatsapp_admin'),
                'rekening' => SystemParameter::get('rekening_pembayaran', []),
            ],
        ])->setPaper('a4')->download("Lembar-Kesepakatan-{$booking->kode_booking}.pdf");
    }
}
