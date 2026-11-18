<x-layout title="Jadwal">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-bold">Jadwal & Penugasan</h1>
        <form class="flex gap-2"><input class="input" type="date" name="tanggal" value="{{ $tanggal->toDateString() }}"><button class="btn-secondary">Lihat</button></form>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="card">
            <div class="flex items-center justify-between">
                <a href="{{ route('admin.schedules.index', ['tanggal' => $tanggal->copy()->subMonthNoOverflow()->startOfMonth()->toDateString()]) }}" class="px-2">‹</a>
                <h2 class="font-bold">{{ tanggal($tanggal, 'F Y') }}</h2>
                <a href="{{ route('admin.schedules.index', ['tanggal' => $tanggal->copy()->addMonthNoOverflow()->startOfMonth()->toDateString()]) }}" class="px-2">›</a>
            </div>
            <div class="mt-3 grid grid-cols-7 gap-1 text-center text-xs">
                @foreach (['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'] as $h)<div class="font-semibold text-stone-500">{{ $h }}</div>@endforeach
                @for ($d = $awal->copy(); $d->lte($akhir); $d->addDay())
                    @php($n = $acaraPerHari[$d->toDateString()] ?? 0)
                    <a href="{{ route('admin.schedules.index', ['tanggal' => $d->toDateString()]) }}" @class([
                        'rounded-md py-2',
                        'text-stone-300' => $d->month !== $tanggal->month,
                        'bg-brand-600 font-bold text-white' => $d->isSameDay($tanggal),
                        'bg-brand-100 font-semibold text-brand-900' => $n && ! $d->isSameDay($tanggal),
                        'hover:bg-stone-100' => ! $n && ! $d->isSameDay($tanggal),
                    ])>{{ $d->day }}@if ($n)<span class="block text-[10px]">{{ $n }} acara</span>@endif</a>
                @endfor
            </div>
            <p class="mt-3 text-xs text-stone-500">Tanggal berwarna = sudah terkunci DP.</p>
        </div>

        <div class="space-y-4 lg:col-span-2">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-stat label="Kru dekorasi internal" :value="$kru.' / '.$kapasitas" :tone="$kru > $kapasitas ? 'red' : 'emerald'" :hint="$kru > $kapasitas ? 'Melebihi kapasitas — gunakan dekorasi bertahap H-2/H-1 atau tenaga lepas' : 'Dalam kapasitas'" />
                <x-stat label="Bentrok tanpa override" :value="count($bentrok)" :tone="count($bentrok) ? 'red' : 'emerald'" />
            </div>
            @foreach ($bentrok as $id => $result)
                <div class="rounded-lg border border-red-300 bg-red-50 p-3 text-sm text-red-800">
                    <p class="font-bold">BENTROK (penugasan #{{ $id }})</p>
                    <ul class="list-inside list-disc">@foreach ($result->messages() as $m)<li>{{ $m }}</li>@endforeach</ul>
                </div>
            @endforeach

            <div class="card">
                <h2 class="font-bold">{{ tanggal($tanggal, 'l, j F Y') }}</h2>
                @forelse ($perOrang as $nama => $items)
                    <div class="mt-4">
                        <p class="font-semibold">{{ $nama }}</p>
                        <div class="mt-1 space-y-1">
                            @foreach ($items as $s)
                                <a href="{{ route('admin.bookings.show', $s->booking_id) }}" class="flex flex-wrap items-center justify-between gap-2 rounded-md border-l-4 {{ $s->is_override ? 'border-amber-500 bg-amber-50' : 'border-brand-500 bg-stone-50' }} px-3 py-2 text-sm hover:bg-stone-100">
                                    <span><strong>{{ $s->waktu_mulai->format('H:i') }}–{{ $s->waktu_selesai->format('H:i') }}</strong> · {{ $s->peran->label() }} · {{ $s->booking->client->namaPasangan() }}</span>
                                    <span class="text-xs text-stone-500">{{ $s->location->nama }}{{ $s->is_override ? ' · override: '.$s->alasan_override : '' }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <p class="mt-2 text-sm text-stone-500">Tidak ada penugasan. Tugaskan petugas dari halaman detail pesanan.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-layout>
