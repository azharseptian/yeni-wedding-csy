<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; }
    h1 { font-size: 18px; margin: 0; color: #8a2b3a; } h2 { font-size: 13px; margin: 18px 0 6px; border-bottom: 1px solid #ccc; padding-bottom: 3px; }
    table { width: 100%; border-collapse: collapse; } td, th { padding: 4px 6px; vertical-align: top; }
    .items th { background: #f3eded; text-align: left; } .items td, .items th { border: 1px solid #ddd; } .r { text-align: right; }
    .muted { color: #666; } .box { border: 1px solid #ddd; padding: 8px; background: #faf8f8; }
    .sign td { height: 70px; vertical-align: bottom; text-align: center; }
</style>
</head>
<body>
    <table><tr>
        <td><h1>{{ $usaha['nama'] }}</h1><div class="muted">{{ $usaha['alamat'] }}<br>WhatsApp: {{ $usaha['wa'] }}</div></td>
        <td class="r"><strong>LEMBAR KESEPAKATAN</strong><br>No. {{ $booking->kode_booking }}<br>Dicetak {{ tanggal(now(), 'j F Y') }}</td>
    </tr></table>

    <h2>Identitas Klien</h2>
    <table>
        <tr><td width="30%">Nama Pengantin</td><td>{{ $booking->client->namaPasangan() }}</td></tr>
        <tr><td>Nama Kontak</td><td>{{ $booking->client->nama_kontak }} ({{ $booking->client->phone }})</td></tr>
        <tr><td>Alamat</td><td>{{ $booking->client->alamat }} {{ $booking->client->kota }}</td></tr>
        <tr><td>Tanggal Acara</td><td><strong>{{ tanggal($booking->tanggal_acara, 'l, j F Y') }}</strong> {{ $booking->jam_mulai_acara ? '· '.substr($booking->jam_mulai_acara, 0, 5).' WIB' : '' }} ({{ $booking->jenis_acara->label() }})</td></tr>
        <tr><td>Lokasi</td><td>{{ $booking->location->nama }}, {{ $booking->location->kota }}<br>{{ $booking->alamat_acara }}</td></tr>
    </table>

    <h2>Rincian Paket & Komponen (Harga Terkunci {{ tanggal($booking->price_locked_at, 'j F Y') }})</h2>
    <table class="items">
        <thead><tr><th>Item</th><th class="r">Jumlah</th><th class="r">Harga Satuan</th><th class="r">Subtotal</th></tr></thead>
        <tbody>
            @foreach ($booking->items as $i)
                <tr><td>{{ $i->nama_item }}</td><td class="r">{{ (float) $i->kuantitas }} {{ $i->tipe_harga === \App\Enums\PriceType::PerCm ? 'bentang' : $i->satuan }}</td><td class="r">{{ rupiah($i->harga_satuan_klien) }}{{ $i->tipe_harga === \App\Enums\PriceType::PerCm ? '/cm' : '' }}</td><td class="r">{{ rupiah($i->subtotal_klien) }}</td></tr>
            @endforeach
        </tbody>
        <tfoot><tr><th colspan="3">TOTAL TAGIHAN</th><th class="r">{{ rupiah($booking->total_tagihan) }}</th></tr></tfoot>
    </table>

    <h2>Pembayaran Diterima</h2>
    <table class="items">
        @foreach ($booking->verifiedPayments as $p)
            <tr><td>{{ tanggal($p->tanggal_bayar, 'j F Y') }}</td><td>{{ $p->jenis->label() }} ({{ $p->metode->label() }})</td><td class="r">{{ rupiah($p->nominal) }}</td></tr>
        @endforeach
        <tr><th colspan="2">Total dibayar ({{ number_format($booking->persentaseDibayar(), 1, ',', '.') }}%)</th><th class="r">{{ rupiah($booking->total_dibayar) }}</th></tr>
        <tr><th colspan="2">Sisa tagihan</th><th class="r">{{ rupiah($booking->sisaTagihan()) }}</th></tr>
    </table>

    <h2>Ketentuan</h2>
    <div class="box">
        <ol style="margin:0; padding-left:16px;">
            <li><strong>DP fleksibel & dapat dikembalikan:</strong> DP tidak dipatok persentase tertentu. Bila klien membatalkan sebelum hari acara, DP dikembalikan <strong>100%</strong>, dan tanggal serta kunci harga dilepas.</li>
            <li><strong>Penguncian:</strong> Dengan diterimanya DP, tanggal acara terkunci di kalender Yeni Wedding dan harga di atas tidak berubah meskipun harga pasar berubah. Perubahan lingkup dicatat sebagai revisi.</li>
            <li><strong>Syarat pelunasan:</strong> Akumulasi pembayaran wajib mencapai minimal <strong>{{ $booking->ambang_pelunasan_persen }}%</strong> ({{ rupiah(ceil($booking->total_tagihan * $booking->ambang_pelunasan_persen / 100)) }}) paling lambat <strong>H-7, {{ tanggal($booking->batas_pelunasan, 'l, j F Y') }}</strong>. Sisa tagihan dilunasi sebelum acara.</li>
            <li>Pembayaran melalui transfer wajib disertai bukti transfer ke rekening:
                @foreach ($usaha['rekening'] ?? [] as $r) {{ $r['bank'] }} {{ $r['nomor'] }} a.n. {{ $r['atas_nama'] }}{{ $loop->last ? '.' : ';' }} @endforeach
            </li>
        </ol>
    </div>

    <table class="sign" style="margin-top:24px;">
        <tr><td>Klien,<br><br><br>( {{ $booking->client->nama_kontak }} )</td><td>{{ $usaha['nama'] }},<br><br><br>( Owner )</td></tr>
    </table>
</body>
</html>
