<?php

namespace App\Http\Controllers\Tim;

use App\Enums\ScheduleStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingInventory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/** Tim MUA/Dekorasi: hanya jadwal miliknya + centang checklist acara yang ditugaskan. */
class TimController extends Controller
{
    public function index(Request $request): View
    {
        $schedules = $request->user()->schedules()->with(['booking.client', 'location'])
            ->where('status', ScheduleStatus::Dijadwalkan->value)
            ->whereDate('tanggal', '>=', Carbon::today()->subDay())
            ->orderBy('waktu_mulai')->get();

        return view('tim.index', ['perHari' => $schedules->groupBy(fn ($s) => $s->tanggal->toDateString())]);
    }

    public function show(Request $request, Booking $booking): View
    {
        $tugas = $request->user()->schedules()->with('location')->where('booking_id', $booking->id)
            ->where('status', ScheduleStatus::Dijadwalkan->value)->orderBy('waktu_mulai')->get();
        abort_if($tugas->isEmpty(), 403, 'Acara ini tidak ditugaskan kepada Anda.');

        return view('tim.show', [
            'booking' => $booking->load(['client', 'location', 'inventories.inventory']),
            'tugas' => $tugas,
        ]);
    }

    public function check(Request $request, BookingInventory $item): RedirectResponse
    {
        abort_unless($request->user()->schedules()->where('booking_id', $item->booking_id)->where('status', ScheduleStatus::Dijadwalkan->value)->exists(), 403);
        $data = $request->validate([
            'tahap' => ['required', 'in:keluar,kembali'],
            'nilai' => ['required', 'boolean'],
            'jumlah_kembali' => ['nullable', 'integer', 'min:0', 'max:'.$item->jumlah],
            'catatan' => ['nullable', 'string', 'max:300'],
        ]);
        $nilai = (bool) $data['nilai'];

        if ($data['tahap'] === 'keluar') {
            abort_if(! $nilai && $item->is_kembali, 422, 'Batalkan centang "kembali" terlebih dahulu.');
            $item->update(['is_keluar' => $nilai, 'keluar_at' => $nilai ? now() : null, 'dikeluarkan_oleh' => $nilai ? $request->user()->id : null]);
        } else {
            abort_if($nilai && ! $item->is_keluar, 422, 'Barang belum dicentang keluar.');
            $item->update([
                'is_kembali' => $nilai,
                'jumlah_kembali' => $nilai ? ($data['jumlah_kembali'] ?? $item->jumlah) : null,
                'kembali_at' => $nilai ? now() : null,
                'dikembalikan_oleh' => $nilai ? $request->user()->id : null,
                'catatan' => $data['catatan'] ?? $item->catatan,
            ]);
        }

        return back()->with('success', 'Checklist diperbarui.');
    }
}
