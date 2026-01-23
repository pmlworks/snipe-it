<?php

namespace App\Presenters;

/**
 * Class GroupPresenter
 */
class GroupPresenter extends Presenter
{
    /**
     * Json Column Layout for bootstrap table
     */
    public static function dataTableLayout()
    {
        $layout = [
            [
                'field' => 'id',
                'searchable' => false,
                'sortable' => true,
                'switchable' => true,
                'title' => trans('general.id'),
                'visible' => false,
            ],
            [
                'field' => 'name',
                'searchable' => true,
                'sortable' => true,
                'switchable' => false,
                'title' => trans('general.name'),
                'visible' => true,
                'formatter' => 'groupsAdminLinkFormatter',
            ],
            [
                'field' => 'users_count',
                'searchable' => false,
                'sortable' => true,
                'switchable' => true,
                'title' =>  trans('admin/groups/table.users'),
                'visible' => true,
                'class' => 'css-users',
            ],  [
                'field' => 'notes',
                'searchable' => true,
                'sortable' => true,
                'visible' => true,
                'title' => trans('general.notes'),
            ], [
                'field' => 'created_by',
                'searchable' => true,
                'sortable' => true,
                'switchable' => true,
                'title' => trans('general.created_by'),
                'visible' => true,
                'formatter' => 'usersLinkObjFormatter',
            ],  [
                'field' => 'updated_at',
                'searchable' => true,
                'sortable' => true,
                'switchable' => true,
                'title' => trans('general.updated_at'),
                'visible' => true,
                'formatter' => 'dateDisplayFormatter',
            ], [
                'field' => 'actions',
                'searchable' => false,
                'sortable' => false,
                'switchable' => false,
                'title' => trans('table.actions'),
                'visible' => true,
                'formatter' => 'groupsActionsFormatter',
                'printIgnore' => true,
            ],
        ];

        return json_encode($layout);
    }


    /**
     * Link to this supplier name
     * @return string
     */
    public function nameUrl()
    {
        return (string) link_to_route('suppliers.show', $this->name, $this->id);
    }

    /**
     * Getter for Polymorphism.
     * @return mixed
     */
    public function name()
    {
        return $this->model->name;
    }

    /**
     * Url to view this item.
     * @return string
     */
    public function viewUrl()
    {
        return route('suppliers.show', $this->id);
    }

    public function glyph()
    {
        return '<x-icon type="suppliers" />';
    }

    public function fullName()
    {
        return $this->name;
    }

    public function formattedNameLink() {

        if (auth()->user()->can('view', ['\App\Models\Supplier', $this])) {
            return ($this->tag_color ? "<i class='fa-solid fa-fw fa-square' style='color: ".e($this->tag_color)."' aria-hidden='true'></i>" : '').'<a href="'.route('suppliers.show', e($this->id)).'">'.e($this->name).'</a>';
        }

        return ($this->tag_color ? "<i class='fa-solid fa-fw fa-square' style='color: ".e($this->tag_color)."' aria-hidden='true'></i> " : '').$this->name;
    }
}
