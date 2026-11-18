<x-layout title="Ketersediaan Vendor">
    <h1 class="text-2xl font-bold">Pengajuan Ketersediaan Vendor</h1>
    <div class="card mt-4 overflow-x-auto p-0">
        <table class="table">
            <thead><tr><th>Vendor</th><th>Tanggal</th><th>Ketersediaan</th><th>Alasan</th><th>Status</th><th></th></tr></thead>
            <tbody class="divide-y divide-stone-100">
                @forelse ($requests as $r)
                    <tr>
                        <td>{{ $r->vendor->name }}</td>
                        <td>{{ tanggal($r->tanggal, 'D, j M Y') }}</td>
                        <td>{{ $r->ketersediaan === \App\Enums\AvailabilityType::Tersedia ? 'Tersedia' : 'Tidak tersedia' }}</td>
                        <td class="text-stone-600">{{ $r->alasan }}</td>
                        <td>{{ $r->status->label() }}@if ($r->peninjau)<p class="text-xs text-stone-500">{{ $r->peninjau->name }}</p>@endif</td>
                        <td class="whitespace-nowrap">
                            @if ($r->status === \App\Enums\ApprovalStatus::Menunggu)
                                <form method="POST" action="{{ route('admin.availability.review', $r) }}" class="inline">@csrf<input type="hidden" name="keputusan" value="disetujui"><button class="btn-success px-3 py-1">Setujui</button></form>
                                <form method="POST" action="{{ route('admin.availability.review', $r) }}" class="inline">@csrf<input type="hidden" name="keputusan" value="ditolak"><button class="btn-danger px-3 py-1">Tolak</button></form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-stone-500">Belum ada pengajuan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $requests->links() }}</div>
</x-layout>
