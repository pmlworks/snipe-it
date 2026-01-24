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
            <li class="list-group-item">
                <i class="fa fa-note-sticky"></i>
                {!! nl2br(Helper::parseEscapedMarkedownInline($contact->notes)) !!}
            </li>
        @endif
    </ul>
</div>


@if (($contact->address!='') && ($contact->state!='') && ($contact->country!='') && (config('services.google.maps_api_key')))
    <div class="col-md-12 text-center" style="padding-bottom: 20px;">
        <img src="https://maps.googleapis.com/maps/api/staticmap?markers={{ urlencode($contact->address.','.$contact->city.' '.$contact->state.' '.$contact->country.' '.$contact->zip) }}&size=500x300&maptype=roadmap&key={{ config('services.google.maps_api_key') }}" class="img-responsive img-thumbnail" alt="Map">
    </div>
@endif
