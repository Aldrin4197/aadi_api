@props([
    'label',
    'value',
    'icon' => 'bar-chart',
    'color' => 'teal', // Default to Aadi teal
    'sub' => null,
])

@php
$colorClasses = [
    'teal' => 'bg-[#E3F6F4] text-[#1FA6A0]', // Aadi primary
    'orange' => 'bg-[#FCE6CE] text-[#F59E42]', // Aadi accent
    'blue' => 'bg-blue-100 text-blue-600',
    'indigo' => 'bg-indigo-100 text-indigo-600',
    'violet' => 'bg-violet-100 text-violet-600',
    'amber' => 'bg-amber-100 text-amber-600',
    'emerald' => 'bg-emerald-100 text-emerald-600',
    'red' => 'bg-red-100 text-red-600',
];
@endphp

<div {{ $attributes->merge(['class' => 'bg-white rounded-lg shadow-xs border border-slate-200 p-6']) }}>
    <div class="flex items-center justify-between">
        <div class="flex-1">
            <p class="text-sm font-medium text-slate-600">{{ $label }}</p>
            <p class="mt-2 text-3xl font-bold text-slate-900">
                {{ is_numeric($value) ? number_format($value) : $value }}
            </p>
            @if($sub)
                <p class="mt-2 text-sm text-slate-500">{{ $sub }}</p>
            @endif
        </div>
        <div class="{{ $colorClasses[$color] }} rounded-lg p-3">
            <x-icon :name="$icon" class="w-8 h-8" />
        </div>
    </div>
</div>
