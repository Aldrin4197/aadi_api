@props([
    'icon' => 'inbox',
    'title' => 'No data found',
    'description' => null,
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center py-12 px-4']) }}>
    <div class="w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center mb-4">
        <x-icon :name="$icon" class="w-8 h-8 text-slate-400" />
    </div>
    <h3 class="text-lg font-semibold text-slate-900 mb-1">{{ $title }}</h3>
    @if($description)
        <p class="text-sm text-slate-600 text-center max-w-md mb-4">{{ $description }}</p>
    @endif
    @if(isset($action))
        <div class="mt-4">
            {{ $action }}
        </div>
    @endif
</div>
