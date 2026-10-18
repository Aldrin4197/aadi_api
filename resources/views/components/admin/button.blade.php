@props([
    'variant' => 'primary',
    'size' => 'md',
    'icon' => null,
    'href' => null,
    'disabled' => false,
])

@php
$variantClasses = [
    'primary' => 'bg-[#1FA6A0] hover:bg-[#1A8F8A] text-white shadow-sm', // Aadi teal
    'secondary' => 'bg-slate-600 hover:bg-slate-700 text-white shadow-sm',
    'success' => 'bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm',
    'danger' => 'bg-red-600 hover:bg-red-700 text-white shadow-sm',
    'warning' => 'bg-amber-500 hover:bg-amber-600 text-white shadow-sm',
    'outline' => 'bg-white hover:bg-slate-50 text-slate-700 border border-slate-300',
    'ghost' => 'hover:bg-slate-100 text-slate-700',
    'orange' => 'bg-[#F59E42] hover:bg-[#D9822B] text-white shadow-sm', // Aadi accent
];

$sizeClasses = [
    'xs' => 'px-2.5 py-1 text-xs',
    'sm' => 'px-3 py-1.5 text-sm',
    'md' => 'px-4 py-2 text-sm',
    'lg' => 'px-5 py-2.5 text-base',
    'xl' => 'px-6 py-3 text-base',
];

$classes = ($disabled ? 'opacity-50 cursor-not-allowed' : '') . ' ' . $variantClasses[$variant] . ' ' . $sizeClasses[$size];
@endphp

@if($href)
    <a 
        href="{{ $href }}" 
        {{ $attributes->merge(['class' => 'inline-flex items-center gap-2 font-medium rounded-lg transition-colors duration-200 ' . $classes]) }}
    >
        @if($icon)
            <x-icon :name="$icon" class="w-4 h-4" />
        @endif
        {{ $slot }}
    </a>
@else
    <button 
        type="button" 
        {{ $disabled ? 'disabled' : '' }}
        {{ $attributes->merge(['class' => 'inline-flex items-center gap-2 font-medium rounded-lg transition-colors duration-200 ' . $classes]) }}
    >
        @if($icon)
            <x-icon :name="$icon" class="w-4 h-4" />
        @endif
        {{ $slot }}
    </button>
@endif
