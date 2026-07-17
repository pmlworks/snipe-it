@props([
    'type' => 'info',
    'icon' => null,
    'live' => 'polite',
])
<div {{ $attributes->merge(['class' => 'callout callout-'.$type]) }} role="alert" aria-live="{{ $live }}" aria-atomic="true">

        @if ($icon)
            <x-icon :type="$icon" />
        @endif
        {{ $slot }}

</div>
