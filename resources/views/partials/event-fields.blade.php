<div class="grid gap-4 sm:grid-cols-2">
    <div class="sm:col-span-2">
        <label class="label">Paket Dasar</label>
        <select class="input" name="package_id" required>
            <option value="">— pilih paket —</option>
            @foreach ($packages as $p)
                <option value="{{ $p->id }}" @selected(old('package_id', $selected ?? null) == $p->id)>{{ $p->nama }} (v{{ $p->versi }}) — {{ rupiah($p->harga_dasar) }}</option>
            @endforeach
        </select>
    </div>
    <div><label class="label">Tanggal Acara</label><input class="input" type="date" name="tanggal_acara" min="{{ today()->addDay()->toDateString() }}" value="{{ old('tanggal_acara') }}" required></div>
    <div>
        <label class="label">Jenis Acara</label>
        <select class="input" name="jenis_acara">
            @foreach ($jenisAcara as $j)<option value="{{ $j->value }}" @selected(old('jenis_acara', 'akad_resepsi') === $j->value)>{{ $j->label() }}</option>@endforeach
        </select>
    </div>
    <div><label class="label">Jam Mulai</label><input class="input" type="time" name="jam_mulai_acara" value="{{ old('jam_mulai_acara') }}"></div>
    <div><label class="label">Jam Selesai</label><input class="input" type="time" name="jam_selesai_acara" value="{{ old('jam_selesai_acara') }}"></div>
    <div>
        <label class="label">Lokasi / Area</label>
        <select class="input" name="location_id" required>
            @foreach ($locations as $l)<option value="{{ $l->id }}" @selected(old('location_id') == $l->id)>{{ $l->nama }} — {{ $l->kota }}</option>@endforeach
        </select>
    </div>
    <div><label class="label">Perkiraan Jumlah Tamu</label><input class="input" type="number" min="1" name="jumlah_tamu" value="{{ old('jumlah_tamu') }}"></div>
    <div class="sm:col-span-2"><label class="label">Alamat Lengkap Acara</label><textarea class="input" name="alamat_acara" rows="2">{{ old('alamat_acara') }}</textarea></div>
    <div class="sm:col-span-2"><label class="label">Catatan</label><textarea class="input" name="catatan" rows="2">{{ old('catatan') }}</textarea></div>
</div>
