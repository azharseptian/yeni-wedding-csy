<x-layout title="Masuk" variant="guest">
    <div class="mx-auto max-w-md">
        <div class="card">
            <h1 class="text-xl font-bold">Masuk ke Yeni Wedding</h1>
            <p class="mt-1 text-sm text-stone-500">Gunakan email dan kata sandi yang terdaftar.</p>
            <form method="POST" action="{{ route('login') }}" class="mt-5 space-y-4">
                @csrf
                <div>
                    <label class="label" for="email">Email</label>
                    <input class="input" id="email" type="email" name="email" value="{{ old('email') }}" required autofocus>
                </div>
                <div>
                    <label class="label" for="password">Kata Sandi</label>
                    <input class="input" id="password" type="password" name="password" required>
                </div>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="remember" class="rounded"> Ingat saya</label>
                <button class="btn-primary w-full py-3">Masuk</button>
            </form>
        </div>
        <p class="mt-4 text-center text-sm text-stone-600">Calon pengantin belum punya akun? <a href="{{ route('register') }}" class="font-semibold text-brand-700 hover:underline">Daftar di sini</a></p>
    </div>
</x-layout>
