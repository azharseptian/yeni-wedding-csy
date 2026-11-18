<x-layout title="Detail Acara">
    <a href="{{ route('tim.jadwal') }}" class="text-sm text-brand-700">← Jadwal saya</a>
    <h1 class="mt-2 text-xl font-bold">{{ $booking->client->namaPasangan() }}</h1>
    <p class="text-sm text-stone-600">{{ tanggal($booking->tanggal_acara, 'l, j F Y') }} · {{ $booking->jenis_acara->label() }}</p>

    <div class="card mt-4 space-y-2 p-4">
        @foreach ($tugas as $s)
            <p><strong>{{ $s->peran->label() }}</strong> · {{ tanggal($s->tanggal, 'D, j M') }} {{ $s->waktu_mulai->format('H:i') }}–{{ $s->waktu_selesai->format('H:i') }}</p>
        @endforeach
        <p class="text-sm"><x-icon name="pin" /> {{ $booking->location->nama }}, {{ $booking->location->kota }}</p>
        @if ($booking->alamat_acara)<p class="text-sm text-stone-600">{{ $booking->alamat_acara }}</p>@endif
        @if ($booking->location->latitude)
            <a class="btn-secondary w-full" target="_blank" rel="noopener" href="https://www.google.com/maps/search/?api=1&query={{ $booking->location->latitude }},{{ $booking->location->longitude }}">Buka di Google Maps</a>
        @endif
    </div>

    <h2 class="mt-6 font-bold">Checklist Perlengkapan</h2>
    <p class="text-xs text-stone-500">Centang <strong>Keluar</strong> saat pengemasan dan <strong>Kembali</strong> saat pembongkaran.</p>
    <div class="mt-2 space-y-2">
        @forelse ($booking->inventories as $item)
            <div class="card p-3">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <p class="font-semibold">{{ $item->inventory->nama }}{{ $item->inventory->ukuran ? ' ('.$item->inventory->ukuran.')' : '' }}</p>
                        <p class="text-xs text-stone-500">Jumlah {{ $item->jumlah }} {{ $item->inventory->satuan }} @if ($item->inventory->is_rawan_hilang)<span class="ml-1 rounded bg-amber-100 px-1.5 text-amber-800">rawan hilang</span>@endif</p>
                        @if ($item->is_kembali && $item->jumlah_kembali < $item->jumlah)<p class="text-xs font-semibold text-red-700">Kurang {{ $item->jumlah - $item->jumlah_kembali }} saat kembali</p>@endif
                    </div>
                </div>
                <div class="mt-3 grid grid-cols-2 gap-2">
                    <form method="POST" action="{{ route('tim.checklist', $item) }}">
                        @csrf @method('PATCH')
                        <input type="hidden" name="tahap" value="keluar"><input type="hidden" name="nilai" value="{{ $item->is_keluar ? 0 : 1 }}">
                        <button @class(['btn w-full py-3', 'bg-emerald-600 text-white' => $item->is_keluar, 'border border-stone-300 bg-white' => ! $item->is_keluar])>{{ $item->is_keluar ? '✔ Keluar' : '☐ Keluar' }}</button>
                    </form>
                    <form method="POST" action="{{ route('tim.checklist', $item) }}" class="flex gap-1">
                        @csrf @method('PATCH')
                        <input type="hidden" name="tahap" value="kembali"><input type="hidden" name="nilai" value="{{ $item->is_kembali ? 0 : 1 }}">
                        @if (! $item->is_kembali && $item->jumlah > 1)<input class="input w-16" type="number" min="0" max="{{ $item->jumlah }}" name="jumlah_kembali" value="{{ $item->jumlah }}" title="Jumlah kembali">@endif
                        <button @disabled(! $item->is_keluar) @class(['btn w-full py-3', 'bg-emerald-600 text-white' => $item->is_kembali, 'border border-stone-300 bg-white' => ! $item->is_kembali])>{{ $item->is_kembali ? '✔ Kembali' : '☐ Kembali' }}</button>
                    </form>
                </div>
            </div>
        @empty
            <div class="card text-center text-stone-500">Belum ada daftar perlengkapan.</div>
        @endforelse
    </div>
</x-layout>
