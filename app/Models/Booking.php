<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\JenisAcara;
use App\Enums\VerificationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $hidden = ['total_biaya_vendor', 'total_margin'];

    protected function casts(): array
    {
        return [
            'status' => BookingStatus::class,
            'jenis_acara' => JenisAcara::class,
            'tanggal_acara' => 'date',
            'batas_pelunasan' => 'date',
            'total_tagihan' => 'decimal:2',
            'total_dibayar' => 'decimal:2',
            'total_refund' => 'decimal:2',
            'total_biaya_vendor' => 'decimal:2',
            'total_margin' => 'decimal:2',
            'is_date_locked' => 'boolean',
            'is_price_locked' => 'boolean',
            'date_locked_at' => 'datetime',
            'price_locked_at' => 'datetime',
            'dikonfirmasi_at' => 'datetime',
            'ambang_dicek_at' => 'datetime',
            'siap_pelaksanaan_at' => 'datetime',
            'lunas_at' => 'datetime',
            'dibatalkan_at' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(BookingItemSnapshot::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function verifiedPayments(): HasMany
    {
        return $this->payments()->where('status_verifikasi', VerificationStatus::Terverifikasi->value);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    public function inventories(): HasMany
    {
        return $this->hasMany(BookingInventory::class);
    }

    public function auditLogs(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }

    /** Booking yang mengunci tanggal di kalender (AB-02). */
    public function scopeDateLocked(Builder $query): Builder
    {
        return $query->where('is_date_locked', true)
            ->whereIn('status', array_map(fn ($s) => $s->value, BookingStatus::locking()));
    }

    public function sisaTagihan(): float
    {
        return max(0, (float) $this->total_tagihan - (float) $this->total_dibayar);
    }

    public function persentaseDibayar(): float
    {
        return (float) $this->total_tagihan > 0
            ? round((float) $this->total_dibayar / (float) $this->total_tagihan * 100, 2)
            : 0.0;
    }
}
