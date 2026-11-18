<?php

namespace App\Services\Pricing;

use App\Enums\PriceType;
use App\Enums\SnapshotItemType;
use App\Exceptions\BusinessRuleException;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\BookingItemSnapshot;
use App\Models\DynamicComponent;
use App\Models\Package;
use App\Models\User;
use App\Models\VendorService;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * AB-05 & AB-08: kalkulasi harga paket dasar berversi + komponen berfluktuasi,
 * dual-pricing vendor (margin), snapshot price-lock, dan revisi lingkup berjejak.
 *
 * Format input komponen: [['component_id' => int, 'kuantitas' => float, 'ukuran_cm' => ?int], ...]
 * Format input vendor:   [['vendor_service_id' => int, 'kuantitas' => float], ...]
 */
class PricingCalculatorService
{
    public function componentSubtotal(DynamicComponent $c, float $kuantitas, ?int $ukuranCm = null, ?float $hargaSatuan = null): float
    {
        if ($kuantitas <= 0) {
            throw new BusinessRuleException("Kuantitas {$c->nama} harus lebih dari 0.", 'AB-05');
        }

        $harga = $hargaSatuan ?? (float) $c->harga_satuan;

        if ($c->tipe_harga !== PriceType::PerCm) {
            return round($harga * $kuantitas, 2);
        }

        if (! $ukuranCm || $ukuranCm <= 0) {
            throw new BusinessRuleException("Ukuran (cm) wajib diisi untuk {$c->nama}.", 'AB-05');
        }
        if (($c->ukuran_min_cm && $ukuranCm < $c->ukuran_min_cm) || ($c->ukuran_max_cm && $ukuranCm > $c->ukuran_max_cm)) {
            throw new BusinessRuleException("Ukuran {$c->nama} harus antara {$c->ukuran_min_cm}–{$c->ukuran_max_cm} cm.", 'AB-05');
        }

        return round($harga * $ukuranCm * $kuantitas, 2);
    }

    public function quote(?Package $package, array $komponen = [], array $vendor = []): PriceQuote
    {
        $lines = [];

        if ($package) {
            $this->ensureActive($package->is_active, "Paket {$package->nama} versi {$package->versi} sudah tidak berlaku.");
            $lines[] = $this->packageLine($package);
        }

        foreach ($komponen as $row) {
            $c = DynamicComponent::findOrFail($row['component_id']);
            $this->ensureActive($c->is_active, "Komponen {$c->nama} sedang tidak tersedia.");
            $lines[] = $this->componentLine($c, (float) $row['kuantitas'], isset($row['ukuran_cm']) ? (int) $row['ukuran_cm'] : null);
        }

        foreach ($vendor as $row) {
            $s = VendorService::findOrFail($row['vendor_service_id']);
            $this->ensureActive($s->is_active, "Layanan {$s->nama_layanan} sedang tidak tersedia.");
            $lines[] = $this->vendorLine($s, (float) $row['kuantitas']);
        }

        return new PriceQuote($lines);
    }

    /** Ganti seluruh rincian booking yang belum terkunci dengan penawaran baru. */
    public function applyQuote(Booking $booking, PriceQuote $quote): Booking
    {
        $this->ensureNotLocked($booking);

        return DB::transaction(function () use ($booking, $quote) {
            $booking->items()->delete();
            $booking->items()->createMany($quote->lines);

            return $this->recalculateTotals($booking);
        });
    }

    /** Hitung ulang rincian booking tentatif dari harga master terkini (paket memakai versi aktif terbaru). */
    public function refreshFromMaster(Booking $booking): Booking
    {
        $this->ensureNotLocked($booking);

        return DB::transaction(function () use ($booking) {
            $lines = [];
            foreach ($booking->items()->where('item_type', '!=', SnapshotItemType::Tambahan->value)->get() as $item) {
                $lines[] = match ($item->item_type) {
                    SnapshotItemType::Paket => $this->packageLine($this->currentVersion(Package::findOrFail($item->source_id))),
                    SnapshotItemType::KomponenDinamis => $this->componentLine(DynamicComponent::findOrFail($item->source_id), (float) $item->kuantitas, $item->ukuran_cm),
                    SnapshotItemType::LayananVendor => $this->vendorLine(VendorService::findOrFail($item->source_id), (float) $item->kuantitas),
                };
            }

            $paket = collect($lines)->firstWhere('item_type', SnapshotItemType::Paket);
            if ($paket) {
                $booking->package_id = $paket['source_id'];
            }

            $booking->items()->where('item_type', '!=', SnapshotItemType::Tambahan->value)->delete();
            $booking->items()->createMany($lines);

            return $this->recalculateTotals($booking);
        });
    }

    /** AB-05: kunci snapshot harga saat DP pertama diterima. Harga master terkini disalin lebih dulu. */
    public function lockPrices(Booking $booking, ?CarbonInterface $at = null): Booking
    {
        if ($booking->is_price_locked) {
            return $booking;
        }

        $at ??= now();

        return DB::transaction(function () use ($booking, $at) {
            $this->refreshFromMaster($booking);
            $booking->items()->update(['is_locked' => true, 'locked_at' => $at]);
            $booking->forceFill(['is_price_locked' => true, 'price_locked_at' => $at])->save();

            return $booking;
        });
    }

    /** AB-03: lepas kunci harga saat pesanan dibatalkan. */
    public function unlockPrices(Booking $booking): Booking
    {
        $booking->items()->update(['is_locked' => false]);
        $booking->forceFill(['is_price_locked' => false])->save();

        return $booking;
    }

    /**
     * Revisi lingkup oleh klien. Pada booking terkunci, item lama tetap memakai harga satuan snapshot,
     * item baru memakai harga master saat ini. Semua perubahan dicatat di audit_logs.
     */
    public function reviseScope(Booking $booking, array $komponen, array $vendor, User $by, string $alasan): Booking
    {
        if (trim($alasan) === '') {
            throw new BusinessRuleException('Alasan revisi wajib diisi.', 'AB-05');
        }

        return DB::transaction(function () use ($booking, $komponen, $vendor, $by, $alasan) {
            $before = $this->summary($booking);
            $revisi = $booking->revisi_ke + 1;

            if (! $booking->is_price_locked) {
                $paket = $booking->items()->where('item_type', SnapshotItemType::Paket->value)->first();
                $this->applyQuote($booking, $this->quote($paket ? Package::find($paket->source_id) : null, $komponen, $vendor));
            } else {
                $this->reviseLocked($booking, $komponen, $vendor, $revisi);
            }

            $booking->forceFill(['revisi_ke' => $revisi])->save();
            AuditLog::record('booking.revisi', $booking, $before, $this->summary($booking), $alasan, $by->id);

            return $booking->refresh();
        });
    }

    public function recalculateTotals(Booking $booking): Booking
    {
        $booking->forceFill([
            'total_tagihan' => $booking->items()->sum('subtotal_klien'),
            'total_biaya_vendor' => $booking->items()->sum('subtotal_vendor'),
            'total_margin' => $booking->items()->sum('margin'),
        ])->save();

        return $booking;
    }

    public function packageLine(Package $p): array
    {
        return [
            'item_type' => SnapshotItemType::Paket,
            'source_type' => $p->getMorphClass(),
            'source_id' => $p->id,
            'nama_item' => "{$p->nama} (v{$p->versi})",
            'tipe_harga' => PriceType::Flat,
            'kuantitas' => 1,
            'satuan' => 'paket',
            'ukuran_cm' => null,
            'harga_satuan_klien' => (float) $p->harga_dasar,
            'tarif_vendor_satuan' => null,
            'subtotal_klien' => (float) $p->harga_dasar,
            'subtotal_vendor' => 0,
            'margin' => 0,
            'versi_paket' => $p->versi,
            'snapshot_data' => $p->only(['kode', 'versi', 'nama', 'harga_dasar', 'rincian']),
        ];
    }

    public function componentLine(DynamicComponent $c, float $kuantitas, ?int $ukuranCm = null, ?float $hargaSatuan = null): array
    {
        $harga = $hargaSatuan ?? (float) $c->harga_satuan;

        return [
            'item_type' => SnapshotItemType::KomponenDinamis,
            'source_type' => $c->getMorphClass(),
            'source_id' => $c->id,
            'nama_item' => $ukuranCm && $c->tipe_harga === PriceType::PerCm ? "{$c->nama} ({$ukuranCm} cm)" : $c->nama,
            'tipe_harga' => $c->tipe_harga,
            'kuantitas' => $kuantitas,
            'satuan' => $c->satuan,
            'ukuran_cm' => $c->tipe_harga === PriceType::PerCm ? $ukuranCm : null,
            'harga_satuan_klien' => $harga,
            'tarif_vendor_satuan' => null,
            'subtotal_klien' => $this->componentSubtotal($c, $kuantitas, $ukuranCm, $harga),
            'subtotal_vendor' => 0,
            'margin' => 0,
            'versi_paket' => null,
            'snapshot_data' => ['kode' => $c->kode, 'harga_satuan' => $harga, 'tipe_harga' => $c->tipe_harga->value],
        ];
    }

    /** AB-08: margin = (tarif_klien - tarif_vendor) x kuantitas. */
    public function vendorLine(VendorService $s, float $kuantitas, ?float $tarifKlien = null, ?float $tarifVendor = null): array
    {
        if ($kuantitas <= 0) {
            throw new BusinessRuleException("Kuantitas {$s->nama_layanan} harus lebih dari 0.", 'AB-08');
        }

        $klien = $tarifKlien ?? (float) $s->tarif_klien;
        $vendor = $tarifVendor ?? (float) $s->tarif_vendor;
        $subKlien = round($klien * $kuantitas, 2);
        $subVendor = round($vendor * $kuantitas, 2);

        return [
            'item_type' => SnapshotItemType::LayananVendor,
            'source_type' => $s->getMorphClass(),
            'source_id' => $s->id,
            'nama_item' => $s->nama_layanan,
            'tipe_harga' => PriceType::PerUnit,
            'kuantitas' => $kuantitas,
            'satuan' => $s->satuan,
            'ukuran_cm' => null,
            'harga_satuan_klien' => $klien,
            'tarif_vendor_satuan' => $vendor,
            'subtotal_klien' => $subKlien,
            'subtotal_vendor' => $subVendor,
            'margin' => round($subKlien - $subVendor, 2),
            'versi_paket' => null,
            'snapshot_data' => ['vendor_user_id' => $s->vendor_user_id, 'tarif_klien' => $klien, 'tarif_vendor' => $vendor],
        ];
    }

    private function reviseLocked(Booking $booking, array $komponen, array $vendor, int $revisi): void
    {
        $existing = $booking->items()->get()->keyBy(fn (BookingItemSnapshot $i) => "{$i->item_type->value}:{$i->source_id}");
        $keep = [];
        $now = now();

        $desired = [];
        foreach ($komponen as $row) {
            $c = DynamicComponent::findOrFail($row['component_id']);
            $key = SnapshotItemType::KomponenDinamis->value.":{$c->id}";
            $old = $existing->get($key);
            $desired[$key] = $old
                ? $this->componentLine($c, (float) $row['kuantitas'], $row['ukuran_cm'] ?? null, (float) $old->harga_satuan_klien)
                : $this->componentLine($c, (float) $row['kuantitas'], $row['ukuran_cm'] ?? null);
        }
        foreach ($vendor as $row) {
            $s = VendorService::findOrFail($row['vendor_service_id']);
            $key = SnapshotItemType::LayananVendor->value.":{$s->id}";
            $old = $existing->get($key);
            $desired[$key] = $old
                ? $this->vendorLine($s, (float) $row['kuantitas'], (float) $old->harga_satuan_klien, (float) $old->tarif_vendor_satuan)
                : $this->vendorLine($s, (float) $row['kuantitas']);
        }

        foreach ($desired as $key => $line) {
            $keep[] = $key;
            $old = $existing->get($key);

            if (! $old) {
                $booking->items()->create($line + ['is_locked' => true, 'locked_at' => $now, 'revisi_ke' => $revisi]);

                continue;
            }

            $changed = (float) $old->kuantitas !== (float) $line['kuantitas'] || $old->ukuran_cm !== $line['ukuran_cm'];
            if ($changed) {
                $old->update([
                    'kuantitas' => $line['kuantitas'],
                    'ukuran_cm' => $line['ukuran_cm'],
                    'nama_item' => $line['nama_item'],
                    'subtotal_klien' => $line['subtotal_klien'],
                    'subtotal_vendor' => $line['subtotal_vendor'],
                    'margin' => $line['margin'],
                    'revisi_ke' => $revisi,
                ]);
            }
        }

        $existing->each(function (BookingItemSnapshot $item, string $key) use ($keep) {
            if (! in_array($item->item_type, [SnapshotItemType::Paket, SnapshotItemType::Tambahan], true) && ! in_array($key, $keep, true)) {
                $item->delete();
            }
        });

        $this->recalculateTotals($booking);
    }

    private function summary(Booking $booking): array
    {
        return [
            'total_tagihan' => (float) $booking->fresh()->total_tagihan,
            'items' => $booking->items()->get()->map(fn (BookingItemSnapshot $i) => [
                'nama_item' => $i->nama_item,
                'kuantitas' => (float) $i->kuantitas,
                'ukuran_cm' => $i->ukuran_cm,
                'harga_satuan_klien' => (float) $i->harga_satuan_klien,
                'subtotal_klien' => (float) $i->subtotal_klien,
            ])->all(),
        ];
    }

    private function currentVersion(Package $package): Package
    {
        return Package::active()->where('kode', $package->kode)->orderByDesc('versi')->first() ?? $package;
    }

    private function ensureNotLocked(Booking $booking): void
    {
        if ($booking->is_price_locked) {
            throw new BusinessRuleException('Harga pesanan sudah terkunci. Gunakan revisi lingkup untuk mengubah rincian.', 'AB-05');
        }
    }

    private function ensureActive(bool $active, string $message): void
    {
        if (! $active) {
            throw new BusinessRuleException($message, 'AB-05');
        }
    }
}
