<x-layout title="Jadwal Saya">
    <h1 class="text-xl font-bold">Jadwal Saya</h1>
    @forelse ($perHari as $tgl => $items)
        @php($hari = \Illuminate\Support\Carbon::parse($tgl))
        <h2 class="mt-5 text-sm font-semibold tracking-wide text-stone-500 uppercase">{{ $hari->isToday() ? 'Hari ini · ' : ($hari->isTomorrow() ? 'Besok · ' : '') }}{{ tanggal($hari, 'l, j F Y') }}</h2>
        <div class="mt-2 space-y-3">
            @foreach ($items as $s)
                <a href="{{ route('tim.acara', $s->booking_id) }}" class="card block p-4 active:bg-stone-50">
                    <div class="flex items-center justify-between">
                        <p class="text-2xl font-bold text-brand-700">{{ $s->waktu_mulai->format('H:i') }}<span class="text-base font-normal text-stone-500"> – {{ $s->waktu_selesai->format('H:i') }}</span></p>
                        <span class="rounded-full bg-brand-50 px-2.5 py-0.5 text-xs font-semibold text-brand-700">{{ $s->peran->label() }}</span>
                    </div>
                    <p class="mt-1 font-semibold">{{ $s->booking->client->namaPasangan() }}</p>
                    <p class="text-sm text-stone-600"><x-icon name="pin" /> {{ $s->location->nama }}, {{ $s->location->kota }}</p>
                    <p class="mt-2 text-sm font-semibold text-brand-700">Lihat detail & checklist →</p>
                </a>
            @endforeach
        </div>
    @empty
        <div class="card mt-4 text-center text-stone-500">Belum ada jadwal penugasan.</div>
    @endforelse
</x-layout>
