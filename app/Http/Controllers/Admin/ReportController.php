<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemParameter;
use App\Services\Report\FinancialReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** AB-09: hanya Owner (dijaga middleware role:owner). */
class ReportController extends Controller
{
    public function __construct(private readonly FinancialReportService $reports) {}

    public function index(Request $request): View
    {
        $bulan = $this->bulan($request);

        return view('admin.reports', ['r' => $this->reports->monthly($bulan), 'tahunan' => $this->reports->yearly($bulan->year)]);
    }

    public function pdf(Request $request): Response
    {
        $bulan = $this->bulan($request);

        return Pdf::loadView('pdf.laporan', ['r' => $this->reports->monthly($bulan), 'usaha' => SystemParameter::get('nama_usaha', 'Yeni Wedding')])
            ->setPaper('a4')->download('Laporan-Keuangan-'.$bulan->format('Y-m').'.pdf');
    }

    /** Ekspor CSV (dibuka langsung di Excel; pemisah titik koma sesuai format regional Indonesia). */
    public function excel(Request $request): StreamedResponse
    {
        $r = $this->reports->monthly($this->bulan($request));

        return response()->streamDownload(function () use ($r) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            $row = fn (array $cols) => fputcsv($out, $cols, ';');

            $row(['Laporan Keuangan Yeni Wedding', tanggal($r['bulan'], 'F Y')]);
            $row([]);
            foreach (['DP Masuk' => 'dp_masuk', 'Pelunasan/Cicilan' => 'pelunasan', 'Refund DP' => 'refund', 'Kas Bersih' => 'kas_bersih',
                'Pendapatan Acara' => 'pendapatan_acara', 'Biaya Vendor' => 'biaya_vendor', 'Margin Vendor' => 'margin_vendor', 'Margin Bersih' => 'laba_bersih'] as $label => $key) {
                $row([$label, $r[$key]]);
            }
            $row([]);
            $row(['PEMBAYARAN MASUK']);
            $row(['Tanggal', 'Kode', 'Klien', 'Jenis', 'Metode', 'Nominal']);
            foreach ($r['payments'] as $p) {
                $row([$p->tanggal_bayar->toDateString(), $p->booking->kode_booking, $p->booking->client->namaPasangan(), $p->jenis->label(), $p->metode->label(), (float) $p->nominal]);
            }
            $row([]);
            $row(['REFUND']);
            $row(['Tanggal', 'Kode', 'Klien', 'Metode', 'Nominal']);
            foreach ($r['refunds'] as $f) {
                $row([$f->tanggal_refund->toDateString(), $f->booking->kode_booking, $f->booking->client->namaPasangan(), $f->metode?->label(), (float) $f->nominal]);
            }
            $row([]);
            $row(['ACARA BULAN INI']);
            $row(['Tanggal', 'Kode', 'Klien', 'Status', 'Tagihan', 'Biaya Vendor', 'Margin Vendor']);
            foreach ($r['events'] as $b) {
                $row([$b->tanggal_acara->toDateString(), $b->kode_booking, $b->client->namaPasangan(), $b->status->label(), (float) $b->total_tagihan, (float) $b->total_biaya_vendor, (float) $b->total_margin]);
            }
            fclose($out);
        }, 'Laporan-Keuangan-'.$r['bulan']->format('Y-m').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function bulan(Request $request): Carbon
    {
        $request->validate(['bulan' => ['nullable', 'date_format:Y-m']]);

        return Carbon::createFromFormat('Y-m-d', ($request->input('bulan') ?? now()->format('Y-m')).'-01')->startOfDay();
    }
}
