@props([
    'icon' => null,
    'href' => null,
    'variant' => 'default',
])

@php
$variantClasses = [
    'default' => 'text-slate-700 hover:bg-slate-50',
    'danger' => 'text-red-600 hover:bg-red-50',
];
@endphp

@if($href)
    <a 
        href="{{ $href }}"
        {{ $attributes->merge(['class' => 'flex items-center gap-3 px-4 py-2 text-sm transition-colors ' . $variantClasses[$variant]]) }}
    >
        @if($icon)
            <x-icon :name="$icon" class="w-4 h-4" />
        @endif
        {{ $slot }}
    </a>
@else
    <button 
        type="button"
        {{ $attributes->merge(['class' => 'w-full flex items-center gap-3 px-4 py-2 text-sm transition-colors ' . $variantClasses[$variant]]) }}
    >
        @if($icon)
            <x-icon :name="$icon" class="w-4 h-4" />
        @endif
        {{ $slot }}
    </button>
@endif
