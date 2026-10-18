@props([
    'hoverable' => true,
])

<tr {{ $attributes->merge(['class' => 'border-b border-slate-200 last:border-b-0 ' . ($hoverable ? 'hover:bg-slate-50 transition-colors' : '')]) }}>
    {{ $slot }}
</tr>
