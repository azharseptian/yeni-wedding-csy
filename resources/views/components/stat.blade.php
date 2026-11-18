@props(['label', 'value', 'hint' => null, 'tone' => 'stone'])
<div {{ $attributes->merge(['class' => 'card']) }}>
    <p class="text-sm text-stone-500">{{ $label }}</p>
    <p class="mt-1 text-2xl font-bold text-{{ $tone }}-700">{{ $value }}</p>
    @if ($hint)<p class="mt-1 text-xs text-stone-500">{{ $hint }}</p>@endif
</div>
