@props([
    'title' => null,
])

<nav {{ $attributes->merge(['class' => 'bg-white border-b border-slate-200 px-6 py-4']) }}>
    <div class="flex items-center justify-between">
        <div>
            @if($title)
                <h1 class="text-2xl font-bold text-slate-900">{{ $title }}</h1>
            @else
                {{ $slot }}
            @endif
        </div>
        
        @if(isset($actions))
            <div class="flex items-center gap-3">
                {{ $actions }}
            </div>
        @endif
    </div>
</nav>
