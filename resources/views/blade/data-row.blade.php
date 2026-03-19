@props([
    'label',
    'copy_what',
])

@if (!$slot->isEmpty())
    <dt>
        {{ $label }}
    </dt>
    <dd>
        <x-copy-to-clipboard copy_what="{{ $copy_what }}" class="pull-right">{{ $slot  }}</x-copy-to-clipboard>
    </dd>
@endif