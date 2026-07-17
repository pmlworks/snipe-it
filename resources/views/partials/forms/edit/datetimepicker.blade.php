<!-- Datetimepicker -->
<div class="form-group{{ $errors->has($fieldname) ? ' has-error' : '' }}">
    <label for="{{ $fieldname }}" class="col-md-3 control-label">{{ $translated_name }}</label>
    <div class="input-group col-md-4">
        <x-input.datetimepicker
                name="{{ $fieldname }}"
                value="{{ old($fieldname, ($item->{$fieldname}) ? date('Y-m-d H:i:s', strtotime($item->{$fieldname})) : '') }}"
                required="{{ Helper::checkIfRequired($item, $fieldname) }}"
        />
        <x-form.error :name="$fieldname" />
    </div>
    @if (isset($help_text))
        <div class="col-md-8 col-md-offset-3">
            <p class="help-block">
                {!! $help_text !!}
            </p>
        </div>
    @endif
</div>

<!-- /Datetimepicker -->
