@props([
    'user' => null,
    'name' => null,
    'src' => null,
    'size' => 'md',
])

@php
$displayName = $name ?? ($user->name ?? 'User');
$imageSrc = $src ?? ($user->avatar_url ?? null);
$initials = collect(explode(' ', $displayName))->map(fn($word) => strtoupper($word[0]))->take(2)->join('');

$sizeClasses = [
    'xs' => 'w-6 h-6 text-xs',
    'sm' => 'w-8 h-8 text-sm',
    'md' => 'w-10 h-10 text-base',
    'lg' => 'w-12 h-12 text-lg',
    'xl' => 'w-16 h-16 text-xl',
    '2xl' => 'w-20 h-20 text-2xl',
];
@endphp

<div {{ $attributes->merge(['class' => 'relative inline-flex items-center justify-center rounded-full bg-gradient-to-br from-[#1FA6A0] to-[#0B3A38] text-white font-semibold overflow-hidden ' . $sizeClasses[$size]]) }}>
    @if($imageSrc)
        <img src="{{ $imageSrc }}" alt="{{ $displayName }}" class="w-full h-full object-cover">
    @else
        <span>{{ $initials }}</span>
    @endif
</div>
