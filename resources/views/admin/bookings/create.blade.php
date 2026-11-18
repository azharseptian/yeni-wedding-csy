<x-layout title="Pesanan Baru">
    <h1 class="text-2xl font-bold">Pesanan Baru (Penawaran)</h1>
    <form method="POST" action="{{ route('admin.bookings.store') }}" class="mt-4 grid gap-6 lg:grid-cols-3">
        @csrf
        <div class="space-y-6 lg:col-span-2">
            <div class="card">
                <h2 class="font-bold">Klien</h2>
                <select class="input mt-2" name="client_id">
                    <option value="">— klien baru (isi di bawah) —</option>
                    @foreach ($clients as $c)<option value="{{ $c->id }}" @selected(old('client_id') == $c->id)>{{ $c->namaPasangan() }} · {{ $c->phone }}</option>@endforeach
                </select>
                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                    <input class="input" name="nama_pengantin_wanita" placeholder="Nama pengantin wanita" value="{{ old('nama_pengantin_wanita') }}">
                    <input class="input" name="nama_pengantin_pria" placeholder="Nama pengantin pria" value="{{ old('nama_pengantin_pria') }}">
                    <input class="input" name="nama_kontak" placeholder="Nama kontak" value="{{ old('nama_kontak') }}">
                    <input class="input" name="phone" placeholder="Nomor WhatsApp" value="{{ old('phone') }}">
                </div>
            </div>
            <div class="card"><h2 class="mb-3 font-bold">Acara</h2>@include('partials.event-fields')</div>
            <div class="card">@include('partials.item-fields', ['showVendorCost' => true])</div>
        </div>
        <div>
            <div class="card sticky top-4 space-y-3 text-sm">
                <p>Pesanan dibuat sebagai <strong>Penawaran (Tentatif)</strong>. Harga dihitung dari master terkini dan dikunci otomatis saat DP pertama tercatat.</p>
                <button class="btn-primary w-full py-3">Simpan Penawaran</button>
            </div>
        </div>
    </form>
</x-layout>
