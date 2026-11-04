@props(['name', 'label' => null, 'type' => 'text', 'value' => null, 'required' => false, 'hint' => null])
@php($fid = $attributes->get('id', $name))
<div class="field">
    @if($label)<label for="{{ $fid }}">{{ $label }} @if($required)<span class="req">*</span>@endif</label>@endif
    <input type="{{ $type }}" name="{{ $name }}" value="{{ old($name, $value) }}" {{ $attributes->merge(['id' => $name]) }} @required($required)>
    @if($hint)<small class="hint">{{ $hint }}</small>@endif
    @error($name)<div class="err">{{ $message }}</div>@enderror
</div>
