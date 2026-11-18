@props(['booking'])
@php
    $persen = min(100, $booking->persentaseDibayar());
    $ambang = $booking->ambang_pelunasan_persen;
    $warna = $persen >= 100 ? 'bg-emerald-500' : ($persen >= $ambang ? 'bg-violet-500' : 'bg-brand-500');
@endphp
<div {{ $attributes }}>
    <div class="relative h-2.5 w-full overflow-hidden rounded-full bg-stone-200">
        <div class="h-full {{ $warna }}" style="width: {{ $persen }}%"></div>
        <div class="absolute inset-y-0 w-0.5 bg-stone-700/60" style="left: {{ $ambang }}%" title="Ambang {{ $ambang }}%"></div>
    </div>
    <div class="mt-1 flex justify-between text-xs text-stone-500">
        <span>{{ rupiah($booking->total_dibayar) }} dari {{ rupiah($booking->total_tagihan) }} ({{ number_format($persen, 1, ',', '.') }}%)</span>
        <span>Ambang {{ $ambang }}%</span>
    </div>
</div>
