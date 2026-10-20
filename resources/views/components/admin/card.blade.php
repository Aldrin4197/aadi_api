@props([
    'title' => null,
    'padding' => true,
])

<div {{ $attributes->merge(['class' => 'bg-white rounded-lg shadow-xs border border-slate-200']) }}>
    @if($title || isset($actions))
        <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
            @if($title)
                <h3 class="text-lg font-semibold text-slate-900">{{ $title }}</h3>
            @endif
            
            @if(isset($actions))
                <div class="flex items-center gap-2">
                    {{ $actions }}
                </div>
            @endif
        </div>
    @endif
    
    <div class="{{ $padding ? 'p-6' : '' }}">
        {{ $slot }}
    </div>
</div>
