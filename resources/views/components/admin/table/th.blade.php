@props([
    'sortable' => false,
    'align' => 'left',
])

@php
$alignClasses = [
    'left' => 'text-left',
    'center' => 'text-center',
    'right' => 'text-right',
];
@endphp

<th {{ $attributes->merge(['class' => 'px-6 py-3 text-xs font-semibold text-slate-700 uppercase tracking-wider border-b border-slate-200 ' . $alignClasses[$align]]) }}>
    @if($sortable)
        <button type="button" class="inline-flex items-center gap-1 hover:text-[#1FA6A0] transition-colors">
            {{ $slot }}
            <x-icon name="chevron-down" class="w-3 h-3" />
        </button>
    @else
        {{ $slot }}
    @endif
</th>
