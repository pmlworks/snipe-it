@props([
    'name' => false,
    'label' => false,
    'count' => 0,
    'icon' => false,
    'icon_style' => false,
    'tooltip' => false,
])
<!-- start tab nav item -->
<li {{ $attributes->merge(['class' => '']) }}>
    <a href="#{{ $name ?? 'info' }}" data-toggle="tab"{!! ($tooltip) ? ' data-tooltip="true" title="'.$tooltip.'"' : '' !!}>

        @if ($icon)

            <span class="hidden-lg hidden-md">
                 <i class="{{ $icon }}" style="font-size: 18px" aria-hidden="true"></i>
            </span>

            <span class="hidden-xs hidden-sm">
                 <i class="{{ $icon }}" style="font-size: 16px" aria-hidden="true"></i>
            </span>

            <span class="sr-only">
            {{ $label }}
        </span>

        @elseif ($label)
            {{ $label }}
        @endif



        @if ($count > 0)
            <span class="badge">{{ number_format($count) }}</span>
        @endif

    </a>
</li>
<!-- end tab nav item -->