<x-layout title="Pengaturan">
    <h1 class="text-2xl font-bold">Pengaturan Sistem</h1>
    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <div class="card">
            <h2 class="font-bold">Parameter</h2>
            <form method="POST" action="{{ route('admin.settings.parameters') }}" class="mt-3 space-y-3">
                @csrf @method('PUT')
                @foreach ($parameters as $p)
                    <div>
                        <label class="label">{{ $p->label }} <span class="text-xs font-normal text-stone-400">({{ $p->grup }})</span></label>
                        @if (in_array($p->tipe, ['json'], true) || strlen((string) $p->value) > 80)
                            <textarea class="input font-mono text-xs" rows="3" name="parameter[{{ $p->key }}]">{{ $p->value }}</textarea>
                        @else
                            <input class="input" name="parameter[{{ $p->key }}]" value="{{ $p->value }}">
                        @endif
                        @if ($p->deskripsi)<p class="mt-1 text-xs text-stone-500">{{ $p->deskripsi }}</p>@endif
                    </div>
                @endforeach
                <button class="btn-primary">Simpan Parameter</button>
            </form>
        </div>
        <div class="space-y-6">
            <div class="card">
                <h2 class="font-bold">Waktu Tempuh & Persiapan Antarlokasi</h2>
                <p class="text-xs text-stone-500">Dipakai rumus bentrok: jeda = waktu tempuh + persiapan.</p>
                <form method="POST" action="{{ route('admin.settings.travel') }}" class="mt-3 grid gap-2 sm:grid-cols-2">
                    @csrf @method('PUT')
                    <select class="input" name="from_location_id">@foreach ($locations as $l)<option value="{{ $l->id }}">{{ $l->nama }}</option>@endforeach</select>
                    <select class="input" name="to_location_id">@foreach ($locations as $l)<option value="{{ $l->id }}" @selected($loop->index === 1)>{{ $l->nama }}</option>@endforeach</select>
                    <input class="input" type="number" min="0" name="waktu_tempuh_menit" placeholder="Tempuh (menit)" required>
                    <input class="input" type="number" min="0" name="waktu_persiapan_menit" value="30" placeholder="Persiapan (menit)" required>
                    <button class="btn-primary sm:col-span-2">Simpan</button>
                </form>
                <table class="table mt-4">
                    <thead><tr><th>Rute</th><th class="text-right">Tempuh</th><th class="text-right">Persiapan</th></tr></thead>
                    <tbody class="divide-y divide-stone-100">
                        @foreach ($travelTimes as $t)
                            <tr><td>{{ $t->from->nama }} ↔ {{ $t->to->nama }}</td><td class="text-right">{{ $t->waktu_tempuh_menit }} mnt</td><td class="text-right">{{ $t->waktu_persiapan_menit }} mnt</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card">
                <h2 class="font-bold">Tambah Lokasi</h2>
                <form method="POST" action="{{ route('admin.settings.location') }}" class="mt-3 grid gap-2">
                    @csrf
                    <input class="input" name="nama" placeholder="Nama gedung / kediaman" required>
                    <input class="input" name="kota" placeholder="Kota/Kabupaten" required>
                    <input class="input" name="alamat" placeholder="Alamat">
                    <button class="btn-secondary">Tambah</button>
                </form>
            </div>
        </div>
    </div>
</x-layout>
