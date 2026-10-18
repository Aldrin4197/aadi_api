@props([
    'variant' => 'default',
    'size' => 'md',
    'dot' => false,
])

@php
$variantClasses = [
    'default' => 'bg-slate-100 text-slate-700',
    'primary' => 'bg-[#C4ECEA] text-[#0B3A38]', // Aadi light teal
    'success' => 'bg-emerald-100 text-emerald-700',
    'danger' => 'bg-red-100 text-red-700',
    'warning' => 'bg-amber-100 text-amber-700',
    'info' => 'bg-blue-100 text-blue-700',
    'purple' => 'bg-purple-100 text-purple-700',
    'orange' => 'bg-[#FCE6CE] text-[#D9822B]', // Aadi accent
];

$sizeClasses = [
    'sm' => 'text-xs px-2 py-0.5',
    'md' => 'text-sm px-2.5 py-0.5',
    'lg' => 'text-base px-3 py-1',
];

$dotColors = [
    'default' => 'bg-slate-400',
    'primary' => 'bg-[#1FA6A0]', // Aadi teal
    'success' => 'bg-emerald-500',
    'danger' => 'bg-red-500',
    'warning' => 'bg-amber-500',
    'info' => 'bg-blue-500',
    'purple' => 'bg-purple-500',
    'orange' => 'bg-[#F59E42]', // Aadi accent
];

$classes = $variantClasses[$variant] . ' ' . $sizeClasses[$size];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 font-medium rounded-full ' . $classes]) }}>
    @if($dot)
        <span class="w-1.5 h-1.5 rounded-full {{ $dotColors[$variant] }}"></span>
    @endif
    {{ $slot }}
</span>
