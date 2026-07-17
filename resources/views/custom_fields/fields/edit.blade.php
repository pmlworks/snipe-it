@extends('layouts/default', [
    'helpText' => trans('admin/custom_fields/general.about_fieldsets_text'),
    'helpPosition' => 'right',
])


{{-- Page title --}}
@section('title')
  {{ trans('admin/custom_fields/general.custom_fields') }}
@parent
@stop

{{-- Page content --}}
@section('content')

    <!-- Horizontal Form -->
    @if ($field->id)
        <form method="POST" action="{{ route('fields.update', $field->id) }}" accept-charset="UTF-8" class="form-horizontal">
        {{ method_field('PUT') }}
    @else
        <form method="POST" action="{{ route('fields.store') }}" accept-charset="UTF-8" class="form-horizontal">
    @endif

    @csrf
<div class="row">
  <div class="col-md-12">
    <div class="box box-default">
        <div class="box-header with-border text-right">
            <button type="submit" class="btn btn-primary"> {{ trans('general.save') }}</button>
        </div>
      <div class="box-body">

          <div class="col-md-8">

          <!-- Name -->
          <div class="form-group {{ $errors->has('name') ? ' has-error' : '' }}">
            <label for="name" class="col-md-3 control-label">
              {{ trans('admin/custom_fields/general.field_name') }}
            </label>
            <div class="col-md-8 required">
                <input class="form-control" aria-label="name" name="name" type="text" required value="{{ old('name', $field->name) }}">
                <x-form.error name="name" />
            </div>
          </div>

          @php
              $field_format = '';
              if (stripos($field->format, 'regex') === 0){
                $field_format = 'CUSTOM REGEX';
              }
              // DATE / DATETIME fields are backed by native DATE / DATETIME
              // columns on the assets table AND always render via the
              // datepicker widgets, so we lock BOTH the format dropdown and
              // the element dropdown once the field exists as DATE/DATETIME.
              $format_locked = $field->exists && in_array($field->format, ['DATE', 'DATETIME'], true);
              // When creating a new field, the format hasn't been chosen yet;
              // JS ($('.format').change) reveals/hides the element block based
              // on the selected format at runtime. Server-side we still
              // enforce (element = text) when saving with DATE/DATETIME.
          @endphp

          <!-- Format -->
          <div class="form-group {{ $errors->has('format') ? ' has-error' : '' }}" id="format_values">
            <label for="format" class="col-md-3 control-label">
              {{ trans('admin/custom_fields/general.field_format') }}
            </label>
            <div class="col-md-8 required">
                <x-input.select
                    name="format"
                    :options="Helper::predefined_formats()"
                    :selected="($field_format == '') ? $field->format : $field_format"
                    class="format form-control"
                    style="width:100%"
                    aria-label="format"
                    :disabled="$format_locked"
                />
                @if ($format_locked)
                    <input type="hidden" name="format" value="{{ $field->format }}">
                    <x-form.help name="format-locked">
                        {{ trans('admin/custom_fields/general.format_locked_for_native_column', ['format' => $field->format]) }}
                    </x-form.help>
                @else
                    <x-form.help name="format">
                        {{ trans('admin/custom_fields/general.field_format_help') }}
                    </x-form.help>
                @endif
                {{-- Shown by JS when element is date_picker / datetime_picker AND format is ANY.
                     Explains the encryption use case for that combo. Component
                     renders id="format_picker_note-help" — matched by the JS
                     toggle in updateFormatPickerNote(). --}}
                <x-form.help name="format_picker_note" style="display:none;">
                    {{ trans('admin/custom_fields/general.format_any_with_date_picker_help') }}
                </x-form.help>
              <x-form.error name="format" />
            </div>
          </div>

          <!-- Element Type -->
          <div class="form-group {{ $errors->has('element') ? ' has-error' : '' }}" id="element_row">
            <label for="element" class="col-md-3 control-label">
              {{ trans('admin/custom_fields/general.field_element') }}
            </label>
            <div class="col-md-8 required">
                {{-- When format is DATE / DATETIME, JS below auto-selects the
                     matching picker option AND disables the other options in
                     this dropdown so the user can't pick something invalid. --}}
                <x-input.select
                    name="element"
                    :selected="old('element', $field->element)"
                    class="field_element"
                    id="element_select"
                    style="width: 100%;"
                    :options="[
                        'text' => trans('admin/custom_fields/general.types.text'),
                        'listbox' => trans('admin/custom_fields/general.types.listbox'),
                        'textarea' => trans('admin/custom_fields/general.types.textarea'),
                        'markdown-textarea' => trans('admin/custom_fields/general.types.markdown-textarea'),
                        'checkbox' => trans('admin/custom_fields/general.types.checkbox'),
                        'radio' => trans('admin/custom_fields/general.types.radio'),
                        'date_picker' => trans('admin/custom_fields/general.types.date_picker'),
                        'datetime_picker' => trans('admin/custom_fields/general.types.datetime_picker'),
                    ]"
                />
                <x-form.help name="element">
                    {{ trans('admin/custom_fields/general.field_element_help') }}
                </x-form.help>
                <x-form.error name="element" />
            </div>
          </div>

          <!-- Element values -->
          <div class="form-group {{ $errors->has('field_values') ? ' has-error' : '' }}" id="field_values_text" style="display:none;">
            <label for="field_values" class="col-md-3 control-label">
              {{ trans('admin/custom_fields/general.field_values') }}
            </label>
            <div class="col-md-8 required">
                <x-input.textarea
                    name="field_values"
                    :value="old('field_values', $field->field_values)"
                    style="width: 100%"
                    rows="4"
                    aria-label="field_values"
                />
              <x-form.error name="field_values" />
              <p class="help-block">{{ trans('admin/custom_fields/general.field_values_help') }}</p>
            </div>
          </div>
          <!-- Custom Format -->
          <div class="form-group {{ $errors->has('custom_format') ? ' has-error' : '' }}" id="custom_regex" style="display:none;">
            <label for="custom_format" class="col-md-3 control-label">
              {{ trans('admin/custom_fields/general.field_custom_format') }}
            </label>
            <div class="col-md-8 required">
                <input class="form-control" id="custom_format" aria-label="custom_format" maxlength="191" placeholder="regex:/^[0-9]{15}$/" name="custom_format" type="text" value="{{ old('custom_format', (($field->format!='') && (stripos($field->format,'regex')===0)) ? $field->format : '') }}">
                <p class="help-block">{!! trans('admin/custom_fields/general.field_custom_format_help') !!}</p>

              <x-form.error name="custom_format" />

            </div>
          </div>

          <!-- Help Text -->
          <div class="form-group {{ $errors->has('help_text') ? ' has-error' : '' }}">
              <label for="help_text" class="col-md-3 control-label">
                  {{ trans('admin/custom_fields/general.help_text') }}
              </label>
              <div class="col-md-8">
                  <input class="form-control" aria-label="help_text" name="help_text" type="text" value=" {{ old('help_text', $field->help_text) }}">
                  <p class="help-block">{{ trans('admin/custom_fields/general.help_text_description') }}</p>
                  <x-form.error name="help_text" />
              </div>
          </div>

         <!-- Set up checkbox form group -->
         <div class="form-group">

          <!-- Encrypted warning callout box -->
          @if (($field->id) && ($field->field_encrypted=='1'))
              <div class="col-md-9 col-md-offset-3">
                      <div class="alert alert-warning fade in">
                          <i class="fas fa-exclamation-triangle faa-pulse animated"></i>
                          <strong>{{ trans('general.notification_warning') }}:</strong>
                          {{ trans('admin/custom_fields/general.encrypted_options') }}
                      </div>

              </div>
          @endif

          @if (!$field->id)
              <!-- Encrypted  -->
              <div class="col-md-9 col-md-offset-3" id="encryption_section">
                  <label class="form-control">
                      <input type="checkbox" value="1" name="field_encrypted" id="field_encrypted"{{ (old('field_encrypted') || $field->field_encrypted) ? ' checked="checked"' : '' }}>
                      {{ trans('admin/custom_fields/general.encrypt_field') }}
                  </label>
                  {{-- Shown by JS when format is DATE or DATETIME. Sits inside
                       encryption_section so it disappears alongside the section
                       for any element type that hides the section entirely
                       (e.g., checkbox / radio). Component renders id
                       "encrypt_disabled_note-help" — matched by the JS toggle
                       in the format-change handler. --}}
                  <x-form.help name="encrypt_disabled_note" style="display:none;">
                      {{ trans('admin/custom_fields/general.encrypt_disabled_for_date_format') }}
                  </x-form.help>
              </div>
              <div class="col-md-9 col-md-offset-3" id="encrypt_warning" style="display:none;">
                  <div class="callout callout-danger" role="alert" aria-live="assertive" aria-atomic="true">
                      <p><x-icon type="warning" /> {{ trans('admin/custom_fields/general.encrypt_field_help') }}</p>
                  </div>
              </div>
          @endif



              <!-- Auto-Add to Future Fieldsets  -->
              <div class="col-md-9 col-md-offset-3" style="padding-bottom: 10px;">
                  <label class="form-control">
                      <input type="checkbox" name="auto_add_to_fieldsets" aria-label="auto_add_to_fieldsets" value="1"{{ (old('auto_add_to_fieldsets') || $field->auto_add_to_fieldsets) ? ' checked="checked"' : '' }}>
                      {{ trans('admin/custom_fields/general.auto_add_to_fieldsets') }}
                  </label>
              </div>

              <!-- Show in list view -->
              <div class="col-md-9 col-md-offset-3" style="padding-bottom: 10px;">
                  <label class="form-control">
                      <input type="checkbox" name="show_in_listview" aria-label="show_in_listview" value="1"{{ (old('show_in_listview') || $field->show_in_listview) ? ' checked="checked"' : '' }}>
                      {{ trans('admin/custom_fields/general.show_in_listview') }}
                  </label>
              </div>


              @if ((!$field->id) || ($field->field_encrypted=='0'))

              <!-- Show in requestable list view -->
              <div class="col-md-9 col-md-offset-3" id="show_in_requestable_list" style="padding-bottom: 10px;">
                  <label class="form-control">
                      <input type="checkbox" name="show_in_requestable_list" aria-label="show_in_requestable_list" value="1"{{ (old('show_in_requestable_list') || $field->show_in_requestable_list) ? ' checked="checked"' : '' }}>
                      {{ trans('admin/custom_fields/general.show_in_requestable_list') }}
                  </label>
              </div>

              <!-- Show in Email  -->
              <div class="col-md-9 col-md-offset-3" id="show_in_email" style="padding-bottom: 10px;">
                  <label class="form-control">
                      <input type="checkbox" name="show_in_email" aria-label="show_in_email" value="1"{{ (old('show_in_email') || $field->show_in_email) ? ' checked="checked"' : '' }}>
                      {{ trans('admin/custom_fields/general.show_in_email') }}
                  </label>
              </div>

              <!-- Value Must be Unique -->
              <div class="col-md-9 col-md-offset-3" id="is_unique" style="padding-bottom: 10px;">
                  <label class="form-control">
                      <input type="checkbox" name="is_unique" aria-label="is_unique" value="1"{{ (old('is_unique') || $field->is_unique) ? ' checked="checked"' : '' }}>
                      {{ trans('admin/custom_fields/general.is_unique') }}
                  </label>
              </div>
              @endif


             <!-- Show in Checkout Form  -->
             <div class="col-md-9 col-md-offset-3" id="display_checkout" style="padding-bottom: 10px;">
                 <label class="form-control">
                     <input type="checkbox" name="display_checkout" aria-label="display_checkout" value="1" {{ (old('display_checkout') || $field->display_checkout) ? ' checked="checked"' : '' }}>
                     {{ trans('admin/custom_fields/general.display_checkout') }}
                 </label>
             </div>

             <!-- Show in Checkin Form  -->
             <div class="col-md-9 col-md-offset-3" id="display_checkin" style="padding-bottom: 10px;">
                 <label class="form-control">
                     <input type="checkbox" name="display_checkin" aria-label="display_checkin" value="1" {{ (old('display_checkin') || $field->display_checkin) ? ' checked="checked"' : '' }}>
                     {{ trans('admin/custom_fields/general.display_checkin') }}
                 </label>
             </div>

             <!-- Show in Audit Form  -->
             <div class="col-md-9 col-md-offset-3" id="display_audit" style="padding-bottom: 10px;">
                 <label class="form-control">
                     <input type="checkbox" name="display_audit" aria-label="display_audit" value="1" {{ (old('display_audit') || $field->display_audit) ? ' checked="checked"' : '' }}>
                     {{ trans('admin/custom_fields/general.display_audit') }}
                 </label>
             </div>


             <!-- Show in View All Assets profile view  -->
              <div class="col-md-9 col-md-offset-3" id="display_in_user_view">
                  <label class="form-control">
                      <input type="checkbox" name="display_in_user_view" aria-label="display_in_user_view" value="1" {{ (old('display_in_user_view') || $field->display_in_user_view) ? ' checked="checked"' : '' }}>
                      {{ trans('admin/custom_fields/general.display_in_user_view') }}
                  </label>
              </div>



          </div>

          </div>

          @if ($fieldsets->count() > 0)
          <!-- begin fieldset columns -->
          <div class="col-md-4">

              <h4>{{ trans('admin/custom_fields/general.fieldsets') }}</h4>
              <x-form.error name="associate_fieldsets" />

              <label class="form-control">
                  <input type="checkbox" id="checkAll">
                  {{ trans('general.select_all') }}
              </label>

                @foreach ($fieldsets as $fieldset)
                    @php
                        $array_fieldname = 'associate_fieldsets.'.$fieldset->id;

                        // Consider the form data first
                        if (old($array_fieldname) == $fieldset->id) {
                            $checked = 'checked';
                        // Otherwise check DB
                        } elseif (isset($field->fieldset) && ($field->fieldset->contains($fieldset->id))) {
                            $checked = 'checked';
                        } else {
                            $checked = '';
                        }
                    @endphp

                          <label class="form-control{{ $errors->has('associate_fieldsets.'.$fieldset->id) ? ' has-error' : '' }}">
                              <input type="checkbox"
                                     name="associate_fieldsets[{{ $fieldset->id }}]"
                                     class="fieldset"
                                     value="{{ $fieldset->id }}"
                                    {{ $checked }}>
                              {{ $fieldset->name }}
                              <x-form.error :name="'associate_fieldsets.'.$fieldset->id" />

                          </label>

                @endforeach

          </div>
          @endif
      </div> <!-- /.box-body-->

      <div class="box-footer text-right">
        <button type="submit" class="btn btn-primary"> {{ trans('general.save') }}</button>
      </div>

    </div> <!--.box.box-default-->


  </div> <!--/.col-md-9-->


</div>
</form>
@stop

@section('moar_scripts')
<script nonce="{{ csrf_token() }}">
    $(document).ready(function(){

        $("#checkAll").change(function () {
            $(".fieldset").prop('checked', $(this).prop("checked"));
        });

        // Only display the custom format field if it's a custom format validation type
        $(".format").change(function(){
            $(this).find("option:selected").each(function(){
                if ($('.format').prop("selectedIndex") == 1) {
                    $("#custom_regex").show();
                } else{
                    $("#custom_regex").hide();
                }
            });

            var pickedFormat = $(this).val();
            var isDateFormat = (pickedFormat === 'DATE' || pickedFormat === 'DATETIME');

            // DATE / DATETIME fields are stored in native date columns which
            // can't hold ciphertext, so encryption is disallowed. Leave the
            // checkbox visible so the user sees the option exists, but
            // uncheck + disable it. Trigger change so the dependent warning
            // and related visibility toggles run.
            $("#field_encrypted")
                .prop('disabled', isDateFormat)
                .prop('checked', isDateFormat ? false : $("#field_encrypted").prop('checked'))
                .trigger('change');

            // Explain WHY the checkbox is disabled when the format is DATE
            // or DATETIME. ID is generated by <x-form.help name="..."> as
            // "{name}-help".
            $("#encrypt_disabled_note-help").toggle(isDateFormat);

            // With DATE/DATETIME format, force the element to the matching
            // picker option and disable the other options so the user can't
            // pick something invalid. Anything else — re-enable everything.
            var $elementOptions = $("#element_select > option");
            if (isDateFormat) {
                var matchingElement = (pickedFormat === 'DATETIME') ? 'datetime_picker' : 'date_picker';
                $elementOptions.each(function () {
                    $(this).prop('disabled', $(this).val() !== matchingElement);
                });
                $("#element_select").val(matchingElement).trigger('change');
            } else {
                $elementOptions.prop('disabled', false);
                // Don't force-change the user's element pick here — only
                // reset if the previous value was one of the forced pickers.
                var currentEl = $("#element_select").val();
                if (currentEl === 'date_picker' || currentEl === 'datetime_picker') {
                    // Leave whatever they had; user can pick another manually.
                }
            }

            // Nudge the format-picker help note (element handler recomputes
            // it too — this catches the case where format alone changed).
            updateFormatPickerNote();
        }).change();

        // Shown when the user has explicitly picked date_picker /
        // datetime_picker as the element AND left the format as ANY. That
        // combo IS supported (needed for encrypted date fields) but isn't
        // the common path — surface the reason so users don't wonder.
        function updateFormatPickerNote() {
            var el = $("#element_select").val();
            var fmt = $(".format").val();
            var isPickerElement = (el === 'date_picker' || el === 'datetime_picker');
            var isAnyFormat = (fmt === 'ANY' || fmt === '' || fmt == null);
            $("#format_picker_note-help").toggle(isPickerElement && isAnyFormat);
        }

        // If the element is a radiobutton/checkbox, doesn't show the format input box
        $(".field_element").change(function(){
            $(this).find("option:selected").each(function(){
                if (($(this).attr("value") != "radio") && ($(this).attr("value") != "checkbox")){
                    $("#format_values").show();
                } else{
                    $("#format_values").hide();
                }
            });
        }).change();

        // Field Values input is only meaningful for elements that need a
        // predefined set of options (listbox, checkbox, radio). Datepickers
        // and text inputs have no options list, so hide it there.
        // Encryption is disallowed for checkbox / radio elements (those
        // store comma-separated values that don't play nicely with encrypt).
        $(".field_element").change(function(){
            var el = $(this).val();
            var needsValues = (el === 'listbox' || el === 'checkbox' || el === 'radio');
            var blocksEncryption = (el === 'checkbox' || el === 'radio');

            $("#field_values_text").toggle(needsValues);
            if (blocksEncryption) {
                $("#encryption_section").hide();
            } else {
                $("#encryption_section").show();
            }

            // Also re-evaluate the format-picker help note when element
            // changes (e.g., user switches to date_picker on an ANY format).
            updateFormatPickerNote();
        }).change();
    });


    $("#field_encrypted").change(function() {
        if (this.checked) {
            $("#encrypt_warning").show();
            $("#show_in_email").hide();
            $("#display_in_user_view").hide();
            $("#is_unique").hide();
            $("#show_in_requestable_list").hide();
        } else {
            $("#encrypt_warning").hide();
            $("#show_in_email").show();
            $("#display_in_user_view").show();
            $("#is_unique").show();
            $("#show_in_requestable_list").show();
        }
    });



</script>
@stop
