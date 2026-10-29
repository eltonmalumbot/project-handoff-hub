@props(['name','label','value'=>'','type'=>'text','required'=>false,'hint'=>null,'options'=>null,'maxlength'=>null])
@php
$fieldValue=old($name,$value);
$fieldValue=is_array($fieldValue) ? implode(', ',array_filter($fieldValue,'is_scalar')) : (is_scalar($fieldValue) || $fieldValue===null ? $fieldValue : '');
@endphp
<div class="field {{ $type==='textarea' ? 'field-wide' : '' }}">
<label for="{{ $name }}">{{ $label }}@if($required)<span aria-hidden="true"> *</span>@endif</label>
@if($options)
<select name="{{ $name }}" id="{{ $name }}" @required($required) @if($errors->has($name)) aria-invalid="true" @endif aria-describedby="{{ $name }}-help">@foreach($options as $option)<option value="{{ $option }}" @selected($fieldValue===$option)>{{ $option }}</option>@endforeach</select>
@elseif($type==='textarea')
<textarea name="{{ $name }}" id="{{ $name }}" rows="4" maxlength="{{ $maxlength ?? 20000 }}" @required($required) @if($errors->has($name)) aria-invalid="true" @endif aria-describedby="{{ $name }}-help">{{ $fieldValue }}</textarea>
@else
<input type="{{ $type }}" name="{{ $name }}" id="{{ $name }}" value="{{ $fieldValue }}" @required($required) @if($maxlength) maxlength="{{ $maxlength }}" @endif @if($errors->has($name)) aria-invalid="true" @endif aria-describedby="{{ $name }}-help">
@endif
<small id="{{ $name }}-help">@error($name)<span class="field-error">{{ $message }}</span>@else{{ $hint }}@enderror</small>
</div>
