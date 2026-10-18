@props([
    'align' => 'left',
])

@php
$alignClasses = [
    'left' => 'text-left',
    'center' => 'text-center',
    'right' => 'text-right',
];
@endphp

<td {{ $attributes->merge(['class' => 'px-6 py-4 text-slate-900 ' . $alignClasses[$align]]) }}>
    {{ $slot }}
</td>
