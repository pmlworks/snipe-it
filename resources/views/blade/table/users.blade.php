@props([
    'route' => null,
    'name' => 'default',
    'presenter' => \App\Presenters\UserPresenter::dataTableLayout(),
    'fixed_right_number' => 1,
    'fixed_number' => 2,
    'table_header' => trans('general.users'),
])

<!-- start assets tab pane -->
@can('view', \App\Models\User::class)
    <x-slot:table_header>
        {{ $table_header }}
    </x-slot:table_header>

    <x-slot:bulkactions>
        @include('partials.users-bulk-actions')
    </x-slot:bulkactions>

    <x-slot:content>
        <x-table
            :$presenter
            :$fixed_right_number
            :$fixed_number
            show_column_search="true"
            show_advanced_search="true"
            buttons="userButtons"
            api_url="{{ $route }}"
            export_filename="export-users-{{ date('Y-m-d') }}"
        />
    </x-slot:content>

@endcan
<!-- end assets tab pane -->