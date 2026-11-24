@props(['name', 'label', 'type' => 'text', 'value' => null, 'required' => false, 'hint' => null])

<div class="field">
    <label for="{{ $name }}">{{ $label }}@if ($required) <span class="req">*</span>@endif</label>
    <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}"
           @if ($type !== 'password') value="{{ old($name, $value) }}" @endif
           @required($required) {{ $attributes->class(['invalid' => $errors->has($name)]) }}>
    @if ($hint)<small class="hint">{{ $hint }}</small>@endif
    @error($name)<small class="error">{{ $message }}</small>@enderror
</div>
