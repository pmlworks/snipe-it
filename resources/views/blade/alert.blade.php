@props([
    'type' => 'info',
    'icon' => null,
    'title' => null,
])
<div {{ $attributes->merge(['class' => 'alert alert-'.$type.' fade in']) }} role="alert">
    @if ($icon)
        <x-icon :type="$icon" />
    @endif
    @if ($title)
        <strong>{{ $title }}:</strong>
    @endif
    {{ $slot }}
</div>
