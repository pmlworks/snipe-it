@props([
    'value' => '',
    'required' => '',
    'format' => 'YYYY-MM-DD HH:mm:ss',
    'placeholder' => trans('general.select_datetime'),
    'col_size_class' => null,
    // Whether an empty picker should auto-populate to the user's current
    // local datetime on open. Callers that render a picker where "now" is
    // not a safe default (user-defined custom fields, for example) can pass
    // :default_now="false" to opt out.
    'default_now' => true,
])

<!-- Datetimepicker (eonasdan-bootstrap-datetimepicker) -->
<div class="input-group date {{ $col_size_class }}"
     data-provide="datetimepicker"
     data-format="{{ $format }}"
     data-locale="{{ auth()->user()?->locale ?? 'en' }}"
     data-default-now="{{ $default_now ? 'true' : 'false' }}">
    <input type="text"
           placeholder="{{ $placeholder }}"
           value="{{ $value }}"
           {{ $attributes->merge(['class' => 'form-control']) }}
           {{ $required == '1' ? 'required' : '' }}>
    <span class="input-group-addon"><x-icon type="calendar" /></span>
</div>
