<!-- form-row blade component -->
@props([
    'name' => null,
    'type' => 'text',
    'item' => null,
    'info_tooltip_text' => null,
    'help_text' => null,
    'help_icon' => null,
    'label' => null,
    'input_div_class' => 'col-md-8',
    'errors_class' => $errors->has('support_url') ? ' has-error' : '',
    'input_icon' => null,
    'input_group_addon' => null,
    'maxlength' => null,
    'min' => null,
    'max' => null,
    'rows' => null,
    'placeholder' => null,
])

<div {{ $attributes->merge(['class' => 'form-group'. $errors_class]) }}>

    <!-- form label -->
    @if (isset($label))
        <x-form.label  :for="$name" class="{{ $label_class ?? 'col-md-3' }}">{{ $label }}</x-form.label>
    @endif


    @php
        $blade_type = in_array($type, ['text', 'email', 'url', 'tel', 'number', 'password']) ? 'text' : $type;

        // The vast majority of string columns in this schema are varchar(191),
        // so default text-family inputs to that length when the caller didn't
        // pass one explicitly. Textareas and numbers opt out — textareas often
        // back TEXT columns with no length limit, and browsers ignore
        // maxlength on type="number" anyway. Callers can still override with
        // an explicit :maxlength="..."
        $effective_maxlength = $maxlength ?? (in_array($type, ['text', 'email', 'url', 'tel', 'password']) ? 191 : null);
    @endphp

        <div class="{{ $input_div_class }}">
            {{-- You can pass an <x-slot:input>...</x-slot:input> when the
                 field needs custom markup (e.g. an input plus a select side
                 by side, or a widget the input.* components don't cover).
                 The wrapping label + error + help + aria still come from
                 <x-form.row>, so only the input area is hand-authored. --}}
            @isset($input)
                {{ $input }}
            @else
                <x-dynamic-component
                    :$name
                    :$type
                    :aria-label="$name"
                    :aria-describedby="$help_text ? $name.'-help' : null"
                    :component="'input.'.$blade_type"
                    :id="$name"
                    :required="Helper::checkIfRequired($item, $name)"
                    :value="old($name, $item->{$name})"
                    :input_icon="$input_icon"
                    :input_group_addon="$input_group_addon"
                    :maxlength="$effective_maxlength"
                    :min="$min"
                    :max="$max"
                    :rows="$rows"
                    :placeholder="$placeholder"

                />
            @endisset
        </div>

    @if ($info_tooltip_text)
        <!-- Info Tooltip -->
        <div class="col-md-1 text-left" style="padding-left:0; margin-top: 5px;">
            <x-form.tooltip>
                {{ $info_tooltip_text }}
            </x-form.tooltip>
        </div>
    @endif


    {{-- Error + help live in a single col-md-8 col-md-offset-3 wrapper so
         they always span the wide-column area regardless of how narrow the
         input above is. --}}
    <div class="col-md-8 col-md-offset-3">
        <x-form.error :name="$name" />

        @if ($help_text)
            <x-form.help :name="$name" :icon="$help_icon">{!! $help_text !!}</x-form.help>
        @endif
    </div>

</div>
