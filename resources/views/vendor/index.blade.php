<x-layout title="Beranda Vendor">
    <h1 class="text-2xl font-bold">Halo, {{ auth()->user()->name }}</h1>

    <section class="mt-6">
        <h2 class="text-xl font-bold">Tugas Saya</h2>
        @forelse ($tugas as $s)
            <div class="card mt-3">
                <p class="text-xl font-bold text-brand-700">{{ tanggal($s->tanggal, 'l, j F Y') }}</p>
                <p class="mt-1 text-lg">Jam {{ $s->waktu_mulai->format('H:i') }} – {{ $s->waktu_selesai->format('H:i') }}</p>
                <p class="mt-1">{{ $s->booking->client->namaPasangan() }}</p>
                <p class="text-stone-600"><x-icon name="pin" /> {{ $s->location->nama }}, {{ $s->location->kota }}</p>
            </div>
        @empty
            <div class="card mt-3 text-stone-500">Belum ada tugas.</div>
        @endforelse
    </section>

    <section class="mt-8">
        <h2 class="text-xl font-bold">Tarif Layanan Saya</h2>
        <div class="card mt-3 divide-y divide-stone-100 p-0">
            @foreach ($layanan as $l)
                <div class="flex items-center justify-between gap-3 px-5 py-4">
                    <span>{{ $l->nama_layanan }}</span>
                    <span class="font-bold whitespace-nowrap">{{ rupiah($l->tarif_vendor) }} <span class="text-sm font-normal text-stone-500">/ {{ $l->satuan }}</span></span>
                </div>
            @endforeach
        </div>
    </section>

    <section class="mt-8">
        <h2 class="text-xl font-bold">Kabari Ketersediaan</h2>
        <form method="POST" action="{{ route('vendor.ketersediaan') }}" class="card mt-3 space-y-4">
            @csrf
            <div>
                <label class="label text-base" for="tanggal">Tanggal</label>
                <input id="tanggal" class="input py-3 text-lg" type="date" name="tanggal" min="{{ today()->toDateString() }}" value="{{ old('tanggal') }}" required>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <button name="ketersediaan" value="tersedia" class="btn-success py-4 text-lg">✔ Saya Bisa</button>
                <button name="ketersediaan" value="tidak_tersedia" class="btn-danger py-4 text-lg">✖ Tidak Bisa</button>
            </div>
            <input class="input py-3" name="alasan" placeholder="Keterangan (boleh dikosongkan)" value="{{ old('alasan') }}">
        </form>
        @if ($pengajuan->isNotEmpty())
            <div class="card mt-3 divide-y divide-stone-100 p-0">
                @foreach ($pengajuan as $p)
                    <div class="flex items-center justify-between px-5 py-3">
                        <span>{{ tanggal($p->tanggal, 'j M Y') }} · {{ $p->ketersediaan === \App\Enums\AvailabilityType::Tersedia ? 'Bisa' : 'Tidak bisa' }}</span>
                        <span @class(['rounded-full px-3 py-1 text-sm font-semibold', 'bg-amber-100 text-amber-800' => $p->status === \App\Enums\ApprovalStatus::Menunggu, 'bg-emerald-100 text-emerald-800' => $p->status === \App\Enums\ApprovalStatus::Disetujui, 'bg-red-100 text-red-800' => $p->status === \App\Enums\ApprovalStatus::Ditolak])>{{ $p->status->label() }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
</x-layout>
