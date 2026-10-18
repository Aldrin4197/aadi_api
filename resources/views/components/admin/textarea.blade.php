@props([
    'label',
    'name',
    'value' => '',
    'placeholder' => '',
    'rows' => 4,
    'required' => false,
    'error' => null,
    'help' => null,
])

<div {{ $attributes->merge(['class' => 'space-y-1.5']) }}>
    @if($label)
        <label for="{{ $name }}" class="block text-sm font-medium text-slate-700">
            {{ $label }}
            @if($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif
    
    <textarea
        id="{{ $name }}"
        name="{{ $name }}"
        rows="{{ $rows }}"
        placeholder="{{ $placeholder }}"
        {{ $required ? 'required' : '' }}
        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-[#1FA6A0] focus:border-[#1FA6A0] transition-colors duration-200 {{ $error ? 'border-red-500' : 'border-slate-300' }}"
    >{{ $value }}</textarea>
    
    @if($error)
        <p class="text-sm text-red-600">{{ $error }}</p>
    @endif
    
    @if($help && !$error)
        <p class="text-sm text-slate-500">{{ $help }}</p>
    @endif
</div>
