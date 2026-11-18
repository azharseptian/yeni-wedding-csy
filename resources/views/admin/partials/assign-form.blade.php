@php($conflict = session('conflict'))
<form method="POST" action="{{ route('admin.schedules.store') }}" class="mt-4 grid gap-3 rounded-lg bg-stone-50 p-4 sm:grid-cols-3">
    @csrf
    <input type="hidden" name="booking_id" value="{{ $booking->id }}">
    <div><label class="label">Peran</label><select class="input" name="peran">@foreach ($scheduleRoles as $r)<option value="{{ $r->value }}" @selected(old('peran') === $r->value)>{{ $r->label() }}</option>@endforeach</select></div>
    <div class="sm:col-span-2"><label class="label">Petugas</label>
        <select class="input" name="user_id"><option value="">— pilih —</option>@foreach ($staff as $u)<option value="{{ $u->id }}" @selected(old('user_id') == $u->id)>{{ $u->name }} · {{ $u->jabatan?->label() ?? $u->role->name->label() }}</option>@endforeach</select>
    </div>
    <div><label class="label">Tanggal kerja</label><input class="input" type="date" name="tanggal" value="{{ old('tanggal', $booking->tanggal_acara->toDateString()) }}" required></div>
    <div><label class="label">Mulai</label><input class="input" type="time" name="jam_mulai" value="{{ old('jam_mulai') }}" required></div>
    <div><label class="label">Selesai</label><input class="input" type="time" name="jam_selesai" value="{{ old('jam_selesai') }}" required></div>
    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_tenaga_lepas" value="1" @checked(old('is_tenaga_lepas'))> Tenaga lepas</label>
    <input class="input" name="nama_tenaga_lepas" placeholder="Nama tenaga lepas" value="{{ old('nama_tenaga_lepas') }}">
    <input class="input" name="phone_tenaga_lepas" placeholder="No. HP tenaga lepas" value="{{ old('phone_tenaga_lepas') }}">
    @if ($conflict)
        <div class="rounded-lg border border-red-300 bg-red-50 p-3 text-sm text-red-800 sm:col-span-3">
            <p class="font-bold">BENTROK</p>
            <ul class="list-inside list-disc">@foreach ($conflict as $c)<li>{{ $c['message'] }}</li>@endforeach</ul>
            <p class="mt-2 font-semibold">Saran:</p>
            <ul class="list-inside list-disc">@foreach (collect($conflict)->pluck('suggestions')->flatten()->unique() as $s)<li>{{ $s }}</li>@endforeach</ul>
            <label class="label mt-3">Alasan Override (wajib bila tetap disimpan)</label>
            <input class="input" name="alasan_override" value="{{ old('alasan_override') }}">
        </div>
    @endif
    <div class="sm:col-span-3"><button class="btn-primary">{{ $conflict ? 'Simpan dengan Override' : 'Tugaskan' }}</button></div>
</form>
