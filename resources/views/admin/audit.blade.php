<x-layout title="Log Aktivitas">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-bold">Log Aktivitas</h1>
        <form class="flex gap-2">
            <select class="input" name="event"><option value="">Semua kejadian</option>@foreach ($events as $e)<option @selected(request('event') === $e)>{{ $e }}</option>@endforeach</select>
            <button class="btn-secondary">Filter</button>
        </form>
    </div>
    <div class="card mt-4 overflow-x-auto p-0">
        <table class="table">
            <thead><tr><th>Waktu</th><th>Pengguna</th><th>Kejadian</th><th>Keterangan</th></tr></thead>
            <tbody class="divide-y divide-stone-100">
                @foreach ($logs as $log)
                    <tr>
                        <td class="whitespace-nowrap">{{ $log->created_at?->format('d/m/Y H:i') }}</td>
                        <td>{{ $log->user?->name ?? 'Sistem' }}</td>
                        <td><span class="rounded bg-stone-100 px-1.5 py-0.5 font-mono text-xs">{{ $log->event }}</span></td>
                        <td>{{ $log->keterangan }}
                            @if ($log->auditable instanceof \App\Models\Booking)<a class="text-brand-700 hover:underline" href="{{ route('admin.bookings.show', $log->auditable) }}">{{ $log->auditable->kode_booking }}</a>@endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $logs->links() }}</div>
</x-layout>
