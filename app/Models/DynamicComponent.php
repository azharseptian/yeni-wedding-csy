<?php

namespace App\Models;

use App\Enums\PriceType;
use Illuminate\Database\Eloquent\Model;

class DynamicComponent extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'tipe_harga' => PriceType::class,
            'harga_satuan' => 'decimal:2',
            'harga_diperbarui_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }
}
