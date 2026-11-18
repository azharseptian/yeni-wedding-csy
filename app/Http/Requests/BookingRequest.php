<?php

namespace App\Http\Requests;

use App\Enums\JenisAcara;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public static function itemRules(): array
    {
        return [
            'komponen' => ['nullable', 'array'],
            'komponen.*.component_id' => ['required', 'exists:dynamic_components,id'],
            'komponen.*.kuantitas' => ['nullable', 'numeric', 'min:0', 'max:10000'],
            'komponen.*.ukuran_cm' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'vendor' => ['nullable', 'array'],
            'vendor.*.vendor_service_id' => ['required', 'exists:vendor_services,id'],
            'vendor.*.kuantitas' => ['nullable', 'numeric', 'min:0', 'max:100000'],
        ];
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'package_id' => ['required', 'exists:packages,id'],
            'tanggal_acara' => ['required', 'date', 'after:today'],
            'location_id' => ['required', 'exists:locations,id'],
            'jenis_acara' => ['required', Rule::enum(JenisAcara::class)],
            'jam_mulai_acara' => ['nullable', 'date_format:H:i'],
            'jam_selesai_acara' => ['nullable', 'date_format:H:i', 'after:jam_mulai_acara'],
            'alamat_acara' => ['nullable', 'string', 'max:500'],
            'jumlah_tamu' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'catatan' => ['nullable', 'string', 'max:1000'],
        ] + static::itemRules();
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'package_id' => 'paket', 'tanggal_acara' => 'tanggal acara', 'location_id' => 'lokasi',
            'jam_mulai_acara' => 'jam mulai', 'jam_selesai_acara' => 'jam selesai', 'jumlah_tamu' => 'jumlah tamu',
        ];
    }
}
