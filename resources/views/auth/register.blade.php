<x-layout title="Daftar" variant="guest">
    <div class="mx-auto max-w-2xl">
        <div class="card">
            <h1 class="text-xl font-bold">Daftar sebagai Calon Pengantin</h1>
            <p class="mt-1 text-sm text-stone-500">Setelah mendaftar, Anda bisa memesan paket, mengunggah bukti transfer, dan memantau jadwal acara.</p>
            <form method="POST" action="{{ route('register') }}" class="mt-5 grid gap-4 sm:grid-cols-2">
                @csrf
                <div><label class="label">Nama Pengantin Wanita</label><input class="input" name="nama_pengantin_wanita" value="{{ old('nama_pengantin_wanita') }}" required></div>
                <div><label class="label">Nama Pengantin Pria</label><input class="input" name="nama_pengantin_pria" value="{{ old('nama_pengantin_pria') }}" required></div>
                <div><label class="label">Nama Kontak (yang bisa dihubungi)</label><input class="input" name="nama_kontak" value="{{ old('nama_kontak') }}" required></div>
                <div><label class="label">Nomor WhatsApp</label><input class="input" name="phone" value="{{ old('phone') }}" placeholder="081234567890" required></div>
                <div><label class="label">Email</label><input class="input" type="email" name="email" value="{{ old('email') }}" required></div>
                <div><label class="label">Kota</label><input class="input" name="kota" value="{{ old('kota') }}" placeholder="Kota Tasikmalaya"></div>
                <div class="sm:col-span-2"><label class="label">Alamat</label><textarea class="input" name="alamat" rows="2">{{ old('alamat') }}</textarea></div>
                <div><label class="label">Kata Sandi (min. 8 karakter)</label><input class="input" type="password" name="password" required></div>
                <div><label class="label">Ulangi Kata Sandi</label><input class="input" type="password" name="password_confirmation" required></div>
                <button class="btn-primary py-3 sm:col-span-2">Daftar</button>
            </form>
        </div>
        <p class="mt-4 text-center text-sm text-stone-600">Sudah punya akun? <a href="{{ route('login') }}" class="font-semibold text-brand-700 hover:underline">Masuk</a></p>
    </div>
</x-layout>
