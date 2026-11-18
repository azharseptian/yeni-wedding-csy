<x-layout title="Dasbor">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold">Dasbor Owner</h1>
            <p class="text-sm text-stone-500">{{ tanggal(today(), 'l, j F Y') }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.bookings.create') }}" class="btn-primary">+ Pesanan Baru</a>
            <a href="{{ route('admin.schedules.index') }}" class="btn-secondary">Atur Jadwal</a>
            <a href="{{ route('admin.pricing.index') }}" class="btn-secondary">Ubah Harga</a>
            <a href="{{ route('admin.reports.index') }}" class="btn-secondary">Laporan Bulan Ini</a>
        </div>
    </div>

    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat label="Kas masuk bersih bulan ini" :value="rupiah($bulanIni['kas_bersih'])" :hint="'DP '.rupiah($bulanIni['dp_masuk']).' · pelunasan '.rupiah($bulanIni['pelunasan'])" tone="emerald" />
        <x-stat label="Margin vendor bulan ini" :value="rupiah($bulanIni['margin_vendor'])" :hint="$bulanIni['jumlah_acara'].' acara'" />
        <x-stat label="Belum memenuhi ambang" :value="$belumAmbang->count()" hint="Perlu pengingat WhatsApp" tone="amber" />
        <x-stat label="Menunggu tindakan" :value="$pendingPayments->count() + $pendingAvailability" :hint="$pendingPayments->count().' bukti transfer · '.$pendingAvailability.' ketersediaan vendor · '.$penawaran.' penawaran'" tone="brand" />
    </div>

    @if ($bentrok->isNotEmpty())
        <div class="mt-6 rounded-xl border border-red-300 bg-red-50 p-4">
            <h2 class="font-bold text-red-800">BENTROK jadwal terdeteksi</h2>
            <ul class="mt-2 space-y-1 text-sm text-red-800">
                @foreach ($bentrok as $tgl => $items)
                    <li><a class="font-semibold underline" href="{{ route('admin.schedules.index', ['tanggal' => $tgl]) }}">{{ tanggal(\Illuminate\Support\Carbon::parse($tgl), 'D, j M') }}</a>: {{ count($items) }} penugasan bermasalah</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="card lg:col-span-2">
            <h2 class="font-bold text-amber-800">Belum Memenuhi Ambang 80% (H-7)</h2>
            @forelse ($belumAmbang as $b)
                <div class="mt-3 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-amber-200 bg-amber-50 p-3">
                    <div>
                        <a href="{{ route('admin.bookings.show', $b) }}" class="font-semibold hover:underline">{{ $b->kode_booking }} · {{ $b->client->namaPasangan() }}</a>
                        <p class="text-xs text-stone-600">Acara {{ tanggal($b->tanggal_acara, 'j M Y') }} · dibayar {{ number_format($b->persentaseDibayar(), 1, ',', '.') }}% · kurang {{ rupiah($payments->shortfallToThreshold($b)) }}</p>
                    </div>
                    <a href="{{ $payments->reminderLink($b) }}" target="_blank" rel="noopener" class="btn-success">Kirim Pengingat WA</a>
                </div>
            @empty
                <p class="mt-2 text-sm text-stone-500">Semua pesanan pada H-7 sudah memenuhi ambang.</p>
            @endforelse
        </div>
        <div class="card">
            <h2 class="font-bold">Jadwal Hari Ini</h2>
            @forelse ($jadwalHariIni as $s)
                <div class="mt-2 border-l-4 border-brand-500 pl-3 text-sm">
                    <p class="font-semibold">{{ $s->waktu_mulai->format('H:i') }}–{{ $s->waktu_selesai->format('H:i') }} · {{ $s->user?->name ?? $s->nama_tenaga_lepas }}</p>
                    <p class="text-stone-500">{{ $s->peran->label() }} · {{ $s->location->nama }}</p>
                </div>
            @empty
                <p class="mt-2 text-sm text-stone-500">Tidak ada penugasan.</p>
            @endforelse
        </div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <div class="card">
            <h2 class="font-bold">Bukti Transfer Menunggu Verifikasi</h2>
            @forelse ($pendingPayments as $p)
                <div class="mt-3 flex flex-wrap items-center justify-between gap-2 border-b border-stone-100 pb-3">
                    <div class="text-sm">
                        <a href="{{ route('admin.bookings.show', $p->booking) }}" class="font-semibold hover:underline">{{ $p->booking->kode_booking }}</a> · {{ rupiah($p->nominal) }}
                        <p class="text-xs text-stone-500">{{ $p->nama_pengirim }} ({{ $p->bank_pengirim }}) · {{ tanggal($p->tanggal_bayar, 'j M Y') }}</p>
                    </div>
                    <div class="flex gap-2">
                        <a href="{{ route('admin.payments.proof', $p) }}" target="_blank" class="btn-secondary px-3 py-1">Lihat Bukti</a>
                        <form method="POST" action="{{ route('admin.payments.verify', $p) }}">@csrf<input type="hidden" name="keputusan" value="terima"><button class="btn-success px-3 py-1">Terima</button></form>
                        <form method="POST" action="{{ route('admin.payments.verify', $p) }}">@csrf<input type="hidden" name="keputusan" value="tolak"><button class="btn-danger px-3 py-1">Tolak</button></form>
                    </div>
                </div>
            @empty
                <p class="mt-2 text-sm text-stone-500">Tidak ada.</p>
            @endforelse
        </div>
        <div class="card">
            <h2 class="font-bold">Acara 30 Hari ke Depan</h2>
            <table class="table mt-2">
                <tbody class="divide-y divide-stone-100">
                    @forelse ($upcoming as $b)
                        <tr>
                            <td class="whitespace-nowrap">{{ tanggal($b->tanggal_acara, 'D, j M') }}</td>
                            <td><a class="font-medium hover:underline" href="{{ route('admin.bookings.show', $b) }}">{{ $b->client->namaPasangan() }}</a><p class="text-xs text-stone-500">{{ $b->location->kota }}</p></td>
                            <td class="text-right"><x-status-badge :status="$b->status" /></td>
                        </tr>
                    @empty
                        <tr><td class="text-stone-500">Belum ada acara.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layout>
