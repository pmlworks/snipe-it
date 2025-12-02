@props([
    'item' => null,
    'name' => 'color',
    'id' => 'color',
    'div_id' => null,
    'placeholder'=> 'FF0000',
])

<!-- Colorpicker -->
<div {{ $attributes->merge(['class' => 'color input-group colorpicker-component row col-md-5']) }} id="{{ $div_id }}">
    <input class="form-control" placeholder="{{ $placeholder }}" aria-label="{{ $name }}" name="{{ $name }}" type="text" id="{{ $id }}" value="{{ old($name, ($item->{$name} ?? '')) }}">
    <span class="input-group-addon"><i> </i></span>
</div>

<!-- /.input group -->
