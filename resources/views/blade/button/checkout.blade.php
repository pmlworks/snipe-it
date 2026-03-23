@props([
    'item' => null,
    'permission' => null,
    'route',
    'wide' => false,
])

@can('checkout', $item)
    @if ($item->showCheckoutButton($item))
        <a href="{{ $route  }}" class="btn btn-sm bg-maroon hidden-print" data-tooltip="true"  data-placement="top" data-title="{{ trans('general.checkout') }}">
            <x-icon type="checkout" class="fa-fw" />
            @if ($wide=='true')
                {{ trans('general.checkout') }}
            @endif
        </a>
    @endif
@endcan
