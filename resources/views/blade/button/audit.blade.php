@props([
    'item' => null,
    'route' => null,
    'wide' => false,
])

@can('audit', $item)
    <span class="tooltip-wrapper"{!! (!$item->model ? ' data-tooltip="true" title="'.trans('admin/hardware/general.model_invalid_fix').'"' : '') !!}>
        <a href="{{ $route  }}" class="btn btn-sm btn-primary hidden-print{{ (!$item->model ? ' disabled' : '') }}" data-tooltip="true" title="{{ trans('general.audit') }}">
             <x-icon type="audit" class="fa-fw"/>
             @if ($wide=='true')
                {{ trans('general.audit') }}
            @endif
        </a>
    </span>
@endcan
