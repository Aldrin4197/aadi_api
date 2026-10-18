@props([
    'label',
    'name',
    'options' => [],
    'value' => '',
    'placeholder' => null,
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
    
    <select
        id="{{ $name }}"
        name="{{ $name }}"
        {{ $required ? 'required' : '' }}
        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-[#1FA6A0] focus:border-[#1FA6A0] transition-colors duration-200 {{ $error ? 'border-red-500' : 'border-slate-300' }}"
    >
        @if($placeholder)
            <option value="">{{ $placeholder }}</option>
        @endif
        
        @if(!empty($options))
            @foreach($options as $optionValue => $optionLabel)
                <option value="{{ $optionValue }}" {{ $value == $optionValue ? 'selected' : '' }}>
                    {{ $optionLabel }}
                </option>
            @endforeach
        @else
            {{ $slot }}
        @endif
    </select>
    
    @if($error)
        <p class="text-sm text-red-600">{{ $error }}</p>
    @endif
    
    @if($help && !$error)
        <p class="text-sm text-slate-500">{{ $help }}</p>
    @endif
</div>
