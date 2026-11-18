<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorService extends Model
{
    protected $guarded = ['id', 'margin'];

    /** AB-08: tarif_vendor & margin disembunyikan secara default; Owner memanggil makeVisible(). */
    protected $hidden = ['tarif_vendor', 'margin'];

    protected function casts(): array
    {
        return [
            'tarif_vendor' => 'decimal:2',
            'tarif_klien' => 'decimal:2',
            'margin' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_user_id');
    }
}
