<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ApprovalStatus;
use App\Http\Controllers\Controller;
use App\Models\VendorAvailabilityRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AvailabilityController extends Controller
{
    public function index(): View
    {
        return view('admin.availability', [
            'requests' => VendorAvailabilityRequest::with(['vendor', 'peninjau'])
                ->orderByRaw("status = 'menunggu' desc")->orderBy('tanggal')->paginate(30),
        ]);
    }

    public function review(Request $request, VendorAvailabilityRequest $availability): RedirectResponse
    {
        $data = $request->validate(['keputusan' => ['required', 'in:disetujui,ditolak'], 'catatan_admin' => ['nullable', 'string', 'max:500']]);
        abort_unless($availability->status === ApprovalStatus::Menunggu, 422, 'Pengajuan sudah ditinjau.');

        $availability->update([
            'status' => ApprovalStatus::from($data['keputusan']),
            'catatan_admin' => $data['catatan_admin'] ?? null,
            'ditinjau_oleh' => $request->user()->id,
            'ditinjau_at' => now(),
        ]);

        return back()->with('success', 'Pengajuan ketersediaan '.$availability->status->label().'.');
    }
}
