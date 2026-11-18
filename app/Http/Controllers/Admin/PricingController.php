<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PriceType;
use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\DynamicComponent;
use App\Models\Package;
use App\Models\User;
use App\Models\VendorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PricingController extends Controller
{
    public function index(): View
    {
        return view('admin.pricing', [
            'packages' => Package::orderBy('kode')->orderByDesc('versi')->get(),
            'components' => DynamicComponent::orderBy('kategori')->orderBy('nama')->get(),
            'services' => VendorService::with('vendor')->orderBy('vendor_user_id')->get()->each->makeVisible(['tarif_vendor', 'margin']),
            'vendors' => User::whereHas('role', fn ($q) => $q->where('name', RoleName::VendorKeluarga->value))->orderBy('name')->get(),
            'priceTypes' => PriceType::cases(),
        ]);
    }

    /** Paket berversi: perubahan harga membuat versi baru; versi lama dinonaktifkan (snapshot pesanan tetap). */
    public function storePackage(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'kode' => ['required', 'string', 'max:30', 'alpha_dash'],
            'nama' => ['required', 'string', 'max:100'],
            'harga_dasar' => ['required', 'numeric', 'min:0'],
            'deskripsi' => ['nullable', 'string', 'max:1000'],
            'rincian' => ['nullable', 'string', 'max:3000'],
        ]);

        $package = DB::transaction(function () use ($data) {
            $lama = Package::where('kode', $data['kode'])->orderByDesc('versi')->first();
            Package::where('kode', $data['kode'])->where('is_active', true)->update(['is_active' => false, 'berlaku_sampai' => today()->subDay()]);

            return Package::create([
                'kode' => $data['kode'],
                'versi' => ($lama->versi ?? 0) + 1,
                'nama' => $data['nama'],
                'harga_dasar' => $data['harga_dasar'],
                'deskripsi' => $data['deskripsi'] ?? $lama?->deskripsi,
                'rincian' => isset($data['rincian']) ? array_values(array_filter(array_map('trim', explode("\n", $data['rincian'])))) : $lama?->rincian,
                'berlaku_mulai' => today(),
                'is_active' => true,
            ]);
        });

        AuditLog::record('harga.paket', $package, null, $package->only('kode', 'versi', 'harga_dasar'), 'Versi paket baru.');

        return back()->with('success', "{$package->nama} versi {$package->versi} berlaku mulai hari ini.");
    }

    public function updateComponent(Request $request, DynamicComponent $component): RedirectResponse
    {
        $data = $request->validate([
            'harga_satuan' => ['required', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $lama = (float) $component->harga_satuan;
        $component->update(['harga_satuan' => $data['harga_satuan'], 'is_active' => $request->boolean('is_active'), 'harga_diperbarui_at' => now()]);
        AuditLog::record('harga.komponen', $component, ['harga_satuan' => $lama], ['harga_satuan' => (float) $data['harga_satuan']], 'Harga komponen diperbarui.');

        return back()->with('success', "Harga {$component->nama} diperbarui. Pesanan yang sudah terkunci tidak berubah.");
    }

    public function storeComponent(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'kode' => ['required', 'string', 'max:30', 'alpha_dash', 'unique:dynamic_components,kode'],
            'nama' => ['required', 'string', 'max:100'],
            'kategori' => ['required', 'string', 'max:50'],
            'tipe_harga' => ['required', Rule::enum(PriceType::class)],
            'harga_satuan' => ['required', 'numeric', 'min:0'],
            'satuan' => ['required', 'string', 'max:30'],
            'ukuran_min_cm' => ['nullable', 'integer', 'min:1'],
            'ukuran_max_cm' => ['nullable', 'integer', 'gte:ukuran_min_cm'],
        ]);
        DynamicComponent::create($data + ['is_active' => true, 'harga_diperbarui_at' => now()]);

        return back()->with('success', 'Komponen harga ditambahkan.');
    }

    public function updateService(Request $request, VendorService $service): RedirectResponse
    {
        $data = $request->validate([
            'tarif_vendor' => ['required', 'numeric', 'min:0'],
            'tarif_klien' => ['required', 'numeric', 'gte:tarif_vendor'],
            'is_active' => ['nullable', 'boolean'],
        ], ['tarif_klien.gte' => 'Tarif klien tidak boleh lebih kecil dari tarif vendor.']);
        $service->update(['tarif_vendor' => $data['tarif_vendor'], 'tarif_klien' => $data['tarif_klien'], 'is_active' => $request->boolean('is_active')]);

        return back()->with('success', "Tarif {$service->nama_layanan} diperbarui.");
    }

    public function storeService(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'vendor_user_id' => ['required', 'exists:users,id'],
            'nama_layanan' => ['required', 'string', 'max:150'],
            'kategori' => ['required', 'string', 'max:50'],
            'satuan' => ['required', 'string', 'max:30'],
            'tarif_vendor' => ['required', 'numeric', 'min:0'],
            'tarif_klien' => ['required', 'numeric', 'gte:tarif_vendor'],
        ], ['tarif_klien.gte' => 'Tarif klien tidak boleh lebih kecil dari tarif vendor.']);
        VendorService::create($data + ['is_active' => true]);

        return back()->with('success', 'Layanan vendor ditambahkan.');
    }
}
