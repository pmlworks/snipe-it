@props([
    'value' => '',
    'required' => '',
    'format' => 'YYYY-MM-DD HH:mm:ss',
    'placeholder' => trans('general.select_datetime'),
    'col_size_class' => null,
])

<!-- Datetimepicker (eonasdan-bootstrap-datetimepicker) -->
<div class="input-group date {{ $col_size_class }}"
     data-provide="datetimepicker"
     data-format="{{ $format }}"
     data-locale="{{ auth()->user()?->locale ?? 'en' }}">
    <input type="text"
           placeholder="{{ $placeholder }}"
           value="{{ $value }}"
           {{ $attributes->merge(['class' => 'form-control']) }}
           {{ $required == '1' ? 'required' : '' }}>
    <span class="input-group-addon"><x-icon type="calendar" /></span>
</div>
