@props([
    'id' => 'modal',
    'title' => null,
    'size' => 'md',
    'show' => false,
])

@php
$sizeClasses = [
    'sm' => 'max-w-md',
    'md' => 'max-w-lg',
    'lg' => 'max-w-2xl',
    'xl' => 'max-w-4xl',
    'full' => 'max-w-7xl',
];
@endphp

<div 
    x-data="{ open: {{ $show ? 'true' : 'false' }} }"
    @open-modal.window="if ($event.detail === '{{ $id }}') open = true"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-50 overflow-y-auto"
    style="display: none;"
>
    {{-- Backdrop --}}
    <div 
        x-show="open"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="open = false"
        class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"
    ></div>
    
    {{-- Modal --}}
    <div class="flex min-h-screen items-center justify-center p-4">
        <div 
            x-show="open"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            @click.stop
            {{ $attributes->merge(['class' => 'relative w-full bg-white rounded-lg shadow-xl ' . $sizeClasses[$size]]) }}
        >
            {{-- Header --}}
            @if($title)
                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200">
                    <h3 class="text-lg font-semibold text-slate-900">{{ $title }}</h3>
                    <button 
                        @click="open = false" 
                        class="text-slate-400 hover:text-slate-600 transition-colors"
                    >
                        <x-icon name="x" class="w-5 h-5" />
                    </button>
                </div>
            @endif
            
            {{-- Body --}}
            <div class="px-6 py-4">
                {{ $slot }}
            </div>
            
            {{-- Footer --}}
            @if(isset($footer))
                <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-slate-200">
                    {{ $footer }}
                </div>
            @endif
        </div>
    </div>
</div>
