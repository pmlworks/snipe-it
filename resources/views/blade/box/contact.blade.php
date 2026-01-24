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

    <ul class="list-group list-group-unbordered">

        {{ $slot }}


        @if ($contact->notes)
            <x-info-element>
                <i class="fa fa-note-sticky fa-fw"></i>
                {!! nl2br(Helper::parseEscapedMarkedownInline($contact->notes)) !!}
            </x-info-element>
        @endif


        @if ($contact->created_by)
            <x-info-element>
                <span class="text-muted">
                    <x-icon type="user" class="fa-fw" />
                    {{ trans('general.created_by') }}
                    {{ $contact->adminuser->display_name }}
                </span>
            </x-info-element>
        @endif


        @if ($contact->created_at)
            <x-info-element>
                <span class="text-muted">
                    <x-icon type="calendar" class="fa-fw" />
                    {{ trans('general.created_plain') }}
                    {{ $contact->created_at }}
                </span>
            </x-info-element>
        @endif

        @if ($contact->updated_at)
            <x-info-element>
                <span class="text-muted">
                    <x-icon type="calendar" class="fa-fw" />
                    {{ trans('general.updated_plain') }}
                    {{ $contact->updated_at }}
                </span>
            </x-info-element>
        @endif
    </ul>

    @if (($contact->address!='') && ($contact->state!='') && ($contact->country!='') && (config('services.google.maps_api_key')))
        <x-info-element>
            <img src="https://maps.googleapis.com/maps/api/staticmap?markers={{ urlencode($contact->address.','.$contact->city.' '.$contact->state.' '.$contact->country.' '.$contact->zip) }}&size=500x300&maptype=roadmap&key={{ config('services.google.maps_api_key') }}" class="img-responsive" alt="Map">
        </x-info-element>
    @endif

</div>



