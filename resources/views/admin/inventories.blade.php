<x-layout title="Inventaris">
    <h1 class="text-2xl font-bold">Inventaris Perlengkapan</h1>
    <div class="card mt-4 overflow-x-auto p-0">
        <table class="table">
            <thead><tr><th>Kode</th><th>Nama</th><th>Kategori</th><th>Ukuran</th><th class="text-right">Total</th><th class="text-right">Sedang Dipakai</th><th class="text-right">Tersedia</th></tr></thead>
            <tbody class="divide-y divide-stone-100">
                @foreach ($inventories as $i)
                    <tr>
                        <td>{{ $i->kode }}</td>
                        <td>{{ $i->nama }} @if ($i->is_rawan_hilang)<span class="ml-1 rounded bg-amber-100 px-1.5 text-xs text-amber-800">rawan hilang</span>@endif</td>
                        <td>{{ $i->kategori }}</td><td>{{ $i->ukuran ?? '-' }}</td>
                        <td class="text-right">{{ $i->jumlah_total }} {{ $i->satuan }}</td>
                        <td class="text-right">{{ (int) $i->dipakai }}</td>
                        <td class="text-right font-semibold">{{ $i->jumlah_total - (int) $i->dipakai }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <form method="POST" action="{{ route('admin.inventories.store') }}" class="card mt-6 grid gap-3 sm:grid-cols-4">
        @csrf
        <h2 class="font-bold sm:col-span-4">Tambah Barang</h2>
        <input class="input" name="kode" placeholder="Kode" required>
        <input class="input" name="nama" placeholder="Nama barang" required>
        <input class="input" name="kategori" placeholder="Kategori (aksesoris/busana/...)" required>
        <input class="input" name="ukuran" placeholder="Ukuran (S/M/L)">
        <input class="input" type="number" min="0" name="jumlah_total" placeholder="Jumlah" required>
        <input class="input" name="satuan" value="buah" required>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_rawan_hilang" value="1"> Rawan hilang</label>
        <button class="btn-primary">Tambah</button>
    </form>
</x-layout>
