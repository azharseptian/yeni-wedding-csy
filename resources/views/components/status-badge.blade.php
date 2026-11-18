@props(['status'])
@php
    $classes = match ($status) {
        \App\Enums\BookingStatus::Penawaran => 'bg-stone-100 text-stone-700 ring-stone-300',
        \App\Enums\BookingStatus::Terkonfirmasi => 'bg-sky-50 text-sky-700 ring-sky-300',
        \App\Enums\BookingStatus::BelumMemenuhiAmbang => 'bg-amber-50 text-amber-800 ring-amber-400',
        \App\Enums\BookingStatus::SiapPelaksanaan => 'bg-violet-50 text-violet-700 ring-violet-300',
        \App\Enums\BookingStatus::Lunas => 'bg-emerald-50 text-emerald-700 ring-emerald-300',
        \App\Enums\BookingStatus::BatalRefund => 'bg-red-50 text-red-700 ring-red-300',
    };
@endphp
<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset {$classes}"]) }}>{{ $status->label() }}</span>
