<x-layout :title="$booking->kode_booking">
    @php($aktif = $booking->status !== \App\Enums\BookingStatus::BatalRefund)
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <p class="text-sm text-stone-500"><a href="{{ route('admin.bookings.index') }}" class="hover:underline">Pesanan</a> / {{ $booking->kode_booking }}</p>
            <h1 class="text-2xl font-bold">{{ $booking->client->namaPasangan() }}</h1>
            <p class="text-sm text-stone-600">{{ tanggal($booking->tanggal_acara, 'l, j F Y') }} · {{ $booking->jenis_acara->label() }} · {{ $booking->location->nama }}, {{ $booking->location->kota }}</p>
            <div class="mt-2 flex flex-wrap gap-2 text-xs">
                <x-status-badge :status="$booking->status" />
                <span class="rounded-full bg-stone-100 px-2.5 py-0.5">@if ($booking->is_date_locked)<x-icon name="lock" /> @endif{{ $booking->is_date_locked ? 'Tanggal terkunci' : 'Tanggal belum terkunci' }}</span>
                <span class="rounded-full bg-stone-100 px-2.5 py-0.5">@if ($booking->is_price_locked)<x-icon name="lock" /> @endif{{ $booking->is_price_locked ? 'Harga terkunci '.tanggal($booking->price_locked_at, 'j M Y') : 'Harga mengikuti master' }}</span>
                <span class="rounded-full bg-stone-100 px-2.5 py-0.5">Batas 80%: {{ tanggal($booking->batas_pelunasan, 'j M Y') }}</span>
            </div>
        </div>
        <div class="flex flex-wrap gap-2">
            @if ($booking->is_price_locked && $aktif)<a href="{{ route('kesepakatan.pdf', $booking) }}" class="btn-secondary">Unduh Lembar Kesepakatan (PDF)</a>@endif
            @if ($reminderLink)<a href="{{ $reminderLink }}" target="_blank" rel="noopener" class="btn-success">Kirim Pengingat WA</a>@endif
        </div>
    </div>

    @if ($reminderLink)
        <div class="mt-4 rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900">Sudah H-7 dan pembayaran baru {{ number_format($booking->persentaseDibayar(), 1, ',', '.') }}%. Kekurangan untuk mencapai {{ $booking->ambang_pelunasan_persen }}%: <strong>{{ rupiah($kekurangan) }}</strong>.</div>
    @endif

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <div class="card">
                <div class="flex items-center justify-between"><h2 class="font-bold">Rincian Harga</h2><span class="text-xs text-stone-500">Revisi ke-{{ $booking->revisi_ke }}</span></div>
                <div class="mt-3 overflow-x-auto">
                    <table class="table">
                        <thead><tr><th>Item</th><th class="text-right">Jumlah</th><th class="text-right">Harga Klien</th><th class="text-right">Subtotal</th><th class="text-right text-amber-700">Biaya Vendor</th><th class="text-right text-amber-700">Margin</th></tr></thead>
                        <tbody class="divide-y divide-stone-100">
                            @foreach ($booking->items as $i)
                                <tr>
                                    <td>{{ $i->nama_item }} @if ($i->is_locked)<span class="text-stone-400" title="Terkunci"><x-icon name="lock" /></span>@endif</td>
                                    <td class="text-right">{{ (float) $i->kuantitas }} {{ $i->tipe_harga === \App\Enums\PriceType::PerCm ? 'bentang' : $i->satuan }}</td>
                                    <td class="text-right">{{ rupiah($i->harga_satuan_klien) }}{{ $i->tipe_harga === \App\Enums\PriceType::PerCm ? '/cm' : '' }}</td>
                                    <td class="text-right font-medium">{{ rupiah($i->subtotal_klien) }}</td>
                                    <td class="text-right text-amber-700">{{ $i->subtotal_vendor > 0 ? rupiah($i->subtotal_vendor) : '-' }}</td>
                                    <td class="text-right text-amber-700">{{ $i->margin > 0 ? rupiah($i->margin) : '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="font-bold"><tr><td colspan="3">Total</td><td class="text-right">{{ rupiah($booking->total_tagihan) }}</td><td class="text-right text-amber-700">{{ rupiah($booking->total_biaya_vendor) }}</td><td class="text-right text-amber-700">{{ rupiah($booking->total_margin) }}</td></tr></tfoot>
                    </table>
                </div>
                @if ($aktif)
                    <details class="mt-4">
                        <summary class="cursor-pointer text-sm font-semibold text-brand-700">Revisi lingkup pesanan</summary>
                        <form method="POST" action="{{ route('admin.bookings.revise', $booking) }}" class="mt-3 space-y-4">
                            @csrf @method('PUT')
                            @include('partials.item-fields', ['showVendorCost' => true, 'current' => $booking->items])
                            <div><label class="label">Alasan revisi (tercatat di log)</label><input class="input" name="alasan" required></div>
                            <button class="btn-primary">Simpan Revisi</button>
                        </form>
                    </details>
                @endif
            </div>

            <div class="card">
                <h2 class="font-bold">Pembayaran</h2>
                <x-progress :booking="$booking" class="mt-3" />
                <table class="table mt-4">
                    <thead><tr><th>Tanggal</th><th>Jenis</th><th>Metode</th><th class="text-right">Nominal</th><th>Status</th><th></th></tr></thead>
                    <tbody class="divide-y divide-stone-100">
                        @forelse ($booking->payments as $p)
                            <tr>
                                <td>{{ tanggal($p->tanggal_bayar, 'j M Y') }}</td><td>{{ $p->jenis->label() }}</td><td>{{ $p->metode->label() }}</td>
                                <td class="text-right">{{ rupiah($p->nominal) }}</td><td>{{ $p->status_verifikasi->label() }}</td>
                                <td class="whitespace-nowrap">
                                    @if ($p->bukti_transfer_path)<a class="text-brand-700 hover:underline" target="_blank" href="{{ route('admin.payments.proof', $p) }}">Bukti</a>@endif
                                    @if ($p->status_verifikasi === \App\Enums\VerificationStatus::Menunggu)
                                        <form method="POST" action="{{ route('admin.payments.verify', $p) }}" class="inline">@csrf<input type="hidden" name="keputusan" value="terima"><button class="ml-2 text-emerald-700 hover:underline">Terima</button></form>
                                        <form method="POST" action="{{ route('admin.payments.verify', $p) }}" class="inline">@csrf<input type="hidden" name="keputusan" value="tolak"><button class="ml-2 text-red-700 hover:underline">Tolak</button></form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-stone-500">Belum ada pembayaran.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                @if ($aktif && $booking->status !== \App\Enums\BookingStatus::Lunas)
                    <form method="POST" action="{{ route('admin.payments.store', $booking) }}" enctype="multipart/form-data" class="mt-4 grid gap-3 rounded-lg bg-stone-50 p-4 sm:grid-cols-3">
                        @csrf
                        <div><label class="label">Nominal (Rp)</label><input class="input" type="number" min="1" name="nominal" value="{{ old('nominal') }}" required></div>
                        <div><label class="label">Tanggal Bayar</label><input class="input" type="date" name="tanggal_bayar" value="{{ old('tanggal_bayar', today()->toDateString()) }}" max="{{ today()->toDateString() }}" required></div>
                        <div><label class="label">Metode</label><select class="input" name="metode">@foreach ($methods as $m)<option value="{{ $m->value }}">{{ $m->label() }}</option>@endforeach</select></div>
                        <div><label class="label">Bank Pengirim</label><input class="input" name="bank_pengirim" value="{{ old('bank_pengirim') }}"></div>
                        <div><label class="label">Nama Pengirim</label><input class="input" name="nama_pengirim" value="{{ old('nama_pengirim') }}"></div>
                        <div><label class="label">Bukti Transfer (wajib untuk transfer)</label><input class="input" type="file" name="bukti_transfer" accept="image/*"></div>
                        <div class="sm:col-span-3"><button class="btn-primary">Catat Pembayaran</button> <span class="ml-2 text-xs text-stone-500">DP berapa pun &gt; Rp0 mengonfirmasi pesanan{{ today()->gte($booking->batas_pelunasan) ? '; pesanan ≤ H-7 wajib langsung ≥ '.$booking->ambang_pelunasan_persen.'%' : '' }}.</span></div>
                    </form>
                @endif
                @foreach ($booking->refunds as $r)
                    <p class="mt-3 rounded-lg bg-red-50 p-3 text-sm text-red-800">Refund {{ rupiah($r->nominal) }} · {{ tanggal($r->tanggal_refund, 'j M Y') }} · {{ $r->metode?->label() }} {{ $r->bank_tujuan }} {{ $r->nomor_rekening_tujuan }} a.n. {{ $r->atas_nama }} · diproses {{ $r->pemroses?->name }}</p>
                @endforeach
            </div>

            <div class="card">
                <h2 class="font-bold">Penugasan Tim & Vendor</h2>
                <table class="table mt-2">
                    <tbody class="divide-y divide-stone-100">
                        @forelse ($booking->schedules->sortBy('waktu_mulai') as $s)
                            <tr @class(['opacity-50' => $s->status === \App\Enums\ScheduleStatus::Dibatalkan])>
                                <td class="whitespace-nowrap">{{ tanggal($s->tanggal, 'D, j M') }}<br><span class="text-xs">{{ $s->waktu_mulai->format('H:i') }}–{{ $s->waktu_selesai->format('H:i') }}</span></td>
                                <td>{{ $s->user?->name ?? $s->nama_tenaga_lepas.' (lepas)' }}<p class="text-xs text-stone-500">{{ $s->peran->label() }}</p></td>
                                <td>@if ($s->is_override)<span class="text-xs text-amber-700" title="{{ $s->alasan_override }}">Override: {{ $s->alasan_override }}</span>@endif {{ $s->status->label() }}</td>
                                <td>@if ($s->status === \App\Enums\ScheduleStatus::Dijadwalkan)<form method="POST" action="{{ route('admin.schedules.destroy', $s) }}">@csrf @method('DELETE')<button class="text-xs text-red-700 hover:underline">Batalkan</button></form>@endif</td>
                            </tr>
                        @empty
                            <tr><td class="text-stone-500">Belum ada penugasan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                @if ($aktif)@include('admin.partials.assign-form', ['booking' => $booking])@endif
            </div>
        </div>

        <div class="space-y-6">
            <div class="card text-sm">
                <h2 class="font-bold">Klien</h2>
                <p class="mt-2">{{ $booking->client->nama_kontak }}<br>{{ $booking->client->phone }}<br>{{ $booking->client->email }}</p>
                <p class="mt-2 text-stone-500">{{ $booking->alamat_acara }}</p>
                @if ($booking->catatan)<p class="mt-2 italic text-stone-500">{{ $booking->catatan }}</p>@endif
            </div>

            <div class="card text-sm">
                <h2 class="font-bold">Checklist Inventaris</h2>
                <ul class="mt-2 space-y-1">
                    @forelse ($booking->inventories as $bi)
                        <li class="flex justify-between"><span>{{ $bi->inventory->nama }} × {{ $bi->jumlah }}</span><span class="text-xs">{{ $bi->is_keluar ? '✔ keluar' : '· belum' }} {{ $bi->is_kembali ? '✔ kembali'.($bi->jumlah_kembali < $bi->jumlah ? ' (kurang '.($bi->jumlah - $bi->jumlah_kembali).')' : '') : '' }}</span></li>
                    @empty
                        <li class="text-stone-500">Belum ada barang.</li>
                    @endforelse
                </ul>
                @if ($aktif)
                    <form method="POST" action="{{ route('admin.bookings.inventory', $booking) }}" class="mt-3 flex gap-2">
                        @csrf
                        <select class="input" name="inventory_id">@foreach ($inventories as $inv)<option value="{{ $inv->id }}">{{ $inv->nama }}{{ $inv->ukuran ? ' ('.$inv->ukuran.')' : '' }}</option>@endforeach</select>
                        <input class="input w-20" type="number" min="1" name="jumlah" value="1">
                        <button class="btn-secondary">+</button>
                    </form>
                @endif
            </div>

            @if ($aktif)
                <div class="card border-red-200 text-sm">
                    <h2 class="font-bold text-red-700">Batalkan Pesanan (Klien Batal)</h2>
                    <p class="mt-1 text-stone-600">Refund 100% dana terverifikasi ({{ rupiah($booking->total_dibayar) }}). Tanggal & harga akan dilepas.</p>
                    <form method="POST" action="{{ route('admin.bookings.cancel', $booking) }}" enctype="multipart/form-data" class="mt-3 space-y-2" onsubmit="return confirm('Yakin membatalkan pesanan ini?')">
                        @csrf
                        <input class="input" name="alasan" placeholder="Alasan pembatalan" required>
                        @if ($booking->total_dibayar > 0)
                            <select class="input" name="metode">@foreach ($methods as $m)<option value="{{ $m->value }}">Refund {{ $m->label() }}</option>@endforeach</select>
                            <input class="input" type="date" name="tanggal_refund" value="{{ today()->toDateString() }}">
                            <input class="input" name="bank_tujuan" placeholder="Bank tujuan">
                            <input class="input" name="nomor_rekening_tujuan" placeholder="No. rekening">
                            <input class="input" name="atas_nama" placeholder="Atas nama">
                            <label class="label">Bukti refund (wajib untuk transfer)</label><input class="input" type="file" name="bukti_refund" accept="image/*">
                        @endif
                        <button class="btn-danger w-full">Batalkan & Catat Refund</button>
                    </form>
                </div>
            @endif

            <div class="card text-sm">
                <h2 class="font-bold">Riwayat</h2>
                <ul class="mt-2 space-y-2">
                    @foreach ($booking->auditLogs->sortByDesc('id') as $log)
                        <li><span class="text-xs text-stone-500">{{ $log->created_at?->format('d/m H:i') }} · {{ $log->user?->name ?? 'Sistem' }}</span><br>{{ $log->keterangan ?? $log->event }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</x-layout>
