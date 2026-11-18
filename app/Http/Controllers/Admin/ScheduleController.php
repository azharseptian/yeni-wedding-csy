<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ScheduleRole;
use App\Enums\ScheduleStatus;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Schedule;
use App\Models\SystemParameter;
use App\Services\Scheduling\ConflictDetectionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    public function index(Request $request, ConflictDetectionService $conflicts): View
    {
        $tanggal = Carbon::parse($request->input('tanggal', today()))->startOfDay();
        $schedules = Schedule::with(['user', 'booking.client', 'location'])
            ->whereDate('tanggal', $tanggal)->where('status', ScheduleStatus::Dijadwalkan->value)
            ->orderBy('user_id')->orderBy('waktu_mulai')->get();

        $awal = $tanggal->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY);
        $akhir = $tanggal->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);
        $acaraPerHari = Booking::dateLocked()->whereBetween('tanggal_acara', [$awal->toDateString(), $akhir->toDateString()])
            ->selectRaw('tanggal_acara, count(*) as jumlah')->groupBy('tanggal_acara')->pluck('jumlah', 'tanggal_acara')
            ->mapWithKeys(fn ($v, $k) => [Carbon::parse($k)->toDateString() => $v]);

        return view('admin.schedules', [
            'tanggal' => $tanggal,
            'awal' => $awal,
            'akhir' => $akhir,
            'acaraPerHari' => $acaraPerHari,
            'perOrang' => $schedules->groupBy(fn (Schedule $s) => $s->user?->name ?? ($s->nama_tenaga_lepas.' (tenaga lepas)')),
            'bentrok' => $conflicts->auditDay($tanggal),
            'kru' => $schedules->filter(fn (Schedule $s) => $s->peran->countsTowardDecorCapacity() && ! $s->is_tenaga_lepas)->count(),
            'kapasitas' => (int) SystemParameter::get('kapasitas_kru_dekorasi', 5),
        ]);
    }

    public function store(Request $request, ConflictDetectionService $service): RedirectResponse
    {
        $data = $request->validate([
            'booking_id' => ['required', 'exists:bookings,id'],
            'peran' => ['required', Rule::enum(ScheduleRole::class)],
            'user_id' => ['required_unless:is_tenaga_lepas,1', 'nullable', 'exists:users,id'],
            'is_tenaga_lepas' => ['nullable', 'boolean'],
            'nama_tenaga_lepas' => ['required_if:is_tenaga_lepas,1', 'nullable', 'string', 'max:100'],
            'phone_tenaga_lepas' => ['nullable', 'string', 'max:20'],
            'tanggal' => ['required', 'date'],
            'jam_mulai' => ['required', 'date_format:H:i'],
            'jam_selesai' => ['required', 'date_format:H:i'],
            'selesai_besok' => ['nullable', 'boolean'],
            'catatan' => ['nullable', 'string', 'max:500'],
            'alasan_override' => ['nullable', 'string', 'max:500'],
        ], ['user_id.required_unless' => 'Pilih petugas atau centang tenaga lepas.']);

        $booking = Booking::findOrFail($data['booking_id']);
        $tanggal = Carbon::parse($data['tanggal']);
        $mulai = $tanggal->copy()->setTimeFromTimeString($data['jam_mulai']);
        $selesai = $tanggal->copy()->setTimeFromTimeString($data['jam_selesai']);
        if ($selesai->lte($mulai) || $request->boolean('selesai_besok')) {
            $selesai->addDay();
        }

        $service->assign([
            'booking_id' => $booking->id,
            'user_id' => $request->boolean('is_tenaga_lepas') ? null : $data['user_id'],
            'peran' => $data['peran'],
            'location_id' => $booking->location_id,
            'is_tenaga_lepas' => $request->boolean('is_tenaga_lepas'),
            'nama_tenaga_lepas' => $data['nama_tenaga_lepas'] ?? null,
            'phone_tenaga_lepas' => $data['phone_tenaga_lepas'] ?? null,
            'tanggal' => $tanggal->toDateString(),
            'waktu_mulai' => $mulai,
            'waktu_selesai' => $selesai,
            'catatan' => $data['catatan'] ?? null,
        ], $request->user(), $data['alasan_override'] ?? null);

        return back()->with('success', 'Penugasan disimpan'.(! empty($data['alasan_override']) ? ' (override tercatat di log).' : '.'));
    }

    public function destroy(Schedule $schedule): RedirectResponse
    {
        $schedule->update(['status' => ScheduleStatus::Dibatalkan]);
        AuditLog::record('schedule.dibatalkan', $schedule, null, null, 'Penugasan dibatalkan Admin.');

        return back()->with('success', 'Penugasan dibatalkan.');
    }
}
