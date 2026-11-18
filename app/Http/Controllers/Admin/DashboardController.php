<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ApprovalStatus;
use App\Enums\BookingStatus;
use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Schedule;
use App\Models\VendorAvailabilityRequest;
use App\Services\Payment\PaymentStatusService;
use App\Services\Report\FinancialReportService;
use App\Services\Scheduling\ConflictDetectionService;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(PaymentStatusService $payments, FinancialReportService $reports, ConflictDetectionService $conflicts): View
    {
        $today = Carbon::today();

        $belumAmbang = Booking::with('client')->where('status', BookingStatus::BelumMemenuhiAmbang->value)->orderBy('tanggal_acara')->get();
        $upcoming = Booking::with(['client', 'location'])
            ->whereIn('status', array_map(fn ($s) => $s->value, BookingStatus::locking()))
            ->whereBetween('tanggal_acara', [$today->toDateString(), $today->copy()->addDays(30)->toDateString()])
            ->orderBy('tanggal_acara')->get();

        $tanggalAcara = $upcoming->pluck('tanggal_acara')->map->toDateString()->unique()->take(14);
        $bentrok = $tanggalAcara->mapWithKeys(fn ($t) => [$t => $conflicts->auditDay(Carbon::parse($t))])->filter();

        return view('admin.dashboard', [
            'belumAmbang' => $belumAmbang,
            'upcoming' => $upcoming,
            'pendingPayments' => Payment::with('booking.client')->where('status_verifikasi', VerificationStatus::Menunggu->value)->latest()->get(),
            'pendingAvailability' => VendorAvailabilityRequest::with('vendor')->where('status', ApprovalStatus::Menunggu->value)->count(),
            'penawaran' => Booking::where('status', BookingStatus::Penawaran->value)->count(),
            'jadwalHariIni' => Schedule::with(['user', 'booking.client', 'location'])->whereDate('tanggal', $today)->where('status', 'dijadwalkan')->orderBy('waktu_mulai')->get(),
            'bulanIni' => $reports->monthly($today),
            'bentrok' => $bentrok,
            'payments' => $payments,
        ]);
    }
}
