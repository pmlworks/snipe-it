@props([
    'box_style' => 'default',
    'header' => false,
    'footer' => false,
])
@aware(['name'])


<!-- Start box component -->
<div class="box box-{{ $box_style }}">

    @if ($header)
    <x-box.header>
        {{ $header }}
    </x-box.header>
    @endif

    <div class="box-body">

        @if (isset($bulkactions))
            <div id="{{ Illuminate\Support\Str::camel($name) }}ToolBar" class="pull-left" style="min-width:500px !important; padding-top: 10px;">
                {{ $bulkactions }}
            </div>
        @endif

        {{ $slot }}
    </div>

    @if ($footer)
        <x-box.footer>
        {{ $footer }}
        </x-box.footer>
    @endif
</div>