<x-layout title="Katalog Paket">
    <section class="rounded-2xl bg-gradient-to-br from-brand-600 to-brand-900 px-6 py-10 text-white">
        <h1 class="text-3xl font-bold">Rias & Dekorasi Pernikahan Khas Sunda</h1>
        <p class="mt-2 max-w-2xl text-brand-100">DP fleksibel berapa pun dan dapat dikembalikan 100% bila batal. Harga dikunci saat DP masuk; pelunasan minimal 80% paling lambat H-7.</p>
        <a href="{{ auth()->check() ? route('portal.pesan') : route('register') }}" class="btn mt-5 bg-white text-brand-700 hover:bg-brand-50">Pesan Sekarang</a>
    </section>

    <h2 class="mt-8 text-xl font-bold">Paket Dasar</h2>
    <div class="mt-3 grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        @foreach ($packages as $p)
            <div class="card flex flex-col">
                <p class="text-xs font-semibold text-brand-600 uppercase">{{ $p->kode }} · v{{ $p->versi }}</p>
                <h3 class="text-lg font-bold">{{ $p->nama }}</h3>
                <p class="mt-1 text-2xl font-bold text-brand-700">{{ rupiah($p->harga_dasar) }}</p>
                <ul class="mt-3 flex-1 list-inside list-disc space-y-1 text-sm text-stone-600">
                    @foreach ($p->rincian ?? [] as $r)<li>{{ $r }}</li>@endforeach
                </ul>
                <a href="{{ auth()->check() ? route('portal.pesan', ['paket' => $p->id]) : route('register') }}" class="btn-primary mt-4">Pilih Paket</a>
            </div>
        @endforeach
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        <div class="card">
            <h2 class="text-lg font-bold">Komponen Tambahan</h2>
            <p class="text-sm text-stone-500">Harga mengikuti pasar dan dikunci saat DP.</p>
            <table class="table mt-3">
                <tbody class="divide-y divide-stone-100">
                    @foreach ($components as $c)
                        <tr><td>{{ $c->nama }}</td><td class="text-right font-medium whitespace-nowrap">{{ rupiah($c->harga_satuan) }}/{{ $c->tipe_harga === \App\Enums\PriceType::PerCm ? 'cm' : $c->satuan }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card">
            <h2 class="text-lg font-bold">Layanan Mitra Keluarga</h2>
            <p class="text-sm text-stone-500">Catering, MC, among tamu, dan dokumentasi.</p>
            <table class="table mt-3">
                <tbody class="divide-y divide-stone-100">
                    @foreach ($services as $s)
                        <tr><td>{{ $s->nama_layanan }}</td><td class="text-right font-medium whitespace-nowrap">{{ rupiah($s->tarif_klien) }}/{{ $s->satuan }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-layout>
