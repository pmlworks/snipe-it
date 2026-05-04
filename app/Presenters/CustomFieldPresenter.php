<?php

namespace App\Presenters;

use App\Models\CustomField;

final class CustomFieldPresenter
{
    private CustomField $field;

    public function __construct(CustomField $field)
    {
        $this->field = $field;
    }

    /**
     * @return string[]
     */
    public function visibilityIconsArray(): array
    {
        $icons = [];

        if ($this->field->display_checkout) {
            $icons[] = '<span title="'.e(trans('admin/custom_fields/general.display_checkout')).'" data-tooltip="true"><i class="fa-solid fa-rotate-left text-muted"></i></span>';
        }

        if ($this->field->display_checkin) {
            $icons[] = '<span title="'.e(trans('admin/custom_fields/general.display_checkin')).'" data-tooltip="true"><i class="fa-solid fa-rotate-right text-muted"></i></span>';
        }

        if ($this->field->display_audit) {
            $icons[] = '<span title="'.e(trans('admin/custom_fields/general.display_audit')).'" data-tooltip="true"><i class="fas fa-clipboard-check text-muted"></i></span>';
        }

        if ($this->field->display_in_user_view) {
            $icons[] = '<span title="'.e(trans('admin/custom_fields/general.display_in_user_view_table')).'" data-tooltip="true"><i class="fas fa-user text-muted"></i></span>';
        }

        if ($this->field->show_in_listview) {
            $icons[] = '<span title="'.e(trans('admin/custom_fields/general.show_in_listview_short')).'" data-tooltip="true"><i class="fas fa-list text-muted"></i></span>';
        }

        if ($this->field->show_in_email) {
            $icons[] = '<span title="'.e(trans('admin/custom_fields/general.show_in_email_short')).'" data-tooltip="true"><i class="fas fa-envelope text-muted"></i></span>';
        }

        if ($this->field->show_in_requestable_list) {
            $icons[] = '<span title="'.e(trans('admin/custom_fields/general.show_in_requestable_list_short')).'" data-tooltip="true"><i class="fa-solid fa-bell-concierge text-muted"></i></span>';
        }

        return $icons;
    }

    public function visibilityIcons(): string
    {
        return implode(' ', $this->visibilityIconsArray());
    }
}

