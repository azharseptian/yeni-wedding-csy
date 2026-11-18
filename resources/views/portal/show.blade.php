<x-layout :title="$booking->kode_booking">
    <a href="{{ route('portal.beranda') }}" class="text-sm text-brand-700">← Pesanan saya</a>
    <div class="mt-2 flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold">{{ tanggal($booking->tanggal_acara, 'l, j F Y') }}</h1>
            <p class="text-sm text-stone-600">{{ $booking->kode_booking }} · {{ $booking->jenis_acara->label() }} · {{ $booking->location->nama }}, {{ $booking->location->kota }}</p>
            <x-status-badge :status="$booking->status" class="mt-2" />
        </div>
        @if ($booking->is_price_locked && $booking->status !== \App\Enums\BookingStatus::BatalRefund)
            <a href="{{ route('kesepakatan.pdf', $booking) }}" class="btn-primary">Unduh Lembar Kesepakatan (PDF)</a>
        @endif
    </div>

    @if ($booking->status === \App\Enums\BookingStatus::Penawaran)
        <div class="mt-4 rounded-lg border border-sky-300 bg-sky-50 p-3 text-sm text-sky-900">Tanggal masih <strong>tentatif</strong>. Bayar DP berapa pun lalu unggah bukti transfer untuk mengunci tanggal & harga{{ today()->gte($booking->batas_pelunasan) ? '. Karena acara sudah dekat (≤ H-7), pembayaran minimal '.$booking->ambang_pelunasan_persen.'% diperlukan' : '' }}.</div>
    @elseif ($booking->status === \App\Enums\BookingStatus::BelumMemenuhiAmbang)
        <div class="mt-4 rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900">Pembayaran belum mencapai {{ $booking->ambang_pelunasan_persen }}%. Mohon lunasi kekurangan <strong>{{ rupiah($kekurangan) }}</strong>.</div>
    @elseif ($booking->status === \App\Enums\BookingStatus::BatalRefund)
        <div class="mt-4 rounded-lg border border-red-300 bg-red-50 p-3 text-sm text-red-900">Pesanan dibatalkan. @foreach ($booking->refunds as $r)Refund {{ rupiah($r->nominal) }} pada {{ tanggal($r->tanggal_refund) }}.@endforeach</div>
    @endif

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <div class="card">
                <h2 class="font-bold">Rincian Biaya</h2>
                <table class="table mt-2">
                    <tbody class="divide-y divide-stone-100">
                        @foreach ($booking->items as $i)
                            <tr><td>{{ $i->nama_item }}</td><td class="text-right">{{ (float) $i->kuantitas }} {{ $i->tipe_harga === \App\Enums\PriceType::PerCm ? 'bentang' : $i->satuan }}</td><td class="text-right">{{ rupiah($i->subtotal_klien) }}</td></tr>
                        @endforeach
                    </tbody>
                    <tfoot><tr class="font-bold"><td colspan="2">Total Tagihan</td><td class="text-right">{{ rupiah($booking->total_tagihan) }}</td></tr></tfoot>
                </table>
                @if ($booking->is_price_locked)<p class="mt-2 text-xs text-stone-500"><x-icon name="lock" /> Harga terkunci sejak {{ tanggal($booking->price_locked_at) }}.</p>@endif
            </div>

            <div class="card">
                <h2 class="font-bold">Pembayaran</h2>
                <x-progress :booking="$booking" class="mt-3" />
                <p class="mt-2 text-sm text-stone-600">Batas pembayaran {{ $booking->ambang_pelunasan_persen }}%: <strong>{{ tanggal($booking->batas_pelunasan, 'l, j F Y') }}</strong></p>
                <table class="table mt-3">
                    <tbody class="divide-y divide-stone-100">
                        @forelse ($booking->payments as $p)
                            <tr><td>{{ tanggal($p->tanggal_bayar, 'j M Y') }}</td><td>{{ $p->jenis->label() }} · {{ $p->metode->label() }}</td><td class="text-right">{{ rupiah($p->nominal) }}</td><td>{{ $p->status_verifikasi->label() }}</td></tr>
                        @empty
                            <tr><td class="text-stone-500">Belum ada pembayaran.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($booking->schedules->isNotEmpty())
                <div class="card">
                    <h2 class="font-bold">Jadwal Tim</h2>
                    <ul class="mt-2 space-y-1 text-sm">
                        @foreach ($booking->schedules as $s)
                            <li>{{ tanggal($s->tanggal, 'D, j M') }} {{ $s->waktu_mulai->format('H:i') }}–{{ $s->waktu_selesai->format('H:i') }} · {{ $s->peran->label() }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        @if (! in_array($booking->status, [\App\Enums\BookingStatus::Lunas, \App\Enums\BookingStatus::BatalRefund], true))
            <div class="space-y-4">
                <div class="card text-sm">
                    <h2 class="font-bold">Rekening Pembayaran</h2>
                    @foreach ($rekening as $r)<p class="mt-2"><strong>{{ $r['bank'] }}</strong> {{ $r['nomor'] }}<br>a.n. {{ $r['atas_nama'] }}</p>@endforeach
                </div>
                <form method="POST" action="{{ route('portal.bayar', $booking) }}" enctype="multipart/form-data" class="card space-y-3">
                    @csrf
                    <h2 class="font-bold">Unggah Bukti Transfer</h2>
                    <div><label class="label">Nominal (Rp)</label><input class="input" type="number" min="1" max="{{ (int) $booking->sisaTagihan() }}" name="nominal" value="{{ old('nominal') }}" required></div>
                    <div><label class="label">Tanggal Transfer</label><input class="input" type="date" name="tanggal_bayar" max="{{ today()->toDateString() }}" value="{{ old('tanggal_bayar', today()->toDateString()) }}" required></div>
                    <div><label class="label">Bank Pengirim</label><input class="input" name="bank_pengirim" value="{{ old('bank_pengirim') }}" required></div>
                    <div><label class="label">Nama Pemilik Rekening</label><input class="input" name="nama_pengirim" value="{{ old('nama_pengirim') }}" required></div>
                    <div><label class="label">Foto Bukti Transfer</label><input class="input" type="file" name="bukti_transfer" accept="image/*" required></div>
                    <button class="btn-primary w-full">Kirim Bukti</button>
                </form>
                @if ($waAdmin)<a href="{{ \App\Support\Whatsapp::link($waAdmin, 'Halo Admin Yeni Wedding, saya ingin bertanya tentang pesanan '.$booking->kode_booking) }}" target="_blank" rel="noopener" class="btn-success w-full">Tanya Admin via WhatsApp</a>@endif
            </div>
        @endif
    </div>
</x-layout>
