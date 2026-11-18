<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #222; }
    h1 { font-size: 16px; margin: 0; color: #8a2b3a; } h2 { font-size: 12px; margin: 14px 0 5px; }
    table { width: 100%; border-collapse: collapse; } td, th { padding: 3px 5px; border: 1px solid #ddd; text-align: left; }
    th { background: #f3eded; } .r { text-align: right; } .sum td { font-size: 11px; }
</style>
</head>
<body>
    <h1>{{ $usaha }} — Laporan Keuangan {{ tanggal($r['bulan'], 'F Y') }}</h1>
    <p>Dicetak {{ tanggal(now(), 'j F Y H:i') }} · RAHASIA — hanya untuk Owner</p>

    <table class="sum">
        <tr><td>DP masuk</td><td class="r">{{ rupiah($r['dp_masuk']) }}</td><td>Pendapatan acara ({{ $r['jumlah_acara'] }} acara)</td><td class="r">{{ rupiah($r['pendapatan_acara']) }}</td></tr>
        <tr><td>Pelunasan & cicilan</td><td class="r">{{ rupiah($r['pelunasan']) }}</td><td>Biaya vendor</td><td class="r">{{ rupiah($r['biaya_vendor']) }}</td></tr>
        <tr><td>Refund DP</td><td class="r">{{ rupiah($r['refund']) }}</td><td>Margin vendor</td><td class="r">{{ rupiah($r['margin_vendor']) }}</td></tr>
        <tr><th>Kas masuk bersih</th><th class="r">{{ rupiah($r['kas_bersih']) }}</th><th>Margin bersih</th><th class="r">{{ rupiah($r['laba_bersih']) }}</th></tr>
    </table>

    <h2>Pembayaran Masuk</h2>
    <table>
        <tr><th>Tanggal</th><th>Kode</th><th>Klien</th><th>Jenis</th><th>Metode</th><th class="r">Nominal</th></tr>
        @forelse ($r['payments'] as $p)
            <tr><td>{{ $p->tanggal_bayar->format('d/m/Y') }}</td><td>{{ $p->booking->kode_booking }}</td><td>{{ $p->booking->client->namaPasangan() }}</td><td>{{ $p->jenis->label() }}</td><td>{{ $p->metode->label() }}</td><td class="r">{{ rupiah($p->nominal) }}</td></tr>
        @empty
            <tr><td colspan="6">Tidak ada.</td></tr>
        @endforelse
    </table>

    <h2>Refund DP</h2>
    <table>
        <tr><th>Tanggal</th><th>Kode</th><th>Klien</th><th>Metode</th><th class="r">Nominal</th></tr>
        @forelse ($r['refunds'] as $f)
            <tr><td>{{ $f->tanggal_refund->format('d/m/Y') }}</td><td>{{ $f->booking->kode_booking }}</td><td>{{ $f->booking->client->namaPasangan() }}</td><td>{{ $f->metode?->label() }}</td><td class="r">{{ rupiah($f->nominal) }}</td></tr>
        @empty
            <tr><td colspan="5">Tidak ada.</td></tr>
        @endforelse
    </table>

    <h2>Acara Bulan Ini</h2>
    <table>
        <tr><th>Tanggal</th><th>Kode</th><th>Klien</th><th>Status</th><th class="r">Tagihan</th><th class="r">Biaya Vendor</th><th class="r">Margin Vendor</th></tr>
        @forelse ($r['events'] as $b)
            <tr><td>{{ $b->tanggal_acara->format('d/m/Y') }}</td><td>{{ $b->kode_booking }}</td><td>{{ $b->client->namaPasangan() }}</td><td>{{ $b->status->label() }}</td><td class="r">{{ rupiah($b->total_tagihan) }}</td><td class="r">{{ rupiah($b->total_biaya_vendor) }}</td><td class="r">{{ rupiah($b->total_margin) }}</td></tr>
        @empty
            <tr><td colspan="7">Tidak ada.</td></tr>
        @endforelse
    </table>
</body>
</html>
