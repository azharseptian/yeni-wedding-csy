@props(['title' => null, 'variant' => null])
@php
    $user = auth()->user();
    $role = $user?->role?->name;
    $variant ??= match ($role) {
        \App\Enums\RoleName::Owner => 'admin',
        \App\Enums\RoleName::TimInternal => 'tim',
        \App\Enums\RoleName::VendorKeluarga => 'vendor',
        \App\Enums\RoleName::Klien => 'portal',
        default => 'guest',
    };
    $nav = match ($variant) {
        'admin' => [
            ['admin.dashboard', 'Dasbor', 'admin.dashboard'],
            ['admin.bookings.index', 'Pesanan', 'admin.bookings.*'],
            ['admin.schedules.index', 'Jadwal', 'admin.schedules.*'],
            ['admin.pricing.index', 'Harga & Vendor', 'admin.pricing.*'],
            ['admin.availability.index', 'Ketersediaan', 'admin.availability.*'],
            ['admin.inventories.index', 'Inventaris', 'admin.inventories.*'],
            ['admin.reports.index', 'Laporan Keuangan', 'admin.reports.*'],
            ['admin.settings.index', 'Pengaturan', 'admin.settings.*'],
            ['admin.audit.index', 'Log', 'admin.audit.*'],
        ],
        'tim' => [['tim.jadwal', 'Jadwal Saya', 'tim.*']],
        'vendor' => [['vendor.beranda', 'Beranda', 'vendor.*']],
        'portal' => [
            ['portal.beranda', 'Pesanan Saya', 'portal.beranda'],
            ['portal.pesan', 'Pesan Paket', 'portal.pesan*'],
            ['katalog', 'Katalog', 'katalog'],
        ],
        default => [['katalog', 'Katalog', 'katalog']],
    };
    $container = match ($variant) {
        'tim' => 'max-w-xl',
        'vendor' => 'max-w-2xl',
        default => 'max-w-7xl',
    };
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title.' · ' : '' }}Yeni Wedding</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-stone-50 font-sans text-stone-800 antialiased {{ $variant === 'vendor' ? 'text-lg' : '' }}">
    <header class="border-b border-stone-200 bg-white">
        <div class="mx-auto flex {{ $container }} items-center justify-between gap-4 px-4 py-3">
            <a href="{{ $user ? route('home') : route('katalog') }}" class="flex items-center gap-2">
                <span class="flex size-9 items-center justify-center rounded-full bg-brand-600 font-bold text-white">YW</span>
                <span class="text-lg font-bold text-brand-900">Yeni Wedding</span>
            </a>
            <div class="flex items-center gap-3">
                @auth
                    <span class="hidden text-right text-sm sm:block">
                        <span class="block font-semibold">{{ $user->name }}</span>
                        <span class="block text-xs text-stone-500">{{ $role->label() }}{{ $user->jabatan ? ' · '.$user->jabatan->label() : '' }}</span>
                    </span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="btn-secondary {{ $variant === 'vendor' ? 'px-5 py-3 text-base' : '' }}">Keluar</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="btn-secondary">Masuk</a>
                    <a href="{{ route('register') }}" class="btn-primary">Daftar</a>
                @endauth
            </div>
        </div>
        @if (count($nav) > 1)
            <nav class="mx-auto flex {{ $container }} gap-1 overflow-x-auto px-4">
                @foreach ($nav as [$route, $label, $pattern])
                    <a href="{{ route($route) }}" @class([
                        'whitespace-nowrap border-b-2 px-3 py-2 text-sm font-medium',
                        'border-brand-600 text-brand-700' => request()->routeIs($pattern),
                        'border-transparent text-stone-500 hover:text-stone-800' => ! request()->routeIs($pattern),
                    ])>{{ $label }}</a>
                @endforeach
            </nav>
        @endif
    </header>

    <main class="mx-auto {{ $container }} px-4 py-6">
        @if (session('success'))
            <div class="mb-4 rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-3 text-emerald-800" role="status">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-4 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-red-800" role="alert">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="mb-4 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-red-800" role="alert">
                <p class="font-semibold">Periksa kembali isian Anda:</p>
                <ul class="mt-1 list-inside list-disc text-sm">
                    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        {{ $slot }}
    </main>

    <footer class="py-6 text-center text-xs text-stone-400">© {{ date('Y') }} Yeni Wedding · Tasikmalaya</footer>
</body>
</html>
