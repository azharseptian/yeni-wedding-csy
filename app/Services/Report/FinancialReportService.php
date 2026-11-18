<?php

namespace App\Services\Report;

use App\Enums\BookingStatus;
use App\Enums\PaymentType;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Refund;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/** AB-09: rekap keuangan bulanan eksklusif Owner. */
class FinancialReportService
{
    /** @return array<string, mixed> */
    public function monthly(CarbonInterface $bulan): array
    {
        $awal = Carbon::instance($bulan)->startOfMonth();
        $akhir = $awal->copy()->endOfMonth();

        $payments = Payment::with('booking.client')->verified()
            ->whereBetween('tanggal_bayar', [$awal->toDateString(), $akhir->toDateString()])
            ->orderBy('tanggal_bayar')->get();
        $refunds = Refund::with('booking.client')
            ->whereBetween('tanggal_refund', [$awal->toDateString(), $akhir->toDateString()])
            ->orderBy('tanggal_refund')->get();
        $events = Booking::with('client')
            ->whereBetween('tanggal_acara', [$awal->toDateString(), $akhir->toDateString()])
            ->whereNotIn('status', [BookingStatus::Penawaran->value, BookingStatus::BatalRefund->value])
            ->orderBy('tanggal_acara')->get()
            ->each->makeVisible(['total_biaya_vendor', 'total_margin']);

        $dp = (float) $payments->where('jenis', PaymentType::Dp)->sum('nominal');
        $pelunasan = (float) $payments->where('jenis', '!=', PaymentType::Dp)->sum('nominal');
        $refund = (float) $refunds->sum('nominal');
        $biayaVendor = (float) $events->sum('total_biaya_vendor');

        return [
            'bulan' => $awal,
            'dp_masuk' => $dp,
            'pelunasan' => $pelunasan,
            'refund' => $refund,
            'kas_bersih' => $dp + $pelunasan - $refund,
            'biaya_vendor' => $biayaVendor,
            'margin_vendor' => (float) $events->sum('total_margin'),
            'pendapatan_acara' => (float) $events->sum('total_tagihan'),
            'laba_bersih' => (float) $events->sum('total_tagihan') - $biayaVendor,
            'jumlah_acara' => $events->count(),
            'payments' => $payments,
            'refunds' => $refunds,
            'events' => $events,
        ];
    }

    /** @return list<array<string, mixed>> ringkasan 12 bulan dalam satu tahun */
    public function yearly(int $tahun): array
    {
        return collect(range(1, 12))
            ->map(fn (int $m) => collect($this->monthly(Carbon::create($tahun, $m, 1)))->except(['payments', 'refunds', 'events'])->all())
            ->all();
    }
}
