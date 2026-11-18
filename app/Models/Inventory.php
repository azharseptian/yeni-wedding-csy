<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Inventory extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'jumlah_total' => 'integer',
            'is_rawan_hilang' => 'boolean',
        ];
    }

    public function bookingInventories(): HasMany
    {
        return $this->hasMany(BookingInventory::class);
    }
}
