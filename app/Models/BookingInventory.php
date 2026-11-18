<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingInventory extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'jumlah' => 'integer',
            'jumlah_kembali' => 'integer',
            'is_keluar' => 'boolean',
            'is_kembali' => 'boolean',
            'keluar_at' => 'datetime',
            'kembali_at' => 'datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }

    public function pengeluar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dikeluarkan_oleh');
    }

    public function pengembali(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dikembalikan_oleh');
    }
}
