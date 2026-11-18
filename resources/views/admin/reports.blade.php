<x-layout title="Laporan Keuangan">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold">Laporan Keuangan Bulanan</h1>
            <p class="text-sm text-stone-500">Hanya dapat diakses Owner.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <form class="flex gap-2"><input class="input" type="month" name="bulan" value="{{ $r['bulan']->format('Y-m') }}"><button class="btn-secondary">Tampilkan</button></form>
            <a href="{{ route('admin.reports.pdf', ['bulan' => $r['bulan']->format('Y-m')]) }}" class="btn-primary">Ekspor PDF</a>
            <a href="{{ route('admin.reports.excel', ['bulan' => $r['bulan']->format('Y-m')]) }}" class="btn-success">Ekspor Excel (CSV)</a>
        </div>
    </div>

    <h2 class="mt-6 text-lg font-bold">{{ tanggal($r['bulan'], 'F Y') }}</h2>
    <div class="mt-3 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat label="DP masuk" :value="rupiah($r['dp_masuk'])" tone="sky" />
        <x-stat label="Pelunasan & cicilan" :value="rupiah($r['pelunasan'])" tone="emerald" />
        <x-stat label="Refund DP" :value="rupiah($r['refund'])" tone="red" />
        <x-stat label="Kas masuk bersih" :value="rupiah($r['kas_bersih'])" hint="DP + pelunasan − refund" />
        <x-stat label="Pendapatan acara" :value="rupiah($r['pendapatan_acara'])" :hint="$r['jumlah_acara'].' acara bulan ini'" />
        <x-stat label="Biaya vendor" :value="rupiah($r['biaya_vendor'])" tone="amber" />
        <x-stat label="Margin vendor" :value="rupiah($r['margin_vendor'])" tone="emerald" />
        <x-stat label="Margin bersih" :value="rupiah($r['laba_bersih'])" hint="Pendapatan acara − biaya vendor" tone="brand" />
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <div class="card overflow-x-auto">
            <h3 class="font-bold">Pembayaran Masuk (terverifikasi)</h3>
            <table class="table mt-2">
                <thead><tr><th>Tanggal</th><th>Pesanan</th><th>Jenis</th><th class="text-right">Nominal</th></tr></thead>
                <tbody class="divide-y divide-stone-100">
                    @forelse ($r['payments'] as $p)
                        <tr><td>{{ tanggal($p->tanggal_bayar, 'j M') }}</td><td>{{ $p->booking->kode_booking }}<p class="text-xs text-stone-500">{{ $p->booking->client->namaPasangan() }}</p></td><td>{{ $p->jenis->label() }} · {{ $p->metode->label() }}</td><td class="text-right">{{ rupiah($p->nominal) }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="text-stone-500">Tidak ada.</td></tr>
                    @endforelse
                </tbody>
            </table>
            @if ($r['refunds']->isNotEmpty())
                <h3 class="mt-4 font-bold text-red-700">Refund</h3>
                <table class="table mt-2"><tbody class="divide-y divide-stone-100">
                    @foreach ($r['refunds'] as $f)<tr><td>{{ tanggal($f->tanggal_refund, 'j M') }}</td><td>{{ $f->booking->kode_booking }} · {{ $f->booking->client->namaPasangan() }}</td><td class="text-right">{{ rupiah($f->nominal) }}</td></tr>@endforeach
                </tbody></table>
            @endif
        </div>
        <div class="card overflow-x-auto">
            <h3 class="font-bold">Acara Bulan Ini</h3>
            <table class="table mt-2">
                <thead><tr><th>Tanggal</th><th>Pesanan</th><th class="text-right">Tagihan</th><th class="text-right">Biaya Vendor</th><th class="text-right">Margin</th></tr></thead>
                <tbody class="divide-y divide-stone-100">
                    @forelse ($r['events'] as $b)
                        <tr><td>{{ tanggal($b->tanggal_acara, 'j M') }}</td><td>{{ $b->kode_booking }}<p class="text-xs text-stone-500">{{ $b->status->label() }}</p></td><td class="text-right">{{ rupiah($b->total_tagihan) }}</td><td class="text-right">{{ rupiah($b->total_biaya_vendor) }}</td><td class="text-right">{{ rupiah($b->total_margin) }}</td></tr>
                    @empty
                        <tr><td colspan="5" class="text-stone-500">Tidak ada.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card mt-6 overflow-x-auto">
        <h3 class="font-bold">Ringkasan Tahun {{ $r['bulan']->year }}</h3>
        <table class="table mt-2">
            <thead><tr><th>Bulan</th><th class="text-right">DP</th><th class="text-right">Pelunasan</th><th class="text-right">Refund</th><th class="text-right">Kas Bersih</th><th class="text-right">Biaya Vendor</th><th class="text-right">Margin Bersih</th></tr></thead>
            <tbody class="divide-y divide-stone-100">
                @foreach ($tahunan as $m)
                    <tr @class(['bg-brand-50 font-semibold' => $m['bulan']->month === $r['bulan']->month])>
                        <td><a class="hover:underline" href="{{ route('admin.reports.index', ['bulan' => $m['bulan']->format('Y-m')]) }}">{{ tanggal($m['bulan'], 'F') }}</a></td>
                        <td class="text-right">{{ rupiah($m['dp_masuk']) }}</td><td class="text-right">{{ rupiah($m['pelunasan']) }}</td><td class="text-right">{{ rupiah($m['refund']) }}</td>
                        <td class="text-right">{{ rupiah($m['kas_bersih']) }}</td><td class="text-right">{{ rupiah($m['biaya_vendor']) }}</td><td class="text-right">{{ rupiah($m['laba_bersih']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-layout>
