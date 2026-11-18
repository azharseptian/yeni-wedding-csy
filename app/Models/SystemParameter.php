<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemParameter extends Model
{
    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::saved(fn (self $p) => Cache::forget("sysparam.{$p->key}"));
        static::deleted(fn (self $p) => Cache::forget("sysparam.{$p->key}"));
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        // Simpan nilai terketik (bukan objek model) agar aman diserialisasi oleh driver cache mana pun.
        $cached = Cache::rememberForever("sysparam.{$key}", fn () => ['ada' => (bool) ($param = static::query()->where('key', $key)->first()), 'nilai' => $param?->typedValue()]);

        return is_array($cached) && $cached['ada'] ? $cached['nilai'] : $default;
    }

    public function typedValue(): mixed
    {
        return match ($this->tipe) {
            'integer' => (int) $this->value,
            'decimal' => (float) $this->value,
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode((string) $this->value, true),
            default => $this->value,
        };
    }
}
