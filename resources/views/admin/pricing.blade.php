<x-layout title="Harga & Vendor">
    <h1 class="text-2xl font-bold">Harga & Vendor</h1>
    <p class="text-sm text-stone-500">Perubahan harga hanya berlaku untuk penawaran baru/tentatif. Pesanan yang sudah DP memakai snapshot harga terkunci.</p>

    <div class="card mt-6">
        <h2 class="font-bold">Paket Dasar (berversi)</h2>
        <table class="table mt-2">
            <thead><tr><th>Kode</th><th>Versi</th><th>Nama</th><th class="text-right">Harga</th><th>Berlaku</th><th>Status</th></tr></thead>
            <tbody class="divide-y divide-stone-100">
                @foreach ($packages as $p)
                    <tr @class(['text-stone-400' => ! $p->is_active])><td>{{ $p->kode }}</td><td>v{{ $p->versi }}</td><td>{{ $p->nama }}</td><td class="text-right">{{ rupiah($p->harga_dasar) }}</td><td>{{ tanggal($p->berlaku_mulai, 'j M Y') }} – {{ $p->berlaku_sampai ? tanggal($p->berlaku_sampai, 'j M Y') : 'sekarang' }}</td><td>{{ $p->is_active ? 'Aktif' : 'Arsip' }}</td></tr>
                @endforeach
            </tbody>
        </table>
        <form method="POST" action="{{ route('admin.pricing.package') }}" class="mt-4 grid gap-3 rounded-lg bg-stone-50 p-4 sm:grid-cols-4">
            @csrf
            <input class="input" name="kode" placeholder="Kode (mis. PKT-MNG)" required>
            <input class="input" name="nama" placeholder="Nama paket" required>
            <input class="input" type="number" min="0" name="harga_dasar" placeholder="Harga dasar" required>
            <button class="btn-primary">Buat Versi Baru</button>
            <textarea class="input sm:col-span-4" name="rincian" rows="2" placeholder="Rincian (satu item per baris; kosongkan untuk menyalin versi lama)"></textarea>
        </form>
    </div>

    <div class="card mt-6">
        <h2 class="font-bold">Komponen Berfluktuasi</h2>
        <div class="mt-2 overflow-x-auto">
            <table class="table">
                <thead><tr><th>Komponen</th><th>Tipe</th><th>Harga</th><th>Diperbarui</th><th></th></tr></thead>
                <tbody class="divide-y divide-stone-100">
                    @foreach ($components as $c)
                        <tr>
                            <td>{{ $c->nama }}<p class="text-xs text-stone-500">{{ $c->kode }}{{ $c->ukuran_min_cm ? ' · '.$c->ukuran_min_cm.'–'.$c->ukuran_max_cm.' cm' : '' }}</p></td>
                            <td>{{ $c->tipe_harga->label() }}</td>
                            <td colspan="3">
                                <form method="POST" action="{{ route('admin.pricing.component', $c) }}" class="flex flex-wrap items-center gap-2">
                                    @csrf @method('PUT')
                                    <input class="input w-36" type="number" min="0" name="harga_satuan" value="{{ (float) $c->harga_satuan }}">
                                    <span class="text-xs text-stone-500">/{{ $c->satuan }}</span>
                                    <label class="flex items-center gap-1 text-xs"><input type="checkbox" name="is_active" value="1" @checked($c->is_active)> aktif</label>
                                    <span class="text-xs text-stone-400">{{ $c->harga_diperbarui_at?->diffForHumans() }}</span>
                                    <button class="btn-secondary px-3 py-1">Simpan</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <form method="POST" action="{{ route('admin.pricing.component.store') }}" class="mt-4 grid gap-3 rounded-lg bg-stone-50 p-4 sm:grid-cols-4">
            @csrf
            <input class="input" name="kode" placeholder="Kode" required>
            <input class="input" name="nama" placeholder="Nama komponen" required>
            <input class="input" name="kategori" placeholder="Kategori (bunga/tenda/...)" required>
            <select class="input" name="tipe_harga">@foreach ($priceTypes as $t)<option value="{{ $t->value }}">{{ $t->label() }}</option>@endforeach</select>
            <input class="input" type="number" min="0" name="harga_satuan" placeholder="Harga satuan" required>
            <input class="input" name="satuan" placeholder="Satuan (set/cm/...)" required>
            <input class="input" type="number" min="1" name="ukuran_min_cm" placeholder="Ukuran min (cm)">
            <input class="input" type="number" min="1" name="ukuran_max_cm" placeholder="Ukuran maks (cm)">
            <button class="btn-primary sm:col-span-4">Tambah Komponen</button>
        </form>
    </div>

    <div class="card mt-6">
        <h2 class="font-bold">Layanan Vendor Keluarga (Dual-Pricing)</h2>
        <p class="text-xs text-amber-700">Tarif vendor & margin hanya terlihat oleh Owner.</p>
        <div class="mt-2 overflow-x-auto">
            <table class="table">
                <thead><tr><th>Layanan</th><th>Vendor</th><th>Tarif Vendor / Tarif Klien</th><th>Margin</th></tr></thead>
                <tbody class="divide-y divide-stone-100">
                    @foreach ($services as $s)
                        <tr>
                            <td>{{ $s->nama_layanan }}<p class="text-xs text-stone-500">per {{ $s->satuan }}</p></td>
                            <td>{{ $s->vendor?->name }}</td>
                            <td>
                                <form method="POST" action="{{ route('admin.pricing.service', $s) }}" class="flex flex-wrap items-center gap-2">
                                    @csrf @method('PUT')
                                    <input class="input w-32" type="number" min="0" name="tarif_vendor" value="{{ (float) $s->tarif_vendor }}" title="Tarif vendor">
                                    <input class="input w-32" type="number" min="0" name="tarif_klien" value="{{ (float) $s->tarif_klien }}" title="Tarif klien">
                                    <label class="flex items-center gap-1 text-xs"><input type="checkbox" name="is_active" value="1" @checked($s->is_active)> aktif</label>
                                    <button class="btn-secondary px-3 py-1">Simpan</button>
                                </form>
                            </td>
                            <td class="font-semibold text-emerald-700">{{ rupiah($s->margin) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <form method="POST" action="{{ route('admin.pricing.service.store') }}" class="mt-4 grid gap-3 rounded-lg bg-stone-50 p-4 sm:grid-cols-3">
            @csrf
            <select class="input" name="vendor_user_id">@foreach ($vendors as $v)<option value="{{ $v->id }}">{{ $v->name }}</option>@endforeach</select>
            <input class="input" name="nama_layanan" placeholder="Nama layanan" required>
            <input class="input" name="kategori" placeholder="Kategori" required>
            <input class="input" name="satuan" placeholder="Satuan (porsi/acara/...)" required>
            <input class="input" type="number" min="0" name="tarif_vendor" placeholder="Tarif vendor" required>
            <input class="input" type="number" min="0" name="tarif_klien" placeholder="Tarif klien" required>
            <button class="btn-primary sm:col-span-3">Tambah Layanan</button>
        </form>
    </div>
</x-layout>
