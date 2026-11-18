<x-layout title="Pesanan Saya">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-bold">Pesanan Saya</h1>
        <a href="{{ route('portal.pesan') }}" class="btn-primary">+ Pesan Paket</a>
    </div>
    <div class="mt-4 grid gap-4 md:grid-cols-2">
        @forelse ($bookings as $b)
            <a href="{{ route('portal.pesanan', $b) }}" class="card block hover:border-brand-300">
                <div class="flex items-center justify-between gap-2"><span class="text-sm text-stone-500">{{ $b->kode_booking }}</span><x-status-badge :status="$b->status" /></div>
                <p class="mt-2 text-lg font-bold">{{ tanggal($b->tanggal_acara, 'l, j F Y') }}</p>
                <p class="text-sm text-stone-600">{{ $b->location->nama }}, {{ $b->location->kota }}</p>
                @if ($b->status !== \App\Enums\BookingStatus::BatalRefund)<x-progress :booking="$b" class="mt-3" />@endif
            </a>
        @empty
            <div class="card text-center text-stone-500 md:col-span-2">Belum ada pesanan. <a href="{{ route('portal.pesan') }}" class="font-semibold text-brand-700">Pesan paket sekarang</a>.</div>
        @endforelse
    </div>
</x-layout>
