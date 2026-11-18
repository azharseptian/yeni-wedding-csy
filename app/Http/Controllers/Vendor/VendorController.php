<?php

namespace App\Http\Controllers\Vendor;

use App\Enums\ApprovalStatus;
use App\Enums\AvailabilityType;
use App\Enums\ScheduleStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Vendor Keluarga: penugasan miliknya, tarif layanannya sendiri, dan pengajuan ketersediaan. */
class VendorController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        return view('vendor.index', [
            'tugas' => $user->schedules()->with(['booking.client', 'location'])
                ->where('status', ScheduleStatus::Dijadwalkan->value)
                ->whereDate('tanggal', '>=', Carbon::today())->orderBy('waktu_mulai')->get(),
            'layanan' => $user->vendorServices()->where('is_active', true)->orderBy('nama_layanan')->get(['id', 'nama_layanan', 'satuan', 'tarif_vendor'])
                ->each->makeVisible('tarif_vendor'),
            'pengajuan' => $user->availabilityRequests()->latest()->take(10)->get(),
        ]);
    }

    public function storeAvailability(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tanggal' => ['required', 'date', 'after_or_equal:today'],
            'ketersediaan' => ['required', Rule::enum(AvailabilityType::class)],
            'alasan' => ['nullable', 'string', 'max:300'],
        ]);

        $request->user()->availabilityRequests()->create($data + ['status' => ApprovalStatus::Menunggu]);

        return back()->with('success', 'Pengajuan terkirim. Tunggu persetujuan Admin.');
    }
}
