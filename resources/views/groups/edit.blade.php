@extends('layouts/edit-form', [
    'createText' => trans('admin/groups/titles.create') ,
    'updateText' => trans('admin/groups/titles.update'),
    'item' => $group,
    'formAction' => ($group !== null && $group->id !== null) ? route('groups.update', ['group' => $group->id]) : route('groups.store'),

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



<div class="col-md-12">
    <div class="col-md-10">
        <h4>{{ trans('admin/groups/titles.permission')}}</h4>
    </div>
    <div class="col-md-1 text-right">
        <h4>{{ trans('admin/groups/titles.grant')}}</h4>
    </div>
    <div class="col-md-1 text-right">
        <h4>{{ trans('admin/groups/titles.deny')}}</h4>
    </div>
</div>

<div class="col-md-12">
    @foreach ($permissions as $area => $area_permission)
        <!-- handle superadmin and reports, and anything else with only one option -->
        <?php $localPermission = $area_permission[0]; ?>
        <div class="form-group{{ ($localPermission['permission']!='superuser') ? ' nonsuperuser' : '' }}{{ ( ($localPermission['permission']!='superuser') && ($localPermission['permission']!='admin')) ? ' nonadmin' : '' }}">
            <div class="callout callout-legend col-md-12">
                <div class="col-md-10">
                    <h4>
                        {{ $area }}
                    </h4>

                    @if ($localPermission['note']!='')
                        <p>{{ $localPermission['note'] }}</p>
                    @endif

                </div>

                <!-- Handle the checkall ALLOW radios -->
                <div class="col-md-1 text-right header-row">
                    <label for="{{ $area }}">
                        <input
                                class="form-control {{ str_slug($area) }}"
                                data-checker-group="{{ str_slug($area) }}"
                                aria-label="{{ $area }}"
                                name="permission[{{ str_slug($area) }}]"
                                @checked(array_key_exists(str_slug($area), $groupPermissions) && $groupPermissions[str_slug($area)] == '1')
                                type="radio"
                                value="1"
                        >

                            <span class="sr-only">
                                {{ trans('admin/groups/titles.allow')}}
                                {{ $area }}
                            </span>
                    </label>

                </div>

                <!-- Handle the checkall DENY radios -->
                <div class="col-md-1 header-row text-right">
                    <label>
                        <input
                                class="form-control  {{ str_slug($area) }}"
                                data-checker-group="{{ str_slug($area) }}"
                                aria-label="{{ $area }}"
                                name="permission[{{ str_slug($area) }}]"
                                type="radio"
                                value="0"
                        >
                        <span class="sr-only">
                            {{ trans('admin/groups/titles.deny')}}
                            {{ $area }}
                        </span>
                    </label>

                </div>
            </div>
        </div>
            @if (count($area_permission) > 1)

                @foreach ($area_permission as $index => $this_permission)
                    @if ($this_permission['display'])

                    <div class="{{ ($localPermission['permission']!='superuser') ? ' nonsuperuser' : '' }}{{ ( ($localPermission['permission']!='superuser') && ($localPermission['permission']!='admin')) ? ' nonadmin' : '' }}">
                    <div class="form-group" style="border-bottom: 1px solid #eee; margin-right: 15px;">
                        <div class="col-md-10">
                            {{ $this_permission['label'] }}
                        </div>

                        <div class="col-md-1 text-right">
                            <label for="{{ 'permission['.$this_permission['permission'].']' }}">
                                <input
                                        class="form-control radiochecker-{{ str_slug($area) }}"
                                        aria-label="permission[{{ $this_permission['permission'] }}]"
                                        @checked(array_key_exists($this_permission['permission'], $groupPermissions) && $groupPermissions[$this_permission['permission']] == '1')
                                        name="permission[{{ $this_permission['permission'] }}]"
                                        type="radio"
                                        value="1"
                                >

                                <span class="sr-only">{{ trans('admin/groups/titles.allow')}}
                                    {{ 'permission['.$this_permission['permission'].']' }}
                                </span>
                            </label>

                        </div>

                        <div class="col-md-1 text-right">
                            <label for="{{ 'permission['.$this_permission['permission'].']' }}">
                            <input
                                class="form-control radiochecker-{{ str_slug($area) }}"
                                aria-label="permission[{{ $this_permission['permission'] }}]"
                                @checked(array_key_exists($this_permission['permission'], $groupPermissions) && $groupPermissions[$this_permission['permission']] == '0')
                                name="permission[{{ $this_permission['permission'] }}]"
                                type="radio"
                                value="0"
                            >
                            </label>
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

            if ($("input[name='permission[superuser]']").is(':checked')) {
                alert('superuser is checked on page load');
                $(".nonsuperuser").fadeOut();
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

            if ($("input[name='permission[admin]']").is(':checked')) {
                alert('admin is checked on page load');
                $(".nonadmin").fadeOut();
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
