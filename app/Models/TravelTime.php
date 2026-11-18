<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TravelTime extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'waktu_tempuh_menit' => 'integer',
            'waktu_persiapan_menit' => 'integer',
        ];
    }

    public function from(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'from_location_id');
    }

    public function to(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'to_location_id');
    }

    /** Total jeda t(l(j), l(i)) dalam menit. */
    public function totalMenit(): int
    {
        return $this->waktu_tempuh_menit + $this->waktu_persiapan_menit;
    }
}
