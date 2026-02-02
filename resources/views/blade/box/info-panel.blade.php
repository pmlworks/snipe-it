@props([
    'contact' => null,
    'img_path' => null,
])

<div class="box-body box-profile">

    <h3 class="profile-username">
    @if (($contact->image) && ($img_path))
            <a href="{{ Storage::disk('public')->url($img_path.e($contact->image)) }}" data-toggle="lightbox" data-type="image">
                <img src="{{ Storage::disk('public')->url($img_path.e($contact->image)) }}" class="profile-user-img img-responsive img-thumbnail" alt="{{ $contact->name }}">
            </a>
    @endif


        {{ $contact->display_name }}
    </h3>

        @if ($contact->present()->displayAddress)
            {!! nl2br($contact->present()->displayAddress) !!}
            <br><br>
        @endif


    @if (isset($before_list))
        {{ $before_list }}
    @endif

    <ul class="list-group list-group-unbordered">

        {{ $slot }}

        <x-info-element icon_type="notes">
            {!! nl2br(Helper::parseEscapedMarkedownInline($contact->notes)) !!}
        </x-info-element>

        @if ($contact->company)
            <x-info-element icon_type="company">
                @can('view', $contact->company)
                    <a href="{{ route('companies.show', $contact->company) }}">
                        {{ $contact->company->display_name }}
                    </a>
                @else
                    {{ $contact->company->display_name }}
                @endcan
            </x-info-element>
        @endif

        @if ($contact->category)
            <x-info-element icon_type="category">
                @can('view', $contact->category)
                    <a href="{{ route('categories.show', $contact->category) }}">
                        {{ $contact->category->display_name }}
                    </a>
                @else
                    {{ $contact->category->display_name }}
                @endcan
            </x-info-element>
        @endif

        <x-info-element icon_type="contact-card">
            {{ $contact->contact }}
        </x-info-element>

        @if ($contact->manager)
            <x-info-element icon_type="manager">
                @can('view', $contact->manager)
                    <a href="{{ route('users.show', $contact->manager) }}">
                        {{ $contact->manager->display_name }}
                    </a>
                @else
                    {{ $contact->manager->display_name }}
                @endcan
            </x-info-element>
        @endif


        @if ($contact->model_number)
            <x-info-element icon_type="number">
                {{ $contact->model_number }}
            </x-info-element>
        @endif

        @if ($contact->fieldset)
            <x-info-element icon_type="fieldset">
                @can('view', $contact->fieldset)
                    <a href="{{ route('fieldsets.show', $contact->fieldset) }}">
                        {{ $contact->fieldset->name }}
                    </a>
                @else
                    {{ $contact->fieldset->name }}
                @endcan
            </x-info-element>
        @endif

        @if ($contact->manufacturer)
            <x-info-element icon_type="manufacturer">
                @can('view', $contact->manufacturer)
                    <a href="{{ route('manufacturers.show', $contact->manufacturer) }}">
                        {{ $contact->manufacturer->name }}
                    </a>
                @else
                    {{ $contact->manufacturer->name }}
                @endcan


            </x-info-element>

            <x-info-element icon_type="contact-card">
                {{ $contact->manufacturer->contact }}
            </x-info-element>

            <x-info-element icon_type="phone">
                <x-info-element.phone>
                    {{ $contact->manufacturer->support_phone }}
                </x-info-element.phone>
            </x-info-element>

            <x-info-element icon_type="email">
                <x-info-element.email>
                    {{ $contact->manufacturer->support_email }}
                </x-info-element.email>
            </x-info-element>

            <x-info-element icon_type="external-link">
                <x-info-element.url>
                    {{ $contact->manufacturer->url }}
                </x-info-element.url>
            </x-info-element>

            <x-info-element icon_type="external-link">
                <x-info-element.url>
                    {{ $contact->manufacturer->support_url }}
                </x-info-element.url>
            </x-info-element>

        @endif

        @if ($contact->parent)
            <x-info-element icon_type="parent">
                {{ $contact->parent->display_name }}
            </x-info-element>
        @endif

        @if ($contact->depreciation)
            <x-info-element icon_type="depreciation">
                @can('view', $contact->fieldset)
                    <a href="{{ route('depreciations.show', $contact->depreciation) }}">{{ $contact->depreciation->display_name }}</a>
                @else
                    {{ $contact->depreciation->display_name }}
                @endif

                ({{ $contact->depreciation->months.' '.trans('general.months')}})
            </x-info-element>
        @endif

        @if ($contact->eol)
            <x-info-element icon_type="eol">
                {{ $contact->eol .' '.trans('general.months') }}
            </x-info-element>
        @endif


        <x-info-element icon_type="email">
            <x-info-element.email>
                {{ $contact->email }}
            </x-info-element.email>
        </x-info-element>

        @if ($contact->phone)
            <x-info-element icon_type="phone">
                <x-info-element.phone>
                    {{ $contact->phone }}
                </x-info-element.phone>
            </x-info-element>
        @endif

        @if ($contact->fax)
            <x-info-element icon_type="fax">
                <x-info-element.phone>
                    {{ $contact->fax }}
                </x-info-element.phone>
            </x-info-element>
        @endif

        <x-info-element icon_type="external-link">
            <x-info-element.url>
                {{ $contact->url }}
            </x-info-element.url>
        </x-info-element>

        <x-info-element icon_type="external-link">
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

        @if ($contact->purchase_cost)
            <x-info-element>
                <x-icon type="cost" class="fa-fw" />
                {{ Helper::formatCurrencyOutput($contact->purchase_cost) }}
            </x-info-element>
        @endif


        @if ($contact->purchase_date)
            <x-info-element>
                <x-icon type="calendar" class="fa-fw" />
                {{ trans('general.purchased_plain') }}
                {{ Helper::getFormattedDateObject($contact->purchase_date, 'datetime', false) }}
            </x-info-element>
        @endif


        @if ($contact->created_by)
            <x-info-element>
                <span class="text-muted">
                    <x-icon type="user" class="fa-fw" />

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
                    <x-icon type="calendar" class="fa-fw" />
                    {{ trans('general.created_plain') }}
                    {{ Helper::getFormattedDateObject($contact->created_at, 'datetime', false) }}
                </span>
            </x-info-element>
        @endif

        @if ($contact->updated_at)
            <x-info-element>
                <span class="text-muted">
                    <x-icon type="calendar" class="fa-fw" />
                    {{ trans('general.updated_plain') }}
                    {{ Helper::getFormattedDateObject($contact->updated_at, 'datetime', false) }}
                </span>
            </x-info-element>
        @endif

        @if ($contact->deleted_at)
            <x-info-element>
                <span class="text-muted">
                    <x-icon type="deleted-date" class="fa-fw" />
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



