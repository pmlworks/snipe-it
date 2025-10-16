@props([
'input_style' => null,
'input_group_addon' => null,
'required' => false,
'item' => null,
])
<!-- input-text blade component -->
<input
        {{ $attributes->merge(['class' => 'form-control']) }}
        @required($required)
/>

