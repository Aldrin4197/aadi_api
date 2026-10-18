@props([
    'align' => 'right',
])

@php
$alignClasses = [
    'left' => 'left-0',
    'right' => 'right-0',
];
@endphp

<div 
    x-data="{ open: false }" 
    @click.away="open = false"
    {{ $attributes->merge(['class' => 'relative inline-block']) }}
>
    {{-- Trigger --}}
    <div @click="open = !open">
        {{ $trigger }}
    </div>
    
    {{-- Dropdown Menu --}}
    <div 
        x-show="open"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        x-cloak
        class="absolute {{ $alignClasses[$align] }} mt-2 w-56 rounded-lg bg-white shadow-lg border border-slate-200 py-1 z-50"
        style="display: none;"
    >
        {{ $slot }}
    </div>
</div>
