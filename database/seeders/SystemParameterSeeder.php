<?php

namespace Database\Seeders;

use App\Models\SystemParameter;
use Illuminate\Database\Seeder;

class SystemParameterSeeder extends Seeder
{
    public function run(): void
    {
        $params = [
            ['nama_usaha', 'Yeni Wedding', 'string', 'umum', 'Nama Usaha', null],
            ['alamat_usaha', 'Jl. Sutisna Senjaya No. 45, Cihideung, Kota Tasikmalaya, Jawa Barat 46121', 'string', 'umum', 'Alamat Usaha', null],
            ['whatsapp_admin', '6281223456789', 'string', 'umum', 'Nomor WhatsApp Admin', 'Format 62xxx tanpa tanda +'],
            ['rekening_pembayaran', json_encode([
                ['bank' => 'BCA', 'nomor' => '1230456789', 'atas_nama' => 'Yeni Rahmawati'],
                ['bank' => 'BRI', 'nomor' => '0123-01-000456-50-7', 'atas_nama' => 'Yeni Rahmawati'],
            ]), 'json', 'pembayaran', 'Rekening Pembayaran', 'Ditampilkan di portal klien & Lembar Kesepakatan'],
            ['minimal_dp', '1', 'decimal', 'pembayaran', 'Minimal DP (Rp)', 'AB-01: DP fleksibel, cukup > 0'],
            ['ambang_pelunasan_persen', '80', 'integer', 'pembayaran', 'Ambang Pelunasan (%)', 'AB-04: minimal pembayaran pada H-7'],
            ['batas_hari_pelunasan', '7', 'integer', 'pembayaran', 'Batas Hari Pelunasan (H-)', 'AB-04: H-7 sebelum acara'],
            ['persen_refund_dp', '100', 'integer', 'pembayaran', 'Persentase Refund DP (%)', 'AB-03: refund DP penuh'],
            ['kapasitas_kru_dekorasi', '5', 'integer', 'penjadwalan', 'Kapasitas Kru Dekorasi Internal per Hari', 'Dapat ditambah tenaga lepas'],
            ['waktu_persiapan_default_menit', '30', 'integer', 'penjadwalan', 'Waktu Persiapan Default (menit)', 'Dipakai bila pasangan lokasi belum diatur'],
            ['waktu_tempuh_default_menit', '60', 'integer', 'penjadwalan', 'Waktu Tempuh Default (menit)', 'Dipakai bila pasangan lokasi belum diatur'],
            ['waktu_tempuh_lokasi_sama_menit', '15', 'integer', 'penjadwalan', 'Jeda di Lokasi yang Sama (menit)', null],
            ['template_pengingat_wa', "Assalamu'alaikum {nama_kontak}, kami dari Yeni Wedding mengingatkan acara {nama_pasangan} pada {tanggal_acara}. Total tagihan {total_tagihan}, sudah dibayar {total_dibayar} ({persen_dibayar}%). Mohon melunasi minimal {ambang}% atau kekurangan {kekurangan_ambang} paling lambat {batas_pelunasan}. Terima kasih.", 'string', 'notifikasi', 'Template Pesan Pengingat WhatsApp', 'Placeholder dalam kurung kurawal'],
        ];

        foreach ($params as [$key, $value, $tipe, $grup, $label, $deskripsi]) {
            SystemParameter::updateOrCreate(['key' => $key], compact('value', 'tipe', 'grup', 'label', 'deskripsi'));
        }
    }
}
