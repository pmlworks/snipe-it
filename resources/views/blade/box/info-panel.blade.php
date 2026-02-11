@props([
    'contact' => null,
    'img_path' => null,
])



<div class="box-header with-border">
    <h3 class="box-title side-box-header">
        {{ $contact->display_name }}
    </h3>

    <div class="box-tools pull-right">
        <button type="button" class="btn btn-box-tool">

        </button>
    </div>
</div>

<div class="box-body box-profile side-box expanded">


    @if (($contact->image) && ($img_path))
            <a href="{{ Storage::disk('public')->url($img_path.e($contact->image)) }}" data-toggle="lightbox" data-type="image">
                <img src="{{ Storage::disk('public')->url($img_path.e($contact->image)) }}" class="profile-user-img img-responsive img-thumbnail" alt="{{ $contact->name }}" style="margin-bottom: 10px;">
            </a>
        <br>
    @endif


    @if ($contact->present()->displayAddress)
        {!! nl2br($contact->present()->displayAddress) !!}
        <br><br>
    @endif


    @if (isset($before_list))
        {{ $before_list }}
    @endif

    <ul class="list-group list-group-unbordered">


        {{ $slot }}

        <x-info-element icon_type="notes" title="{{ trans('general.notes') }}">
            {!! nl2br(Helper::parseEscapedMarkedownInline($contact->notes)) !!}
        </x-info-element>

        @if ($contact->serial)
            @can('viewKeys', $contact)
                <x-info-element>
                    <x-copy-to-clipboard copy_what="license_key">
                        <code>{{ $contact->serial }}</code>
                    </x-copy-to-clipboard>
                </x-info-element>
            @else
                ------------
            @endcan
        @endif

        @if ($contact->license_name)
            <x-info-element icon_type="contact-card" title="{{ trans('admin/licenses/form.to_name') }}">
                {{ trans('admin/licenses/form.to_name') }}
                {{ $contact->license_name }}
            </x-info-element>
        @endif

        @if ($contact->license_email)
            <x-info-element icon_type="email" title="{{ trans('admin/licenses/form.to_email') }}">
                {{ trans('admin/licenses/form.to_email') }}
                <x-info-element.email>
                    {{ $contact->license_email }}
                </x-info-element.email>
            </x-info-element>
        @endif

        @if ($contact->termination_date)
            <x-info-element icon_type="terminates" title="{{ trans('general.termination_date') }}">
                {{ Helper::getFormattedDateObject($contact->termination_date, 'date', false) }}
            </x-info-element>
        @endif

        @if ($contact->expiration_date)
            <x-info-element icon_type="expiration" title="{{ trans('general.expires') }}">
                {{ Helper::getFormattedDateObject($contact->expiration_date, 'date', false) }}
            </x-info-element>
        @endif

        @if ($contact->model_number)
            <x-info-element icon_type="number" title="{{ trans('general.model_number') }}">
                {{ $contact->model_number }}
            </x-info-element>
        @endif

        @if ($contact->order_number)
            <x-info-element icon_type="order" title="{{ trans('general.order_number') }}">
                {{ $contact->order_number }}
            </x-info-element>
        @endif

        @if ($contact->purchase_order)
            <x-info-element icon_type="purchase_order" title="{{ trans('admin/licenses/form.purchase_order') }}">
                {{ $contact->purchase_order }}
            </x-info-element>
        @endif

        @if (function_exists('numRemaining'))
            <x-info-element icon_type="available" title="{{ trans('general.remaining') }}">
                {{ $contact->numRemaining() }}
                {{ trans('general.remaining') }}
            </x-info-element>

            <x-info-element icon_type="checkedout" title="{{ trans('general.available') }}">
                {{ $contact->checkouts_count }}
                {{ trans('general.checked_out') }}
            </x-info-element>
        @endif


        @if ($contact->company)
            <x-info-element icon_type="company" icon_color="{{ $contact->company->tag_color }}" title="{{ trans('general.company') }}">
                {!!  $contact->company->present()->nameUrl !!}
            </x-info-element>
        @endif

        @if ($contact->category)
            <x-info-element icon_type="category" icon_color="{{ $contact->category->tag_color }}" title="{{ trans('general.category') }}">
                {!!  $contact->category->present()->nameUrl !!}
            </x-info-element>
        @endif

        @if ($contact->location)
            <x-info-element icon_type="location" icon_color="{{ $contact->location->tag_color }}" title="{{ trans('general.location') }}">
                {!!  $contact->location->present()->nameUrl !!}
            </x-info-element>
        @endif


        @if ($contact->manager)
            <x-info-element icon_type="manager" title="{{ trans('admin/users/table.manager') }}">
                {!!  $contact->manager->present()->nameUrl !!}
            </x-info-element>
        @endif


        @if ($contact->fieldset)
            <x-info-element icon_type="fieldset" title="{{ trans('general.fieldset') }}">
                {!!  $contact->fieldset->present()->nameUrl !!}
            </x-info-element>
        @endif

        @if ($contact->manufacturer)
            <x-info-element icon_type="manufacturer" title="{{ trans('general.manufacturer') }}">
                <strong>{{ trans('general.manufacturer') }}</strong>
            </x-info-element>

            <x-info-element class="subitem">
                {!!  $contact->manufacturer->present()->formattedNameLink !!}
            </x-info-element>

            <x-info-element icon_type="phone" class="subitem" title="{{ trans('general.phone') }}">
                <x-info-element.phone>
                    {{ $contact->manufacturer->support_phone }}
                </x-info-element.phone>
            </x-info-element>

            <x-info-element icon_type="email" class="subitem" title="{{ trans('general.email') }}">
                <x-info-element.email>
                    {{ $contact->manufacturer->support_email }}
                </x-info-element.email>
            </x-info-element>

            <x-info-element icon_type="external-link" class="subitem" title="{{ trans('general.url') }}">
                <x-info-element.url>
                    {{ $contact->manufacturer->url }}
                </x-info-element.url>
            </x-info-element>

            <x-info-element icon_type="external-link" class="subitem" title="{{ trans('general.url') }}">
                <x-info-element.url>
                    {{ $contact->manufacturer->support_url }}
                </x-info-element.url>
            </x-info-element>
        @endif


        @if ($contact->supplier)
            <x-info-element icon_type="manufacturer" title="{{ trans('general.supplier') }}">
                <strong>{{ trans('general.supplier') }}</strong>
            </x-info-element>

            <x-info-element class="subitem">
                {!!  $contact->supplier->present()->formattedNameLink !!}
            </x-info-element>

            <x-info-element icon_type="contact-card" class="subitem" title="{{ trans('admin/suppliers/table.contact') }}">
                {{ $contact->supplier->contact }}
            </x-info-element>

            @if ($contact->supplier->present()->displayAddress)
                <x-info-element class="subitem">
                    {!! nl2br($contact->supplier->present()->displayAddress) !!}
                </x-info-element>
            @endif

            <x-info-element icon_type="phone" class="subitem" title="{{ trans('general.phone') }}">
                <x-info-element.phone title="{{ trans('general.phone') }}">
                    {{ $contact->supplier->phone }}
                </x-info-element.phone>
            </x-info-element>

            <x-info-element icon_type="email" class="subitem" title="{{ trans('general.email') }}">
                <x-info-element.email>
                    {{ $contact->supplier->email }}
                </x-info-element.email>
            </x-info-element>

            <x-info-element icon_type="external-link" class="subitem" title="{{ trans('general.url') }}">
                <x-info-element.url>
                    {{ $contact->supplier->url }}
                </x-info-element.url>
            </x-info-element>

        @endif



        @if ($contact->parent)
            <x-info-element icon_type="parent" title="{{ trans('admin/locations/table.parent') }}">
                {{ $contact->parent->display_name }}
            </x-info-element>
        @endif

        @if ($contact->depreciation && $contact->purchase_date)
            <x-info-element icon_type="depreciation" title="{{ trans('general.depreciation') }}">
                {!!  $contact->depreciation->present()->nameUrl !!}
                ({{ $contact->depreciation->months.' '.trans('general.months')}})
            </x-info-element>

            <x-info-element icon_type="depreciation-calendar" title="{{ trans('general.depreciates') }}">
                {{ Helper::getFormattedDateObject($contact->depreciated_date(), 'date', false) }}
            </x-info-element>
        @endif

        @if ($contact->eol)
            <x-info-element icon_type="eol" title="{{ trans('general.eol') }}">
                {{ $contact->eol .' '.trans('general.months') }}
            </x-info-element>
        @endif


        <x-info-element icon_type="email" title="{{ trans('general.email') }}">
            <x-info-element.email title="{{ trans('general.email') }}">
                {{ $contact->email }}
            </x-info-element.email>
        </x-info-element>

        @if ($contact->phone)
            <x-info-element icon_type="phone" title="{{ trans('general.phone') }}">
                <x-info-element.phone>
                    {{ $contact->phone }}
                </x-info-element.phone>
            </x-info-element>
        @endif

        @if ($contact->fax)
            <x-info-element icon_type="fax" title="{{ trans('general.fax') }}">
                <x-info-element.phone>
                    {{ $contact->fax }}
                </x-info-element.phone>
            </x-info-element>
        @endif

        <x-info-element icon_type="external-link" title="{{ trans('general.url') }}">
            <x-info-element.url>
                {{ $contact->url }}
            </x-info-element.url>
        </x-info-element>

        <x-info-element icon_type="external-link" title="{{ trans('admin/manufacturers/table.support_url') }}">
            <x-info-element.url>
                {{ $contact->support_url }}
            </x-info-element.url>
        </x-info-element>


        @if (($contact->present()->displayAddress) && (config('services.google.maps_api_key')))

                <x-info-element>
                    <div class="text-center">
                        <img src="https://maps.googleapis.com/maps/api/staticmap?markers={{ urlencode($contact->address.','.$contact->city.' '.$contact->state.' '.$contact->country.' '.$contact->zip) }}&size=500x300&maptype=roadmap&key={{ config('services.google.maps_api_key') }}" class="img-thumbnail img-responsive" style="width: 100%" alt="Map">
                    </div>
                </x-info-element>
        @endif

        @if ((($contact->address!='') && ($contact->city!='')) || ($contact->state!='') || ($contact->country!=''))
            <x-info-element>
                <a class="btn btn-sm btn-theme" href="https://maps.google.com/?q={{ urlencode($contact->address.','. $contact->city.','.$contact->state.','.$contact->country.','.$contact->zip) }}" target="_blank">
                    {!! trans('admin/locations/message.open_map', ['map_provider_icon' => '<i class="fa-brands fa-google" aria-hidden="true"></i>']) !!}
                    <x-icon type="external-link"/>
                </a>

                <a class="btn btn-sm btn-theme"  href="https://maps.apple.com/?q={{ urlencode($contact->address.','. $contact->city.','.$contact->state.','.$contact->country.','.$contact->zip) }}" target="_blank">
                    {!! trans('admin/locations/message.open_map', ['map_provider_icon' => '<i class="fa-brands fa-apple" aria-hidden="true"></i>']) !!}
                    <x-icon type="external-link"/>
                </a>
            </x-info-element>
        @endif

        @if ($contact->months)
            <x-info-element title="{{ trans('general.months') }}">
                {{ $contact->months }}
                {{ trans('general.months') }}
            </x-info-element>
        @endif

        @if ($contact->depreciation_type)
            <x-info-element title="{{ trans('general.depreciation_type') }}">
                @if ($contact->depreciation_type == 'amount')
                    {{ trans('general.depreciation_options.amount') }}
                @elseif ($contact->depreciation_type == 'percent')
                    {{ trans('general.depreciation_options.amount') }}
                @endif
            </x-info-element>
        @endif

        @if ($contact->purchase_cost)
            <x-info-element>
                <x-icon type="cost" class="fa-fw" title="{{ trans('general.purchase_cost') }}" />
                {{ Helper::formatCurrencyOutput($contact->purchase_cost) }}
            </x-info-element>
        @endif


        @if ($contact->purchase_date)
            <x-info-element>
                <x-icon type="calendar" class="fa-fw" title="{{ trans('general.purchase_date') }}" />
                {{ trans('general.purchased_plain') }}
                {{ Helper::getFormattedDateObject($contact->purchase_date, 'datetime', false) }}
            </x-info-element>
        @endif


        @if ($contact->maintained)
            <x-info-element title="{{ trans('general.maintained') }}">
            @if ($contact->maintained == 1)
                <x-icon type="checkmark" class="fa-fw text-success" />
                {{ trans('admin/licenses/form.maintained') }}
            @else
                <x-icon type="x" class="fa-fw text-danger" />
                {{ trans('admin/licenses/form.maintained') }}
            @endif
            </x-info-element>
        @endif

        @if ($contact->reassignable)
            <x-info-element title="{{ trans('admin/licenses/form.reassignable') }}">
            @if ($contact->reassignable == 1)
                <x-icon type="checkmark" class="fa-fw text-success" />
                {{ trans('admin/licenses/form.reassignable') }}
            @else
                <x-icon type="x" class="text-danger" />
                {{ trans('admin/licenses/form.reassignable') }}
            @endif
            </x-info-element>
        @endif

        @if ($contact->requestable)
            <x-info-element title="{{ trans('general.requestable') }}">
            @if ($contact->requestable == 1)
                <x-icon type="checkmark" class="fa-fw text-success" />
               {{ trans('admin/hardware/general.requestable') }}
            @else
                <x-icon type="x" class="fa-fw text-danger" />
                {{ trans('admin/hardware/general.requestable') }}
            @endif
            </x-info-element>
        @endif



        @if ($contact->adminuser)
            <x-info-element title="{{ trans('general.created_by') }}">
                <span class="text-muted">
                    <x-icon type="user" class="fa-fw" title="{{ trans('general.created_by') }}" />
                        {{ trans('general.created_by') }}
                    @can('view', $contact->adminuser)
                        <a href="{{ route('users.show', $contact->adminuser) }}"> {{ $contact->adminuser->display_name }}</a>
                    @else
                        {{ $contact->adminuser->display_name }}
                    @endcan

                </span>
            </x-info-element>
        @endif


        @if ($contact->created_at)
            <x-info-element>
                <span class="text-muted">
                    <x-icon type="calendar" class="fa-fw" title="{{ trans('general.created_at') }}" />
                    {{ trans('general.created_plain') }}
                    {{ Helper::getFormattedDateObject($contact->created_at, 'datetime', false) }}
                </span>
            </x-info-element>
        @endif

        @if ($contact->updated_at)
            <x-info-element>
                <span class="text-muted">
                    <x-icon type="calendar" class="fa-fw" title="{{ trans('general.updated_at') }}" />
                    {{ trans('general.updated_plain') }}
                    {{ Helper::getFormattedDateObject($contact->updated_at, 'datetime', false) }}
                </span>
            </x-info-element>
        @endif

        @if ($contact->deleted_at)
            <x-info-element>
                <span class="text-muted">
                    <x-icon type="deleted-date" class="fa-fw" title="{{ trans('general.deleted_at') }}" />
                    {{ trans('general.deleted_plain') }}
                    {{ Helper::getFormattedDateObject($contact->deleted_at, 'datetime', false) }}
                </span>
            </x-info-element>
        @endif


    </ul>
    @if (isset($after_list))
        {{ $after_list }}
    @endif

</div>



