@extends('layouts/edit-form', [
    'createText' => trans('admin/groups/titles.create') ,
    'updateText' => trans('admin/groups/titles.update'),
    'item' => $group,
    'formAction' => ($group !== null && $group->id !== null) ? route('groups.update', ['group' => $group->id]) : route('groups.store'),
    'container_classes' => 'col-lg-6 col-lg-offset-3 col-md-10 col-md-offset-1 col-sm-12 col-sm-offset-0',
    'topSubmit' => 'true',
])
@section('content')



@parent
@stop

@section('inputFields')

<!-- Name -->
<div class="form-group {{ $errors->has('name') ? ' has-error' : '' }}">
    <label for="name" class="col-md-3 control-label">{{ trans('admin/groups/titles.group_name') }}</label>
    <div class="col-md-8 required">
        <input class="form-control" type="text" name="name" id="name" value="{{ old('name', $group->name) }}" required />
        {!! $errors->first('name', '<span class="alert-msg" aria-hidden="true"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
    </div>
</div>

<div class="form-group{!! $errors->has('notes') ? ' has-error' : '' !!}">
    <label for="notes" class="col-md-3 control-label">{{ trans('general.notes') }}</label>
    <div class="col-md-8">
        <x-input.textarea
                name="notes"
                id="notes"
                :value="old('notes', $group->notes)"
                placeholder="{{ trans('general.placeholders.notes') }}"
                aria-label="notes"
                rows="2"
        />

        {!! $errors->first('notes', '<span class="alert-msg" aria-hidden="true"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
    </div>
</div>


<div class="form-group{{ $errors->has('associated_users') ? ' has-error' : '' }}">

    <label for="associated_users[]" class="col-md-3 control-label">
        {{ trans('general.users') }}
    </label>

    <div class="col-md-7">
        <select class="js-data-ajax"
                data-endpoint="users"
                data-placeholder="{{ trans('general.select_user') }}"
                name="associated_users[]"
                style="width: 100%"
                id="associated_users[]"
                aria-label="associated_users[]"  multiple>

                <option value=""  role="option">{{ trans('general.select_user') }}</option>
                @if(isset($associated_users))
                    @foreach($associated_users as $associated_user)
                        <option value="{{ $associated_user->id }}" selected="selected" role="option" aria-selected="true"
                                role="option">
                            {{ $associated_user->present()->fullName }} ({{ $associated_user->username }})
                        </option>
                    @endforeach
                @endif
        </select>
    </div>

    {!! $errors->first('associated_users', '<div class="col-md-8 col-md-offset-3"><span class="alert-msg" aria-hidden="true"><i class="fas fa-times" aria-hidden="true"></i> :message</span></div>') !!}

</div>

<style>
    .slider-wrapper {
        display: flex;
        padding: 2px;
        background-color: #e9e9e9;
        margin-bottom: 3px;
        border-radius: 4px;
        border: 1px #d6d6d6 solid;
    }

    .custom-input {
        flex-grow: 1;
    }

    .custom-input input[type=radio] {
        display: none;
    }

    .custom-input label {
        display: block;
        margin-bottom: 0px;
        padding: 6px 8px;
        color: #fff;
        font-weight: bold;
        text-align: center;
        transition : all .4s 0s ease;
        cursor: pointer;
    }

    .custom-input label {
        color: #9a9999;
        border-radius: 4px;
    }

    .custom-input .allow:checked + label {
        background-color: green;
        color: white;
        border-radius: 4px;
    }

    .custom-input .deny:checked + label {
        background-color: #a94442;
        color: white;
        border-radius: 4px;
    }

    .custom-input .allow:checked + label {
        background-color: green;
        color: white;
        border-radius: 4px;
    }
</style>

<div class="col-md-12">
    @foreach ($permissions as $area => $area_permission)
        <!-- handle superadmin and reports, and anything else with only one option -->
        @php
            $localPermission = $area_permission[0];
        @endphp
        <div class="form-group{{ ($localPermission['permission']!='superuser') ? ' nonsuperuser' : '' }}{{ ( ($localPermission['permission']!='superuser') && ($localPermission['permission']!='admin')) ? ' nonadmin' : '' }}">
            <div class="callout callout-legend col-md-12">
                <div class="col-md-10">
                    <h4>
                        {{ trans('permissions.'.str_slug($area).'.name') }}
                    </h4>

                    @if (\Lang::has('permissions.'.str_slug($area).'.note'))
                        <p>{{ trans('permissions.'.str_slug($area).'.note') }}</p>
                    @endif


                </div>

                <!-- Handle the checkall ALLOW and DENY radios -->
                <div class="col-md-2 text-right header-row">
                    <div class="slider-wrapper">
                        <div class="custom-input"{!! (count($area_permission) > 1) ? ' data-tooltip="true" title="Grant All Permissions for '.$area.'"' : '' !!}>
                                <input
                                        class="form-control {{ str_slug($area) }} allow"
                                        data-checker-group="{{ str_slug($area) }}"
                                        aria-label="{{ str_slug($area) }}"
                                        name="permission[{{ str_slug($area) }}]"
                                        @checked(array_key_exists(str_slug($area), $groupPermissions) && $groupPermissions[str_slug($area)] == '1')
                                        type="radio"
                                        value="1"
                                        id="{{ str_slug($area) }}_allow"
                                >

                            <label class="allow" for="{{ str_slug($area) }}_allow">
                                <i class="fa-solid fa-square-check"></i>
                            </label>

                        </div>
                        <div class="custom-input"{!! (count($area_permission) > 1) ? ' data-tooltip="true" title="Deny All for '.$area.'"' : '' !!}>

                                <input
                                        class="form-control  {{ str_slug($area) }} deny"
                                        data-checker-group="{{ str_slug($area) }}"
                                        aria-label="{{ str_slug($area) }}"
                                        name="permission[{{ str_slug($area) }}]"
                                        @checked(array_key_exists(str_slug($area), $groupPermissions) && $groupPermissions[str_slug($area)] == '0')
                                        type="radio"
                                        value="0"
                                        id="{{ str_slug($area) }}_deny"
                                >

                            <label class="deny" for="{{ str_slug($area) }}_deny">
                                <i class="fa-solid fa-square-xmark"></i>
                            </label>

                        </div>
                    </div>
                </div>

            </div>
        </div>
            @if (count($area_permission) > 1)

                @foreach ($area_permission as $index => $this_permission)
                    @if ($this_permission['display'])

                    <div class="{{ ($localPermission['permission']!='superuser') ? ' nonsuperuser' : '' }}{{ ( ($localPermission['permission']!='superuser') && ($localPermission['permission']!='admin')) ? ' nonadmin' : '' }}">
                    <div class="form-group" style="border-bottom: 1px solid #eee; padding-right: 9px;">
                        <div class="col-md-10">
                            <strong>{{ trans('permissions.'.str_slug($this_permission['permission']).'.name') }}</strong>
                            @if (\Lang::has('permissions.'.str_slug($this_permission['permission']).'.note'))
                                <p>{{ trans('permissions.'.str_slug($this_permission['permission']).'.note') }}</p>
                            @endif
                        </div>

                        <div class="form-group col-md-2 text-right">
                            <div class="slider-wrapper">
                                <div class="custom-input" data-tooltip="true" title="Allow">
                                        <input
                                                class="radiochecker-{{ str_slug($area) }} allow"
                                                aria-label="permission[{{ $this_permission['permission'] }}]"
                                                @checked(array_key_exists($this_permission['permission'], $groupPermissions) && $groupPermissions[$this_permission['permission']] == '1')
                                                name="permission[{{ $this_permission['permission'] }}]"
                                                type="radio"
                                                id="{{ str_slug($this_permission['permission']) }}_allow"
                                                value="1"
                                        >
                                    <label for="{{ str_slug($this_permission['permission']) }}_allow" class="allow">
                                        <i class="fa-solid fa-square-check"></i>
                                    </label>
                                </div>
                                <div class="custom-input" data-tooltip="true" title="Deny">
                                    <input
                                            class="form-control deny radiochecker-{{ str_slug($area) }}"
                                            aria-label="permission[{{ $this_permission['permission'] }}]"
                                            @checked(array_key_exists($this_permission['permission'], $groupPermissions) && $groupPermissions[$this_permission['permission']] == '0')
                                            name="permission[{{ $this_permission['permission'] }}]"
                                            type="radio"
                                            value="0"
                                            id="{{ str_slug($this_permission['permission']) }}_deny"
                                    >
                                    <label for="{{ str_slug($this_permission['permission']) }}_deny">
                                        <i class="fa-solid fa-square-xmark"></i>
                                    </label>
                                </div>
                            </div>
                        </div>


                    </div>
                    </div>
                   @endif
                @endforeach
           @endif

    @endforeach
</div>

@stop
@section('moar_scripts')

    <script nonce="{{ csrf_token() }}">

        $(document).ready(function(){

            if ($("#superuser_allow").is(':checked')) {
                // alert('superuser is checked on page load');

                // Hide here instead of fadeout on pageload to prevent what looks like Flash Of Unstyled Content (FOUC)
                $(".nonsuperuser").hide();
                $(".nonsuperuser").attr('display','none');
            }


            $(".superuser").change(function() {
                if ($(this).val() == '1') {
                    $(".nonsuperuser").fadeOut();
                    $(".nonsuperuser").attr('display','none');
                    $(".nonadmin").fadeOut();
                    $(".nonadmin").attr('display','none');
                } else if ($(this).val() == '0') {
                    $(".nonsuperuser").fadeIn();
                    $(".nonsuperuser").attr('display','block');
                }
            });



            if ($("#admin_allow").is(':checked')) {
                // alert('admin is checked on page load');

                // Hide here instead of fadeout on pageload to prevent what looks like Flash Of Unstyled Content (FOUC)
                $(".nonadmin").hide();
                $(".nonadmin").attr('display','none');
            }

            $(".admin").change(function() {
                if ($(this).val() == '1') {
                    $(".nonadmin").fadeOut();
                    $(".nonadmin").attr('display','none');
                } else if ($(this).val() == '0') {
                    $(".nonadmin").fadeIn();
                    $(".nonadmin").attr('display','block');
                }
            });


            // Check/Uncheck all radio buttons in the group
            $('.header-row input:radio').change(function() {
                value = $(this).attr('value');
                area = $(this).data('checker-group');
                $('.radiochecker-'+area+'[value='+value+']').prop('checked', true);
            });


        });


    </script>
@stop
