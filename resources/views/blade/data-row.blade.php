@props([
    'label',
    'copy_what' => null,
])

@if (!$slot->isEmpty())
    <dt>
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