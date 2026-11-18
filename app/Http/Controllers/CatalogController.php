<?php

namespace App\Http\Controllers;

use App\Models\DynamicComponent;
use App\Models\Package;
use App\Models\VendorService;
use Illuminate\View\View;

/** Katalog publik: hanya harga klien (tanpa tarif vendor/margin). */
class CatalogController extends Controller
{
    public function index(): View
    {
        return view('katalog', [
            'packages' => Package::active()->orderBy('harga_dasar')->get(),
            'components' => DynamicComponent::where('is_active', true)->orderBy('kategori')->get(),
            'services' => VendorService::where('is_active', true)->orderBy('kategori')->get(['id', 'nama_layanan', 'kategori', 'satuan', 'tarif_klien']),
        ]);
    }
}
