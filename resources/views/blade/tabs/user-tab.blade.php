@props([
    'count' => null,
    'name' => 'users',
])
@aware(['class'])

@can('view', \App\Models\User::class)
    <x-tabs.nav-item
        name="{{ $name }}"
            icon_type="users"
            label="{{ trans('general.users') }}"
            count="{{ $count }}"
            tooltip="{{ trans('general.users') }}"
    />
@endcan