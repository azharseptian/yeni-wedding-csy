<?php

namespace App\Models;

use App\Enums\PriceType;
use App\Enums\SnapshotItemType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class BookingItemSnapshot extends Model
{
    protected $table = 'booking_items_snapshot';

    protected $guarded = ['id'];

    /** AB-08: biaya vendor & margin disembunyikan dari klien/tim internal. */
    protected $hidden = ['tarif_vendor_satuan', 'subtotal_vendor', 'margin', 'snapshot_data'];

    protected function casts(): array
    {
        return [
            'item_type' => SnapshotItemType::class,
            'tipe_harga' => PriceType::class,
            'kuantitas' => 'decimal:2',
            'ukuran_cm' => 'integer',
            'harga_satuan_klien' => 'decimal:2',
            'tarif_vendor_satuan' => 'decimal:2',
            'subtotal_klien' => 'decimal:2',
            'subtotal_vendor' => 'decimal:2',
            'margin' => 'decimal:2',
            'snapshot_data' => 'array',
            'is_locked' => 'boolean',
            'locked_at' => 'datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }
}
