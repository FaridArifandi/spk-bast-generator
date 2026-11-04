@props(['name', 'label' => null, 'required' => false, 'hint' => null, 'kosong' => null])
@php($fid = $attributes->get('id', $name))
<div class="field">
    @if($label)<label for="{{ $fid }}">{{ $label }} @if($required)<span class="req">*</span>@endif</label>@endif
    <select name="{{ $name }}" {{ $attributes->merge(['id' => $name]) }} @required($required)>
        @if($kosong !== null)<option value="">{{ $kosong }}</option>@endif
        {{ $slot }}
    </select>
    @if($hint)<small class="hint">{{ $hint }}</small>@endif
    @error($name)<div class="err">{{ $message }}</div>@enderror
</div>
