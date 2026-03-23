@props([
    'count' => null,
    'class' => false,
])

<x-tabs.nav-item
    :$class
    name="files"
    icon_type="files"
    label="{{ trans('general.eula') }}"
    count="{{ $count }}"
    tooltip="{{ trans('general.eula') }}"
/>