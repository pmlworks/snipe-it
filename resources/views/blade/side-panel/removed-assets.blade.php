@props([
    'items',
    'message',
    'title' => trans('general.notification_warning'),
    // On bulk-checkout the checkout-target-panel Livewire component takes
    // over the right column once the user picks a target, so this warning
    // is only useful in the "picking assets, no target yet" phase — pass
    // hideOnTargetSelected=true and the small script at the bottom will
    // hide the box the moment a target select gets a value.
    'hideOnTargetSelected' => false,
])

{{-- Warning side-panel used by bulk checkin/checkout to explain which assets
     were pulled from the selection (because they were already assigned on
     checkout, or already unassigned on checkin) and can't be included in
     the operation. Only renders when the caller has at least one item to
     show. Component intentionally doesn't set a col-md-* on its root so the
     caller can wrap it in whatever column width fits their page layout.
     Table layout mirrors the sibling checkout-target-panel so the two side
     panels read consistently when stacked in the same column. --}}
@if ($items->isNotEmpty())
    <div class="box box-warning" id="removed-assets-warning">
        <div class="box-header with-border">
            <h2 class="box-title">
                <x-icon type="warning" /> {{ $title }}
            </h2>
        </div>
        <div class="box-body">
            <p>{{ $message }}</p>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th scope="col"></th>
                        <th scope="col">{{ trans('general.name') }}</th>
                        <th scope="col">{{ trans('admin/hardware/form.tag') }}</th>
                        <th scope="col">{{ trans('admin/hardware/form.serial') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($items as $item)
                        <tr>
                            <td>
                                @if ($item->image_url ?? null)
                                    <img src="{{ $item->image_url }}" style="max-height: {{ $snipeSettings->thumbnail_max_h }}px; width: auto;" alt="">
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('hardware.show', $item->id) }}">
                                    {{ $item->name }}
                                </a>
                            </td>
                            <td>{{ $item->asset_tag }}</td>
                            <td>{{ $item->serial }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @if ($hideOnTargetSelected)
        <script nonce="{{ csrf_token() }}">
            (function () {
                // Hide the removed-assets warning as soon as the user picks a
                // checkout target — the checkout-target-panel Livewire component
                // becomes the primary side-column content at that point and the
                // stale warning would just eat vertical space. We piggyback on
                // the same target selects the panel's bridge script watches so
                // there's no coordination between the two.
                var $warning = $('#removed-assets-warning');
                if (!$warning.length) return;

                var syncVisibility = function () {
                    var radioVal = $('input[name="checkout_to_type"]:checked').val() || 'user';
                    var selectSelectors = {
                        'user': '#assigned_user_select',
                        'asset': '#assigned_asset_select',
                        'location': '#assigned_location_location_select',
                    };
                    var val = $(selectSelectors[radioVal]).val();
                    var hasTarget = val !== null && val !== undefined && val !== '';
                    $warning.toggle(!hasTarget);
                };

                $('#assigned_user_select, #assigned_asset_select, #assigned_location_location_select')
                    .on('change', syncVisibility);
                $('input[name="checkout_to_type"]').on('change', syncVisibility);

                // Run once on ready so a server-side pre-selected target
                // (session-remembered after a validation redirect) hides the
                // warning immediately instead of flashing it on the first paint.
                syncVisibility();
            })();
        </script>
    @endif
@endif
