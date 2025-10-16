<!-- form-row blade component -->
@props([
    'name' => null,
    'type' => 'text',
    'item' => null,
    'info_tooltip_text' => null,
    'help_text' => null,
    'label' => null,
])

<div {{ $attributes->merge(['class' => 'form-group']) }}>

    @if (isset($label))
        <x-form-label
                :for="$name"
                :style="$label_style ?? null"
                class="{{ $label_class ?? null }}"
        >
            {{ $label }}
        </x-form-label>
    @endif

    @php
        $blade_type = in_array($type, ['text', 'email', 'url', 'tel', 'number', 'password']) ? 'text' : $type;
    @endphp
        <div class="col-xs-12 col-sm-12 col-md-8 col-lg-6 col-xl-6">
            <x-dynamic-component
                    :aria-label="$name"
                    :component="'input.'.$blade_type"
                    :id="$name"
                    :required="Helper::checkIfRequired($item, $name)"
                    :value="old($name, $item->{$name})"
            />
        </div>

    @if ($info_tooltip_text)
        <!-- Info Tooltip -->
        <div class="col-md-1 text-left" style="padding-left:0; margin-top: 5px;">
            <x-form-tooltip>
                {{ $info_tooltip_text }}
            </x-form-tooltip>
        </div>
    @endif


    @error($name)
    <!-- Form Error -->
    <div {{ $attributes->merge(['class' => $error_offset_class]) }}>
                <span class="alert-msg" role="alert">
                    <i class="fas fa-times" aria-hidden="true"></i>
                    {{ $message }}
                </span>
    </div>
    @enderror

    @if ($help_text)
        <!-- Help Text -->
        <div {{ $attributes->merge(['class' => $error_offset_class]) }}>
            <p class="help-block">
                {!! $help_text !!}
            </p>
        </div>
    @endif

</div>