@props([
    'href' => '#',
    'active' => false,
    'icon' => null,
])

@php
$classes = $active 
    ? 'bg-[#1FA6A0] text-white' // Aadi teal
    : 'text-[#C4ECEA] hover:bg-[#0B3A38]';
@endphp

<a 
    href="{{ $href }}" 
    {{ $attributes->merge(['class' => 'flex items-center gap-3 px-4 py-3 rounded-lg transition-colors duration-200 ' . $classes]) }}
>
    @if($icon)
        <x-icon :name="$icon" class="w-5 h-5" />
    @endif
    <span class="font-medium">{{ $slot }}</span>
</a>
