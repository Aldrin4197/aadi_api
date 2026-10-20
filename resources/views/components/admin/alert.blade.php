@props([
    'type' => 'info',
])

@php
$types = [
    'success' => [
        'container' => 'bg-emerald-50 border-emerald-200',
        'icon' => 'check-circle',
        'iconColor' => 'text-emerald-600',
        'text' => 'text-emerald-800',
    ],
    'error' => [
        'container' => 'bg-red-50 border-red-200',
        'icon' => 'x-circle',
        'iconColor' => 'text-red-600',
        'text' => 'text-red-800',
    ],
    'warning' => [
        'container' => 'bg-amber-50 border-amber-200',
        'icon' => 'alert-circle',
        'iconColor' => 'text-amber-600',
        'text' => 'text-amber-800',
    ],
    'info' => [
        'container' => 'bg-[#E3F6F4] border-[#C4ECEA]', // Aadi teal
        'icon' => 'info',
        'iconColor' => 'text-[#1FA6A0]',
        'text' => 'text-[#0B3A38]',
    ],
];

$config = $types[$type];
@endphp

<div {{ $attributes->merge(['class' => 'flex items-start gap-3 p-4 rounded-lg border ' . $config['container']]) }}>
    <x-icon :name="$config['icon']" class="w-5 h-5 shrink-0 {{ $config['iconColor'] }}" />
    <div class="flex-1 {{ $config['text'] }}">
        {{ $slot }}
    </div>
</div>
