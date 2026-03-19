@props([
    'item',
])

@can('update', $item)
    <li class="pull-right">
    <a href="#" data-toggle="modal" data-target="#uploadFileModal" data-tooltip="true" data-placement="top" data-title="{{ trans('general.upload_files') }}">
        <x-icon type="paperclip" style="font-size: 16px"/>
    </a>
</li>
@endcan