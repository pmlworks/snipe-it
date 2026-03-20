@props([
    'label',
    'copy_what' => null,
    'icon_type' => null,
])

@if (!$slot->isEmpty())
    <dt>
        @if (isset($icon_type))
            <x-icon type="{{ $icon_type }}" class="fa-fw"/>
        @endif
        {{ $label }}
    </dt>
    <dd>
        @if ($copy_what!='')
            <x-copy-to-clipboard copy_what="{{ $copy_what }}">{{ $slot }}</x-copy-to-clipboard>
        @else
            {{ $slot }}
        @endif
    </dd>
@endif