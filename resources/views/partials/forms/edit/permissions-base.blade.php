<style>
  .radio-toggle-wrapper {
    display: flex;
    padding: 2px;
    background-color: #e9e9e9;
    margin-bottom: 3px;
    border-radius: 4px;
    border: 1px #d6d6d6 solid;
  }

  .radio-slider-inputs {
    flex-grow: 1;
  }

  .radio-slider-inputs input[type=radio] {
    display: none;
  }

  .radio-slider-inputs label {
    display: block;
    margin-bottom: 0px;
    padding: 6px 8px;
    color: #fff;
    font-weight: bold;
    text-align: center;
    transition : all .4s 0s ease;
    cursor: pointer;
  }

  .radio-slider-inputs label {
    color: #9a9999;
    border-radius: 4px;
    border: 1px transparent solid;
  }

  .radio-slider-inputs .allow:checked + label {
    background-color: green;
    color: white;
    border-radius: 4px;
    border: 1px transparent solid;
  }

  .radio-slider-inputs .inherit:checked + label {
    background-color: #f6f5f5;
    color: #9a9999;
    border-radius: 4px;
    border: 1px #d6d6d6 solid;
  }

  .radio-slider-inputs .deny:checked + label {
    background-color: #a94442;
    color: white;
    border-radius: 4px;
    border: 1px transparent solid;
  }

</style>


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
        <div class="radio-toggle-wrapper">
          <div class="radio-slider-inputs" data-tooltip="true" title="{{ (count($area_permission) > 1) ? trans('permissions.grant_all', ['area' => $area])  : trans('permissions.grant', ['area' => $area]) }}">
            <input
                    class="form-control {{ str_slug($area) }} allow"
                    data-checker-group="{{ str_slug($area) }}"
                    aria-label="{{ str_slug($area) }}"
                    name="permission[{{ str_slug($area) }}]"
                    @checked(array_key_exists(str_slug($area), $groupPermissions) && $groupPermissions[str_slug($area)] == '1')
                    type="radio"
                    value="1"
                    {{-- Disable the superuser and admin allow if the user is not a superuser --}}
                    @if (((str_slug($area) == 'admin') && (!auth()->user()->hasAccess('admin'))) || ((str_slug($area) == 'superuser') && (!auth()->user()->isSuperUser())))
                      disabled
                    @endif
                    id="{{ str_slug($area) }}_allow"
            >

            <label class="allow" for="{{ str_slug($area) }}_allow">
              <i class="fa-solid fa-square-check"></i>
            </label>
          </div>


          @if ($use_inherit)
            <div class="radio-slider-inputs"data-tooltip="true" title="{{ (count($area_permission) > 1) ? trans('permissions.inherit_all', ['area' => $area])  : trans('permissions.inherit', ['area' => $area]) }}">
              <input
                      class="form-control  {{ str_slug($area) }} inherit"
                      data-checker-group="{{ str_slug($area) }}"
                      aria-label="{{ str_slug($area) }}"
                      name="permission[{{ str_slug($area) }}]"
                      @checked(array_key_exists(str_slug($area), $groupPermissions) && $groupPermissions[str_slug($area)] == '-1')
                      type="radio"
                      value="-1"
                      {{-- Disable the superuser and admin allow if the user is not a superuser --}}
                      @if (((str_slug($area) == 'admin') && (!auth()->user()->hasAccess('admin'))) || ((str_slug($area) == 'superuser') && (!auth()->user()->isSuperUser())))
                        disabled
                      @endif
                      id="{{ str_slug($area) }}_inherit"
              >

              <label class="inherit" for="{{ str_slug($area) }}_inherit">
                <i class="fa-solid fa-layer-group"></i>
              </label>
            </div>

          @endif

          <div class="radio-slider-inputs" data-tooltip="true" title="{{ (count($area_permission) > 1) ? trans('permissions.deny_all', ['area' => $area])  : trans('permissions.deny', ['area' => $area]) }}">
            <input
                    class="form-control  {{ str_slug($area) }} deny"
                    data-checker-group="{{ str_slug($area) }}"
                    aria-label="{{ str_slug($area) }}"
                    name="permission[{{ str_slug($area) }}]"
                    @checked(array_key_exists(str_slug($area), $groupPermissions) && $groupPermissions[str_slug($area)] == '0')
                    type="radio"
                    value="0"
                    {{-- Disable the superuser and admin allow if the user is not a superuser --}}
                    @if (((str_slug($area) == 'admin') && (!auth()->user()->hasAccess('admin'))) || ((str_slug($area) == 'superuser') && (!auth()->user()->isSuperUser())))
                      disabled
                    @endif
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
        @php
            $section_translation = trans('permissions.'.str_slug($this_permission['permission']).'.name');
        @endphp
        <div class="{{ ($localPermission['permission']!='superuser') ? ' nonsuperuser' : '' }}{{ ( ($localPermission['permission']!='superuser') && ($localPermission['permission']!='admin')) ? ' nonadmin' : '' }}">
          <div class="form-group" style="border-bottom: 1px solid #eee; padding-right: 9px;">
            <div class="col-md-10">
              <strong>{{ $section_translation }}</strong>
              @if (\Lang::has('permissions.'.str_slug($this_permission['permission']).'.note'))
                <p>{{ trans('permissions.'.str_slug($this_permission['permission']).'.note') }}</p>
              @endif
            </div>

            <div class="form-group col-md-2 text-right">
              <div class="radio-toggle-wrapper">
                <div class="radio-slider-inputs" data-tooltip="true" title="{{ trans('permissions.grant', ['area' => $section_translation]) }}">
                  <input
                          class="form-control allow radiochecker-{{ str_slug($area) }}"
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

                @if ($use_inherit)
                <div class="radio-slider-inputs" data-tooltip="true" title="{{ trans('permissions.inherit', ['area' => $section_translation]) }}">
                  <input
                          class="form-control inherit radiochecker-{{ str_slug($area) }}"
                          aria-label="permission[{{ $this_permission['permission'] }}]"
                          @checked(array_key_exists($this_permission['permission'], $groupPermissions) && $groupPermissions[$this_permission['permission']] == '-1')
                          name="permission[{{ $this_permission['permission'] }}]"
                          type="radio"
                          id="{{ str_slug($this_permission['permission']) }}_inherit"
                          value="-1"
                  >
                  <label for="{{ str_slug($this_permission['permission']) }}_inherit" class="inherit">
                    <i class="fa-solid fa-layer-group"></i>
                  </label>
                </div>
                @endif

                <div class="radio-slider-inputs" data-tooltip="true" title="{{ trans('permissions.deny', ['area' => $section_translation]) }}">
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


