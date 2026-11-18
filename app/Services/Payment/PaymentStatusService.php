<?php

namespace App\Services\Payment;

use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentType;
use App\Enums\ScheduleStatus;
use App\Enums\VerificationStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\SystemParameter;
use App\Models\User;
use App\Services\Pricing\PricingCalculatorService;
use App\Support\Rupiah;
use App\Support\Whatsapp;
use Carbon\CarbonInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * AB-01, AB-02, AB-03, AB-04, AB-06 dan siklus status pemesanan otomatis:
 * Penawaran -> Terkonfirmasi -> (H-7) Belum Memenuhi Ambang / Siap Pelaksanaan -> Lunas, atau Batal - Refund.
 */
class PaymentStatusService
{
    private const ACTIVE = [
        BookingStatus::Terkonfirmasi, BookingStatus::BelumMemenuhiAmbang, BookingStatus::SiapPelaksanaan, BookingStatus::Lunas,
    ];

    public function __construct(private readonly PricingCalculatorService $pricing) {}

    /**
     * Catat pembayaran. Dicatat Owner/Admin = langsung terverifikasi; diunggah Klien = menunggu verifikasi.
     *
     * @param  array{nominal: float|int|string, metode: PaymentMethod|string, tanggal_bayar?: mixed, jenis?: PaymentType|string, bank_pengirim?: string, nama_pengirim?: string, bukti_transfer_path?: string, catatan?: string}  $data
     */
    public function recordPayment(Booking $booking, array $data, User $by, ?UploadedFile $bukti = null): Payment
    {
        $nominal = round((float) ($data['nominal'] ?? 0), 2);
        $metode = $data['metode'] instanceof PaymentMethod ? $data['metode'] : PaymentMethod::from($data['metode']);

        if ($nominal <= 0) {
            throw new BusinessRuleException('Nominal pembayaran harus lebih dari Rp0.', 'AB-01');
        }
        if ($booking->status === BookingStatus::BatalRefund) {
            throw new BusinessRuleException('Pesanan sudah dibatalkan; pembayaran tidak dapat dicatat.', 'AB-03');
        }
        if ($booking->status === BookingStatus::Lunas) {
            throw new BusinessRuleException('Pesanan sudah lunas.', 'AB-06');
        }
        if ($metode === PaymentMethod::Transfer && ! $bukti && empty($data['bukti_transfer_path'])) {
            throw new BusinessRuleException('Bukti transfer wajib diunggah untuk pembayaran transfer.', 'AB-06');
        }

        $tercatat = (float) $booking->payments()->where('status_verifikasi', '!=', VerificationStatus::Ditolak->value)->sum('nominal');
        $sisa = round((float) $booking->total_tagihan - $tercatat, 2);
        if ($nominal > $sisa) {
            throw new BusinessRuleException('Nominal melebihi sisa tagihan ('.Rupiah::format($sisa).').', 'AB-06');
        }

        $isStaff = $by->isOwner();

        return DB::transaction(function () use ($booking, $data, $by, $bukti, $nominal, $metode, $isStaff, $sisa) {
            $path = $bukti?->store("bukti-transfer/{$booking->kode_booking}", 'local') ?? ($data['bukti_transfer_path'] ?? null);
            $jenis = isset($data['jenis'])
                ? ($data['jenis'] instanceof PaymentType ? $data['jenis'] : PaymentType::from($data['jenis']))
                : match (true) {
                    ! $booking->verifiedPayments()->exists() && ! $booking->is_date_locked => PaymentType::Dp,
                    $nominal >= $sisa => PaymentType::Pelunasan,
                    default => PaymentType::Cicilan,
                };

            $payment = $booking->payments()->create([
                'jenis' => $jenis,
                'nominal' => $nominal,
                'tanggal_bayar' => Carbon::parse($data['tanggal_bayar'] ?? now())->toDateString(),
                'metode' => $metode,
                'bank_pengirim' => $data['bank_pengirim'] ?? null,
                'nama_pengirim' => $data['nama_pengirim'] ?? null,
                'bukti_transfer_path' => $metode === PaymentMethod::Transfer ? $path : null,
                'penerima_id' => $isStaff ? $by->id : null,
                'diunggah_oleh' => $by->id,
                'status_verifikasi' => $isStaff ? VerificationStatus::Terverifikasi : VerificationStatus::Menunggu,
                'diverifikasi_at' => $isStaff ? now() : null,
                'catatan' => $data['catatan'] ?? null,
            ]);

            if ($isStaff) {
                $this->syncStatus($booking, null, $by);
            }

            return $payment;
        });
    }

    public function verifyPayment(Payment $payment, User $by, bool $approve, ?string $catatan = null): Payment
    {
        if (! $by->isOwner()) {
            throw new BusinessRuleException('Hanya Owner/Admin yang dapat memverifikasi pembayaran.', 'AB-06');
        }
        if ($payment->status_verifikasi !== VerificationStatus::Menunggu) {
            throw new BusinessRuleException('Pembayaran ini sudah diproses.', 'AB-06');
        }

        return DB::transaction(function () use ($payment, $by, $approve, $catatan) {
            $payment->update([
                'status_verifikasi' => $approve ? VerificationStatus::Terverifikasi : VerificationStatus::Ditolak,
                'penerima_id' => $by->id,
                'diverifikasi_at' => now(),
                'catatan' => $catatan ?? $payment->catatan,
            ]);

            if ($approve) {
                $this->syncStatus($payment->booking, null, $by);
            }

            return $payment;
        });
    }

    /** Evaluasi ulang status pesanan berdasarkan pembayaran terverifikasi dan tanggal hari ini. */
    public function syncStatus(Booking $booking, ?CarbonInterface $today = null, ?User $by = null): Booking
    {
        $today = Carbon::instance($today ?? now())->startOfDay();
        $old = $booking->status;

        $booking->total_dibayar = (float) $booking->verifiedPayments()->sum('nominal');

        if ($booking->status === BookingStatus::BatalRefund) {
            $booking->save();

            return $booking;
        }

        $lewatBatas = $today->gte($booking->batas_pelunasan);

        if ($booking->status === BookingStatus::Penawaran) {
            if ((float) $booking->total_dibayar <= 0) {
                $booking->save();

                return $booking;
            }

            $this->pricing->lockPrices($booking);
            $booking->refresh();
            $booking->total_dibayar = (float) $booking->verifiedPayments()->sum('nominal');

            // AB-04: pemesanan pada <= H-7 wajib langsung >= ambang untuk terkonfirmasi.
            if ($lewatBatas && $booking->persentaseDibayar() < $booking->ambang_pelunasan_persen) {
                $this->pricing->unlockPrices($booking);
                $booking->save();

                return $booking;
            }

            $booking->forceFill([
                'status' => BookingStatus::Terkonfirmasi,
                'is_date_locked' => true,
                'date_locked_at' => now(),
                'dikonfirmasi_at' => now(),
            ]);
        }

        if (in_array($booking->status, self::ACTIVE, true)) {
            $persen = $booking->persentaseDibayar();

            if ($persen >= 100) {
                $booking->status = BookingStatus::Lunas;
                $booking->siap_pelaksanaan_at ??= now();
                $booking->lunas_at ??= now();
            } elseif ($lewatBatas) {
                $booking->ambang_dicek_at = now();
                if ($persen >= $booking->ambang_pelunasan_persen) {
                    $booking->status = BookingStatus::SiapPelaksanaan;
                    $booking->siap_pelaksanaan_at ??= now();
                } else {
                    $booking->status = BookingStatus::BelumMemenuhiAmbang;
                }
                $booking->lunas_at = null;
            } else {
                $booking->status = BookingStatus::Terkonfirmasi;
                $booking->lunas_at = null;
            }
        }

        $booking->save();

        if ($old !== $booking->status) {
            AuditLog::record('booking.status', $booking, ['status' => $old->value], [
                'status' => $booking->status->value,
                'total_dibayar' => (float) $booking->total_dibayar,
                'persen_dibayar' => $booking->persentaseDibayar(),
            ], "Status berubah: {$old->label()} → {$booking->status->label()}", $by?->id);
        }

        return $booking;
    }

    /**
     * Pengecekan harian H-7 (dijalankan scheduler). Tidak membatalkan otomatis; keputusan di tangan Owner.
     *
     * @return Collection<int, Booking> pesanan yang Belum Memenuhi Ambang
     */
    public function evaluateThresholds(?CarbonInterface $today = null): Collection
    {
        $today = Carbon::instance($today ?? now())->startOfDay();

        return Booking::with('client')
            ->whereIn('status', [BookingStatus::Terkonfirmasi->value, BookingStatus::BelumMemenuhiAmbang->value, BookingStatus::SiapPelaksanaan->value])
            ->whereDate('batas_pelunasan', '<=', $today->toDateString())
            ->whereDate('tanggal_acara', '>=', $today->toDateString())
            ->get()
            ->map(fn (Booking $b) => $this->syncStatus($b, $today))
            ->filter(fn (Booking $b) => $b->status === BookingStatus::BelumMemenuhiAmbang)
            ->values();
    }

    public function thresholdAmount(Booking $booking): float
    {
        return ceil((float) $booking->total_tagihan * $booking->ambang_pelunasan_persen / 100);
    }

    public function shortfallToThreshold(Booking $booking): float
    {
        return max(0, $this->thresholdAmount($booking) - (float) $booking->total_dibayar);
    }

    public function reminderMessage(Booking $booking): string
    {
        $booking->loadMissing('client');
        $template = (string) SystemParameter::get('template_pengingat_wa', '');
        $tgl = fn (CarbonInterface $d) => $d->copy()->locale('id')->translatedFormat('l, j F Y');

        return strtr($template, [
            '{kode_booking}' => $booking->kode_booking,
            '{nama_kontak}' => $booking->client->nama_kontak,
            '{nama_pasangan}' => $booking->client->namaPasangan(),
            '{tanggal_acara}' => $tgl($booking->tanggal_acara),
            '{batas_pelunasan}' => $tgl($booking->batas_pelunasan),
            '{total_tagihan}' => Rupiah::format($booking->total_tagihan),
            '{total_dibayar}' => Rupiah::format($booking->total_dibayar),
            '{sisa_tagihan}' => Rupiah::format($booking->sisaTagihan()),
            '{persen_dibayar}' => number_format($booking->persentaseDibayar(), 1, ',', '.'),
            '{ambang}' => (string) $booking->ambang_pelunasan_persen,
            '{kekurangan_ambang}' => Rupiah::format($this->shortfallToThreshold($booking)),
        ]);
    }

    /** Tautan wa.me siap kirim berisi rincian kekurangan pembayaran (AB-04). */
    public function reminderLink(Booking $booking): string
    {
        $booking->loadMissing('client');

        return Whatsapp::link($booking->client->phone, $this->reminderMessage($booking));
    }

    /**
     * AB-03: batal oleh klien -> refund 100% dana masuk, lepas tanggal & harga, batalkan penugasan.
     *
     * @param  array{metode?: PaymentMethod|string, tanggal_refund?: mixed, alasan?: string, bank_tujuan?: string, nomor_rekening_tujuan?: string, atas_nama?: string, bukti_refund_path?: string}  $data
     */
    public function cancel(Booking $booking, array $data, User $by, ?UploadedFile $bukti = null): ?Refund
    {
        if (! $by->isOwner()) {
            throw new BusinessRuleException('Hanya Owner/Admin yang dapat memproses pembatalan.', 'AB-03');
        }
        if ($booking->status === BookingStatus::BatalRefund) {
            throw new BusinessRuleException('Pesanan sudah dibatalkan.', 'AB-03');
        }
        if (Carbon::today()->gte($booking->tanggal_acara)) {
            throw new BusinessRuleException('Pembatalan hanya dapat dilakukan sebelum hari acara.', 'AB-03');
        }

        $dibayar = (float) $booking->verifiedPayments()->sum('nominal');
        $nominal = round($dibayar * (int) SystemParameter::get('persen_refund_dp', 100) / 100, 2);
        $metode = null;

        if ($nominal > 0) {
            if (empty($data['metode'])) {
                throw new BusinessRuleException('Metode refund wajib diisi.', 'AB-03');
            }
            $metode = $data['metode'] instanceof PaymentMethod ? $data['metode'] : PaymentMethod::from($data['metode']);
            if ($metode === PaymentMethod::Transfer && ! $bukti && empty($data['bukti_refund_path'])) {
                throw new BusinessRuleException('Bukti refund wajib diunggah untuk refund transfer.', 'AB-03');
            }
        }

        return DB::transaction(function () use ($booking, $data, $by, $bukti, $nominal, $metode) {
            $old = $booking->status;
            $refund = null;

            if ($nominal > 0) {
                $refund = $booking->refunds()->create([
                    'nominal' => $nominal,
                    'tanggal_refund' => Carbon::parse($data['tanggal_refund'] ?? now())->toDateString(),
                    'metode' => $metode,
                    'bank_tujuan' => $data['bank_tujuan'] ?? null,
                    'nomor_rekening_tujuan' => $data['nomor_rekening_tujuan'] ?? null,
                    'atas_nama' => $data['atas_nama'] ?? null,
                    'bukti_refund_path' => $bukti?->store("bukti-refund/{$booking->kode_booking}", 'local') ?? ($data['bukti_refund_path'] ?? null),
                    'alasan' => $data['alasan'] ?? null,
                    'diproses_oleh' => $by->id,
                ]);
            }

            $booking->payments()->where('status_verifikasi', VerificationStatus::Menunggu->value)
                ->update(['status_verifikasi' => VerificationStatus::Ditolak->value, 'catatan' => 'Pesanan dibatalkan.']);
            $booking->schedules()->where('status', ScheduleStatus::Dijadwalkan->value)
                ->update(['status' => ScheduleStatus::Dibatalkan->value]);
            $booking->inventories()->where('is_keluar', false)->delete();

            $this->pricing->unlockPrices($booking);
            $booking->forceFill([
                'status' => BookingStatus::BatalRefund,
                'total_refund' => $nominal,
                'is_date_locked' => false,
                'dibatalkan_at' => now(),
                'alasan_batal' => $data['alasan'] ?? null,
            ])->save();

            AuditLog::record('booking.status', $booking, ['status' => $old->value], [
                'status' => BookingStatus::BatalRefund->value,
                'refund' => $nominal,
            ], 'Dibatalkan klien. Refund '.Rupiah::format($nominal).'; tanggal & harga dilepas.', $by->id);

            return $refund;
        });
    }
}
