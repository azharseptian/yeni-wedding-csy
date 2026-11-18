<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function index(): View
    {
        return view('admin.inventories', [
            'inventories' => Inventory::withSum(['bookingInventories as dipakai' => fn ($q) => $q->where('is_keluar', true)->where('is_kembali', false)], 'jumlah')
                ->orderBy('kategori')->orderBy('nama')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Inventory::create($request->validate([
            'kode' => ['required', 'string', 'max:30', 'alpha_dash', 'unique:inventories,kode'],
            'nama' => ['required', 'string', 'max:150'],
            'kategori' => ['required', 'string', 'max:50'],
            'ukuran' => ['nullable', 'string', 'max:20'],
            'jumlah_total' => ['required', 'integer', 'min:0'],
            'satuan' => ['required', 'string', 'max:30'],
            'is_rawan_hilang' => ['nullable', 'boolean'],
        ]) + ['is_rawan_hilang' => $request->boolean('is_rawan_hilang')]);

        return back()->with('success', 'Barang inventaris ditambahkan.');
    }
}
