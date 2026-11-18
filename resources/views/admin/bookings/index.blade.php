<x-layout title="Pesanan">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-bold">Pesanan</h1>
        <a href="{{ route('admin.bookings.create') }}" class="btn-primary">+ Pesanan Baru</a>
    </div>
    <form class="mt-4 flex flex-wrap gap-2">
        <input class="input max-w-xs" name="q" value="{{ request('q') }}" placeholder="Cari kode / nama pengantin">
        <select class="input max-w-xs" name="status">
            <option value="">Semua status</option>
            @foreach ($statuses as $s)<option value="{{ $s->value }}" @selected(request('status') === $s->value)>{{ $s->label() }}</option>@endforeach
        </select>
        <button class="btn-secondary">Filter</button>
    </form>
    <div class="card mt-4 overflow-x-auto p-0">
        <table class="table">
            <thead><tr><th>Kode</th><th>Pengantin</th><th>Tanggal Acara</th><th>Lokasi</th><th>Pembayaran</th><th>Status</th></tr></thead>
            <tbody class="divide-y divide-stone-100">
                @forelse ($bookings as $b)
                    <tr class="hover:bg-stone-50">
                        <td><a class="font-semibold text-brand-700 hover:underline" href="{{ route('admin.bookings.show', $b) }}">{{ $b->kode_booking }}</a></td>
                        <td>{{ $b->client->namaPasangan() }}<p class="text-xs text-stone-500">{{ $b->client->phone }}</p></td>
                        <td class="whitespace-nowrap">{{ tanggal($b->tanggal_acara, 'j M Y') }} @if ($b->is_date_locked)<span class="text-stone-400" title="Tanggal terkunci"><x-icon name="lock" /></span>@endif</td>
                        <td>{{ $b->location->kota }}</td>
                        <td class="min-w-56"><x-progress :booking="$b" /></td>
                        <td><x-status-badge :status="$b->status" /></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-stone-500">Tidak ada pesanan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $bookings->links() }}</div>
</x-layout>
